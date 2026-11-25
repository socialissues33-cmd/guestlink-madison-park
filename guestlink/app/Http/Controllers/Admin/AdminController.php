<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiTriageResult;
use App\Models\FoodCategory;
use App\Models\FoodItem;
use App\Models\FoodOrder;
use App\Models\FoodOrderStatusLog;
use App\Models\RequestCategory;
use App\Models\RequestStatusLog;
use App\Models\Room;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class AdminController extends Controller
{
    // ── Dashboard ──────────────────────────────────────────────────────────
    public function dashboard()
    {
        $roomCounts = Room::selectRaw("
            COUNT(*) as total,
            SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active,
            SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) as inactive
        ")->first();

        $staffCounts = User::selectRaw("
            COUNT(*) as total,
            SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active,
            SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) as inactive
        ")->first();

        $requestCounts = ServiceRequest::selectRaw("
            COUNT(*) as total,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN LOWER(COALESCE(priority, '')) = 'urgent' AND status NOT IN ('resolved','closed') THEN 1 ELSE 0 END) as urgent_open,
            SUM(CASE WHEN status IN ('resolved','closed') AND DATE(updated_at) = ? THEN 1 ELSE 0 END) as resolved_today
        ", [now()->toDateString()])->first();

        $orderCounts = FoodOrder::selectRaw("
            COUNT(*) as total,
            SUM(CASE WHEN status NOT IN ('delivered','cancelled') THEN 1 ELSE 0 END) as active,
            COALESCE(SUM(CASE WHEN status = 'delivered' THEN total_amount ELSE 0 END), 0) as delivered_revenue
        ")->first();

        $menuCounts = FoodItem::selectRaw("
            COUNT(*) as total,
            SUM(CASE WHEN is_available = 1 THEN 1 ELSE 0 END) as available,
            SUM(CASE WHEN is_available = 0 THEN 1 ELSE 0 END) as unavailable
        ")->first();

        $aiCounts = AiTriageResult::selectRaw("
            COUNT(*) as total,
            SUM(CASE WHEN status = 'accepted' THEN 1 ELSE 0 END) as accepted,
            SUM(CASE WHEN status IN ('modified', 'overridden') THEN 1 ELSE 0 END) as overridden,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending
        ")->first();

        $aiReviewed = (int) ($aiCounts->accepted ?? 0) + (int) ($aiCounts->overridden ?? 0);
        $aiAcceptanceRate = $aiReviewed > 0
            ? (int) round(((int) ($aiCounts->accepted ?? 0) / $aiReviewed) * 100)
            : null;

        $stats = [
            'total_rooms'           => (int) ($roomCounts->total ?? 0),
            'active_rooms'          => (int) ($roomCounts->active ?? 0),
            'inactive_rooms'        => (int) ($roomCounts->inactive ?? 0),
            'total_staff'           => (int) ($staffCounts->total ?? 0),
            'active_staff'          => (int) ($staffCounts->active ?? 0),
            'inactive_staff'        => (int) ($staffCounts->inactive ?? 0),
            'total_requests'        => (int) ($requestCounts->total ?? 0),
            'pending_requests'      => (int) ($requestCounts->pending ?? 0),
            'urgent_open'           => (int) ($requestCounts->urgent_open ?? 0),
            'resolved_today'        => (int) ($requestCounts->resolved_today ?? 0),
            'total_food_orders'     => (int) ($orderCounts->total ?? 0),
            'active_food_orders'    => (int) ($orderCounts->active ?? 0),
            'delivered_revenue'     => (float) ($orderCounts->delivered_revenue ?? 0),
            'total_food_items'      => (int) ($menuCounts->total ?? 0),
            'available_food_items'  => (int) ($menuCounts->available ?? 0),
            'unavailable_food_items'=> (int) ($menuCounts->unavailable ?? 0),
            'active_categories'     => RequestCategory::where('is_active', true)->count(),
            'total_categories'      => RequestCategory::count(),
        ];

        // SQLite health telemetry
        $dbDriver = config('database.default');
        $dbPath = config("database.connections.{$dbDriver}.database");
        $dbSizeKb = ($dbDriver === 'sqlite' && is_string($dbPath) && file_exists($dbPath))
            ? round(filesize($dbPath) / 1024, 1)
            : null;
        $dbJournal = 'N/A';
        if ($dbDriver === 'sqlite') {
            try {
                $pragma = DB::selectOne('PRAGMA journal_mode');
                $dbJournal = strtoupper((string) ($pragma->journal_mode ?? 'SQLITE'));
            } catch (\Throwable $e) {
                $dbJournal = 'SQLITE';
            }
        }

        $systemHealth = [
            'gemini_configured'     => !empty(config('services.gemini.key')),
            'gemini_model'          => config('services.gemini.model', 'gemini-3.5-flash'),
            'ai_total'              => (int) ($aiCounts->total ?? 0),
            'ai_accepted'           => (int) ($aiCounts->accepted ?? 0),
            'ai_overridden'         => (int) ($aiCounts->overridden ?? 0),
            'ai_pending'            => (int) ($aiCounts->pending ?? 0),
            'ai_acceptance_rate'    => $aiAcceptanceRate,
            'bot_auto_reply'        => User::isBotAutoReplyDefaultEnabled(),
            'kitchen_ordering_open' => (bool) Cache::get('guestlink_kitchen_ordering_open', true),
            'guest_notice'          => (string) Cache::get('guestlink_guest_notice', ''),
            'db_driver'             => strtoupper($dbDriver),
            'db_size_kb'            => $dbSizeKb,
            'db_journal'            => $dbJournal,
            'php_version'           => PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION,
            'laravel_version'       => app()->version(),
        ];

        // Combined Recent Governance & Activity Feed (Requests + Kitchen Orders)
        $reqLogs = RequestStatusLog::with(['changedBy:id,name', 'serviceRequest:id,reference_number,room_id', 'serviceRequest.room:id,room_number'])
            ->latest('created_at')
            ->limit(8)
            ->get()
            ->map(function ($log) {
                $req = $log->serviceRequest;
                return [
                    'kind'       => 'request',
                    'icon'       => 'bi-ticket-perforated',
                    'actor'      => $log->changedBy->name ?? 'Guest / System',
                    'ref'        => $req->reference_number ?? ('REQ #' . $log->service_request_id),
                    'room'       => $req?->room?->room_number,
                    'from'       => $log->from_status,
                    'to'         => $log->to_status,
                    'notes'      => $log->notes,
                    'url'        => $req ? route('requests.show', $req->id) : null,
                    'created_at' => $log->created_at,
                ];
            });

        $orderLogs = FoodOrderStatusLog::with(['changedBy:id,name', 'foodOrder:id,reference_number,room_id', 'foodOrder.room:id,room_number'])
            ->latest('created_at')
            ->limit(8)
            ->get()
            ->map(function ($log) {
                $ord = $log->foodOrder;
                return [
                    'kind'       => 'order',
                    'icon'       => 'bi-cup-hot',
                    'actor'      => $log->changedBy->name ?? 'Guest / Kitchen',
                    'ref'        => $ord->reference_number ?? ('ORD #' . $log->food_order_id),
                    'room'       => $ord?->room?->room_number,
                    'from'       => $log->from_status,
                    'to'         => $log->to_status,
                    'notes'      => $log->notes,
                    'url'        => $ord ? route('food.orders.show', $ord->id) : null,
                    'created_at' => $log->created_at,
                ];
            });

        $recentActivity = $reqLogs->concat($orderLogs)
            ->sortByDesc('created_at')
            ->take(8)
            ->values();

        $roleScopes = [
            'admin'                  => 'Full System & RBAC',
            'owner'                  => 'Executive Analytics & Ops',
            'front_desk'             => 'Tickets, Chat & Room QR',
            'maintenance'            => 'Engineering & Repairs',
            'maintenance_supervisor' => 'Maintenance Dispatch',
            'restaurant'             => 'Times Square Kitchen KDS',
            'housekeeping'           => 'Room Services & Amenities',
        ];

        $roles = Role::withCount('users')->get();

        return view('admin.dashboard', compact('stats', 'systemHealth', 'recentActivity', 'roles', 'roleScopes'));
    }

    /**
     * Save hotel-wide operational controls from the Admin Overview panel.
     */
    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'bot_auto_reply_enabled' => 'nullable|boolean',
            'kitchen_ordering_open'  => 'nullable|boolean',
            'guest_notice'           => 'nullable|string|max:180',
        ]);

        $botEnabled = $request->boolean('bot_auto_reply_enabled');
        $kitchenOpen = $request->boolean('kitchen_ordering_open');
        $guestNotice = trim((string) ($validated['guest_notice'] ?? ''));

        Cache::forever('guestlink_hotel_bot_auto_reply', $botEnabled);
        Cache::forever('guestlink_kitchen_ordering_open', $kitchenOpen);
        Cache::forever('guestlink_guest_notice', $guestNotice);

        // Sync current admin's account preference & active open tickets immediately
        if ($request->user()) {
            $request->user()->forceFill(['bot_auto_reply_enabled' => $botEnabled])->save();
        }
        ServiceRequest::whereNotIn('status', ['resolved', 'closed'])->update([
            'auto_reply_bot' => $botEnabled,
        ]);

        return back()->with('success', 'Hotel operational settings saved and applied live.');
    }

    /**
     * Download a consistent point-in-time SQLite database snapshot (Admin only).
     */
    public function downloadDatabaseBackup()
    {
        $driver = config('database.default');
        $dbPath = config("database.connections.{$driver}.database");

        if ($driver !== 'sqlite' || !is_string($dbPath) || !file_exists($dbPath)) {
            return back()->with('error', 'SQLite database file is not available for direct backup in this environment.');
        }

        $filename = 'madison-park-backup-' . now()->format('Y-m-d_Hi') . '.sqlite';
        $tmpDir = storage_path('app/backups');
        if (!is_dir($tmpDir)) {
            @mkdir($tmpDir, 0755, true);
        }
        $snapshotPath = $tmpDir . DIRECTORY_SEPARATOR . 'snap_' . uniqid() . '.sqlite';

        try {
            $escaped = str_replace("'", "''", str_replace('\\', '/', $snapshotPath));
            DB::statement("VACUUM INTO '{$escaped}'");
        } catch (\Throwable $e) {
            if (!@copy($dbPath, $snapshotPath)) {
                return back()->with('error', 'Unable to create database backup snapshot.');
            }
        }

        return response()->download($snapshotPath, $filename, [
            'Content-Type' => 'application/x-sqlite3',
        ])->deleteFileAfterSend(true);
    }

    // ── User Management ────────────────────────────────────────────────────
    public function users(?Request $request = null)
    {
        $request = $request ?? request();
        $query = User::with('roles');
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('department', 'like', "%{$search}%")
                  ->orWhereHas('roles', fn($r) => $r->where('name', 'like', "%{$search}%"));
            });
        }
        $users = $query->latest()->paginate(20)->withQueryString();
        $roles = Role::all();
        return view('admin.users', compact('users', 'roles'));
    }

    public function storeUser(Request $request)
    {
        $passwordRule = $request->has('password_confirmation')
            ? 'required|string|min:6|confirmed'
            : 'required|string|min:6';

        $validated = $request->validate([
            'name'       => 'required|string|max:100',
            'username'   => 'nullable|string|max:64|unique:users,username',
            'email'      => 'nullable|email|unique:users,email',
            'password'   => $passwordRule,
            'role'       => 'required|exists:roles,name',
            'department' => 'nullable|string|max:100',
        ]);

        $username = !empty($validated['username'])
            ? strtolower(trim($validated['username']))
            : (!empty($validated['email'])
                ? strtolower(explode('@', $validated['email'])[0])
                : \Illuminate\Support\Str::slug($validated['name'], '_') . '_' . rand(10, 99));

        if (User::where('username', $username)->exists()) {
            $username .= '_' . rand(10, 99);
        }

        $email = !empty($validated['email'])
            ? trim($validated['email'])
            : ($username . '@madisonpark.com');

        if (User::where('email', $email)->exists()) {
            $email = $username . '_' . rand(10, 99) . '@madisonpark.com';
        }

        DB::transaction(function () use ($validated, $username, $email) {
            $user = User::create([
                'name'       => $validated['name'],
                'username'   => $username,
                'email'      => $email,
                'password'   => Hash::make($validated['password']),
                'department' => $validated['department'] ?? null,
                'is_active'  => true,
            ]);
            $user->assignRole($validated['role']);
        });

        return back()->with('success', 'User created successfully.');
    }

    public function toggleUser(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Security Policy: You cannot deactivate your own active session account.');
        }

        if ($user->is_active && $user->hasRole('admin') && User::role('admin')->where('is_active', true)->where('id', '!=', $user->id)->doesntExist()) {
            return back()->with('error', 'Security Policy: Cannot deactivate the only active Administrator on this system.');
        }

        $user->update(['is_active' => !$user->is_active]);
        return back()->with('success', 'User status updated.');
    }

    public function updateUser(Request $request, User $user)
    {
        $passwordRule = ($request->filled('password') && $request->has('password_confirmation'))
            ? 'nullable|string|min:6|confirmed'
            : 'nullable|string|min:6';

        $validated = $request->validate([
            'name'       => 'required|string|max:100',
            'username'   => 'nullable|string|max:64|unique:users,username,' . $user->id,
            'email'      => 'nullable|email|unique:users,email,' . $user->id,
            'role'       => 'required|exists:roles,name',
            'department' => 'nullable|string|max:100',
            'password'   => $passwordRule,
            'is_active'  => 'nullable|boolean',
        ]);

        $newIsActive = $request->has('is_active') ? $request->boolean('is_active') : $user->is_active;

        if ($user->id === auth()->id()) {
            if (!$newIsActive) {
                return back()->with('error', 'Security Policy: You cannot deactivate your own administrative account.');
            }
            if ($validated['role'] !== 'admin' && $user->hasRole('admin')) {
                return back()->with('error', 'Security Policy: You cannot remove the Admin role from your own account.');
            }
        }

        if ($user->hasRole('admin') && (!$newIsActive || $validated['role'] !== 'admin')) {
            $otherActiveAdmins = User::role('admin')->where('is_active', true)->where('id', '!=', $user->id)->exists();
            if (!$otherActiveAdmins) {
                return back()->with('error', 'Security Policy: At least one active Administrator must remain on the system.');
            }
        }

        $username = !empty($validated['username'])
            ? strtolower(trim($validated['username']))
            : ($user->username ?: (!empty($validated['email']) ? strtolower(explode('@', $validated['email'])[0]) : \Illuminate\Support\Str::slug($validated['name'], '_')));

        $email = !empty($validated['email'])
            ? trim($validated['email'])
            : ($user->email ?: ($username . '@madisonpark.com'));

        $updateData = [
            'name'       => $validated['name'],
            'username'   => $username,
            'email'      => $email,
            'department' => $validated['department'] ?? null,
            'is_active'  => $newIsActive,
        ];

        if (!empty($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        DB::transaction(function () use ($user, $updateData, $validated) {
            $user->update($updateData);
            $user->syncRoles([$validated['role']]);
        });

        return back()->with('success', "Staff user '{$user->name}' updated successfully.");
    }

    public function destroyUser(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Security Policy: You cannot delete your own administrative account.');
        }

        if ($user->hasRole('admin') && User::role('admin')->where('is_active', true)->where('id', '!=', $user->id)->doesntExist()) {
            return back()->with('error', 'Security Policy: Cannot delete the only remaining active Administrator on this system.');
        }

        $userName = $user->name;
        $user->delete();

        return back()->with('success', "Staff user '{$userName}' deleted successfully.");
    }

    // ── Room Management ────────────────────────────────────────────────────
    public function rooms(?Request $request = null)
    {
        $request = $request ?? request();
        $query = Room::query();
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('room_number', 'like', "%{$search}%")
                  ->orWhere('room_type', 'like', "%{$search}%")
                  ->orWhere('floor', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }
        $rooms = $query->orderBy('floor')->orderBy('room_number')->paginate(20)->withQueryString();
        $wifiConfig = \App\Services\QrUrlService::getWifiConfig();
        $sampleRoom = $rooms->first() ?? Room::first();
        $detectedLanIp = $sampleRoom ? (\App\Services\QrUrlService::resolveRoomUrls($sampleRoom)['lanHost'] ?? '192.168.254.147') : '192.168.254.147';
        return view('admin.rooms', array_merge(compact('rooms', 'detectedLanIp'), $wifiConfig));
    }

    public function resetWifi(Request $request)
    {
        $validated = $request->validate([
            'wifi_ssid'     => 'required|string|max:64',
            'wifi_password' => 'nullable|string|max:64',
            'qr_mode'       => 'nullable|in:local,public',
            'custom_lan_ip' => 'nullable|string|max:64',
        ]);

        \App\Services\QrUrlService::updateWifiConfig(
            $validated['wifi_ssid'],
            $validated['wifi_password'] ?? '',
            $validated['qr_mode'] ?? null,
            $validated['custom_lan_ip'] ?? null
        );

        return back()->with('success', 'Hotel Wi-Fi & QR network settings updated! All Wi-Fi & Room QRs are now synced.');
    }

    public function switchQrMode(Request $request)
    {
        $validated = $request->validate([
            'qr_mode'       => 'required|in:local,public',
            'custom_lan_ip' => 'nullable|string|max:64',
        ]);

        $cfg = \App\Services\QrUrlService::setQrMode(
            $validated['qr_mode'],
            $request->has('custom_lan_ip') ? $validated['custom_lan_ip'] : null
        );

        $modeLabel = $cfg['qrMode'] === 'local'
            ? 'Local Wi-Fi / Hotspot Network QR (Option A)'
            : 'Worldwide Cloudflare Tunnel QR';

        return back()->with('success', "Default Room QR Mode switched to: {$modeLabel}.");
    }

    public function defenseQrSheet(Request $request)
    {
        $requestedRooms = $request->input('rooms', ['201', '202', '203']);
        if (!is_array($requestedRooms) || count($requestedRooms) === 0) {
            $requestedRooms = ['201', '202', '203'];
        }

        $selectedRooms = Room::whereIn('room_number', $requestedRooms)
            ->orderBy('floor')
            ->orderBy('room_number')
            ->take(3)
            ->get();

        if ($selectedRooms->count() < 3) {
            $selectedRooms = Room::where('is_active', true)
                ->orderBy('floor')
                ->orderBy('room_number')
                ->take(3)
                ->get();
        }

        $cards = $selectedRooms->map(fn(Room $r) => \App\Services\QrUrlService::resolveRoomUrls($r));
        $wifiConfig = \App\Services\QrUrlService::getWifiConfig();
        $allRooms = Room::where('is_active', true)->orderBy('floor')->orderBy('room_number')->get();
        $activeMode = $request->query('mode', $wifiConfig['qrMode'] ?? 'local');

        return view('admin.qr-defense-sheet', array_merge([
            'cards'      => $cards,
            'allRooms'   => $allRooms,
            'activeMode' => $activeMode,
        ], $wifiConfig));
    }

    public function storeRoom(Request $request)
    {
        $validated = $request->validate([
            'room_number' => 'required|string|unique:rooms',
            'room_type'   => 'required|string',
            'floor'       => 'required|integer|min:1',
            'description' => 'nullable|string|max:200',
        ]);

        Room::create($validated);
        return back()->with('success', 'Room created with QR code.');
    }

    public function updateRoom(Request $request, Room $room)
    {
        $validated = $request->validate([
            'room_number' => 'required|string|unique:rooms,room_number,' . $room->id,
            'room_type'   => 'required|string',
            'floor'       => 'required|integer|min:1',
            'description' => 'nullable|string|max:200',
            'is_active'   => 'nullable|boolean',
        ]);

        $updateData = [
            'room_number' => $validated['room_number'],
            'room_type'   => $validated['room_type'],
            'floor'       => $validated['floor'],
            'description' => $validated['description'] ?? null,
            'is_active'   => $request->has('is_active') ? $request->boolean('is_active') : $room->is_active,
        ];

        $room->update($updateData);

        return back()->with('success', "Room {$room->room_number} updated successfully.");
    }

    public function toggleRoom(Room $room)
    {
        $becomingActive = !$room->is_active;
        $updateData = ['is_active' => $becomingActive];
        if ($becomingActive) {
            $updateData['checked_in_at'] = now();
        }
        $room->update($updateData);
        $statusText = $room->is_active ? 'Activated (new stay session started)' : 'Deactivated';
        return back()->with('success', "Room {$room->room_number} {$statusText}.");
    }

    public function destroyRoom(Room $room)
    {
        $activeReqs = $room->serviceRequests()->whereNotIn('status', ['resolved', 'closed'])->count();
        $activeOrds = $room->foodOrders()->whereNotIn('status', ['delivered', 'cancelled'])->count();

        if ($activeReqs > 0 || $activeOrds > 0) {
            return back()->with('error', "Cannot delete Room {$room->room_number}: It currently has {$activeReqs} unresolved request(s) and {$activeOrds} active order(s). Resolve or close them first.");
        }

        if ($room->serviceRequests()->exists() || $room->foodOrders()->exists()) {
            return back()->with('error', "Cannot permanently delete Room {$room->room_number} because historical requests/folios are attached to it. Deactivate the room instead to preserve financial audit records.");
        }

        $roomNum = $room->room_number;
        $room->delete();

        return back()->with('success', "Room {$roomNum} deleted successfully.");
    }

    public function regenerateQr(Room $room)
    {
        $room->update([
            'qr_token'      => \Illuminate\Support\Str::random(32),
            'checked_in_at' => now(),
        ]);
        return back()->with('success', "QR code regenerated & new guest stay session started for Room {$room->room_number}.");
    }

    public function roomFolio(Room $room)
    {
        $ordersQuery = FoodOrder::with(['orderItems.foodItem'])
            ->where('room_id', $room->id)
            ->where('status', 'delivered')
            ->orderBy('created_at');

        $requestsQuery = ServiceRequest::where('room_id', $room->id);

        if ($room->checked_in_at) {
            $ordersQuery->where('created_at', '>=', $room->checked_in_at);
            $requestsQuery->where('created_at', '>=', $room->checked_in_at);
        }

        $deliveredOrders = $ordersQuery->get();
        $stayRequestsCount = $requestsQuery->count();

        $roomChargedOrders = $deliveredOrders->filter(fn($o) => $o->isChargeToRoom());
        $paidDirectOrders  = $deliveredOrders->filter(fn($o) => $o->isPayOnArrival());

        $subtotal = (float) $roomChargedOrders->sum('total_amount');
        $serviceCharge = round($subtotal * 0.10, 2);
        $grandTotal = round($subtotal + $serviceCharge, 2);
        $paidDirectlyTotal = round((float) $paidDirectOrders->sum('total_amount'), 2);

        return view('admin.room-folio', compact(
            'room',
            'deliveredOrders',
            'stayRequestsCount',
            'subtotal',
            'serviceCharge',
            'grandTotal',
            'paidDirectlyTotal'
        ));
    }

    public function downloadQr(Room $room)
    {
        $qrData = \App\Services\QrUrlService::resolveRoomUrls($room);

        return view('admin.qr-print', array_merge([
            'room' => $room,
            'url'  => $qrData['publicUrl'],
            'qr'   => $qrData['publicQr'],
        ], $qrData));
    }

    // ── Category Management ────────────────────────────────────────────────
    public function categories(?Request $request = null)
    {
        $request = $request ?? request();
        $query = RequestCategory::query();
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('type', 'like', "%{$search}%");
            });
        }
        $categories = $query->latest()->paginate(20)->withQueryString();
        return view('admin.categories', compact('categories'));
    }

    public function storeCategory(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'type' => 'required|in:service,maintenance',
            'icon' => 'nullable|string|max:32',
        ]);
        RequestCategory::create($validated);
        return back()->with('success', 'Category added.');
    }

    public function updateCategory(Request $request, RequestCategory $category)
    {
        $validated = $request->validate([
            'name'      => 'required|string|max:100',
            'type'      => 'required|in:service,maintenance',
            'icon'      => 'nullable|string|max:32',
            'is_active' => 'nullable|boolean',
        ]);

        $updateData = [
            'name'      => $validated['name'],
            'type'      => $validated['type'],
            'icon'      => $validated['icon'] ?? $category->icon,
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : $category->is_active,
        ];

        $category->update($updateData);

        return back()->with('success', "Category '{$category->name}' updated successfully.");
    }

    public function toggleCategory(RequestCategory $category)
    {
        $category->update(['is_active' => !$category->is_active]);
        $statusText = $category->is_active ? 'Activated' : 'Deactivated';
        return back()->with('success', "Category '{$category->name}' {$statusText}.");
    }

    public function destroyCategory(RequestCategory $category)
    {
        if ($category->serviceRequests()->exists()) {
            return back()->with('error', "Cannot delete '{$category->name}' because guest requests reference this category. Deactivate it instead.");
        }

        $catName = $category->name;
        $category->delete();

        return back()->with('success', "Category '{$catName}' deleted successfully.");
    }

    // ── Reports ────────────────────────────────────────────────────────────
    public function reports(Request $request)
    {
        $dateFrom = $request->input('date_from', now()->startOfMonth()->toDateString());
        $dateTo   = $request->input('date_to', now()->toDateString());
        $range    = [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'];

        $requestStats = ServiceRequest::whereBetween('created_at', $range)
            ->selectRaw("status, count(*) as count")
            ->groupBy('status')
            ->pluck('count', 'status');

        $orderStats = FoodOrder::whereBetween('created_at', $range)
            ->selectRaw("status, count(*) as count")
            ->groupBy('status')
            ->pluck('count', 'status');

        $totalRevenue = FoodOrder::whereBetween('created_at', $range)
            ->where('status', 'delivered')
            ->sum('total_amount');

        $byType = ServiceRequest::whereBetween('created_at', $range)
            ->selectRaw("type, count(*) as count")
            ->groupBy('type')
            ->pluck('count', 'type');

        $byPriority = ServiceRequest::whereBetween('created_at', $range)
            ->selectRaw("LOWER(COALESCE(priority, 'medium')) as priority_key, count(*) as count")
            ->groupByRaw("LOWER(COALESCE(priority, 'medium'))")
            ->pluck('count', 'priority_key');

        $byDepartment = ServiceRequest::whereBetween('created_at', $range)
            ->selectRaw("department, count(*) as count")
            ->groupBy('department')
            ->pluck('count', 'department');

        $aiTriageCounts = \App\Models\AiTriageResult::whereBetween('created_at', $range)
            ->selectRaw("status, count(*) as count")
            ->groupBy('status')
            ->pluck('count', 'status');

        $aiTriageStats = [
            'total'    => $aiTriageCounts->sum(),
            'accepted' => $aiTriageCounts['accepted'] ?? 0,
            'modified' => ($aiTriageCounts['modified'] ?? 0) + ($aiTriageCounts['overridden'] ?? 0),
            'pending'  => $aiTriageCounts['pending'] ?? 0,
        ];

        $recentRequests = ServiceRequest::with(['room', 'assignedTo', 'aiTriage'])
            ->whereBetween('created_at', $range)
            ->latest()
            ->take(15)
            ->get();

        $reqRatings = ServiceRequest::whereBetween('created_at', $range)
            ->whereNotNull('guest_rating')
            ->pluck('guest_rating');
        $ordRatings = FoodOrder::whereBetween('created_at', $range)
            ->whereNotNull('guest_rating')
            ->pluck('guest_rating');
        $allRatings = $reqRatings->concat($ordRatings);

        $satisfactionStats = [
            'count'         => $allRatings->count(),
            'average'       => $allRatings->count() > 0 ? round($allRatings->avg(), 2) : null,
            'request_count' => $reqRatings->count(),
            'order_count'   => $ordRatings->count(),
        ];

        return view('admin.reports', compact(
            'requestStats',
            'orderStats',
            'totalRevenue',
            'byType',
            'byPriority',
            'byDepartment',
            'aiTriageStats',
            'satisfactionStats',
            'recentRequests',
            'dateFrom',
            'dateTo'
        ));
    }

    public function exportReports(Request $request)
    {
        $dateFrom = $request->input('date_from', now()->startOfMonth()->toDateString());
        $dateTo   = $request->input('date_to', now()->toDateString());
        $range    = [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'];

        $requests = ServiceRequest::with(['room', 'assignedTo', 'aiTriage'])
            ->whereBetween('created_at', $range)
            ->latest()
            ->get();

        $orders = FoodOrder::with(['room', 'processedBy'])
            ->whereBetween('created_at', $range)
            ->latest()
            ->get();

        $filename = "guestlink-report-{$dateFrom}-to-{$dateTo}.csv";

        return response()->streamDownload(function () use ($requests, $orders, $dateFrom, $dateTo) {
            $out = fopen('php://output', 'w');

            fputcsv($out, ['Madison Park Hotel - GuestLink Operational & AI Triage Report']);
            fputcsv($out, ['Date Range', "{$dateFrom} to {$dateTo}"]);
            fputcsv($out, ['Generated At', now()->toDateTimeString()]);
            fputcsv($out, []);

            fputcsv($out, ['--- SERVICE & MAINTENANCE REQUESTS ---']);
            fputcsv($out, [
                'Reference #',
                'Room',
                'Type',
                'Priority',
                'Department',
                'Assigned Personnel',
                'Status',
                'AI Triage Status',
                'Guest Name',
                'Description',
                'Guest Rating (1-5)',
                'Guest Feedback',
                'Created At',
            ]);

            $csvSafe = static function ($val) {
                if ($val === null || is_int($val) || is_float($val)) {
                    return $val;
                }
                $str = (string) $val;
                if ($str !== '' && in_array($str[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
                    return "'" . $str;
                }
                return $str;
            };

            foreach ($requests as $req) {
                fputcsv($out, [
                    $csvSafe($req->reference_number),
                    $csvSafe($req->room?->room_number ?? 'N/A'),
                    ucfirst($req->type),
                    ucfirst(strtolower($req->priority ?? 'medium')),
                    $csvSafe($req->department ?? 'Front Desk'),
                    $csvSafe($req->assignedTo?->name ?? 'Unassigned'),
                    $req->status,
                    $req->aiTriage?->status ?? 'N/A',
                    $csvSafe($req->guest_name ?? 'Guest'),
                    $csvSafe($req->description),
                    $req->guest_rating ?? 'Unrated',
                    $csvSafe($req->guest_feedback ?? ''),
                    $req->created_at?->toDateTimeString(),
                ]);
            }

            fputcsv($out, []);
            fputcsv($out, ['--- FOOD ORDERS ---']);
            fputcsv($out, [
                'Reference #',
                'Room',
                'Guest Name',
                'Status',
                'Total Amount (PHP)',
                'Processed By',
                'Guest Rating (1-5)',
                'Guest Feedback',
                'Created At',
            ]);

            foreach ($orders as $ord) {
                fputcsv($out, [
                    $csvSafe($ord->reference_number),
                    $csvSafe($ord->room?->room_number ?? 'N/A'),
                    $csvSafe($ord->guest_name ?? 'Guest'),
                    $ord->status,
                    number_format((float) $ord->total_amount, 2, '.', ''),
                    $csvSafe($ord->processedBy?->name ?? 'Unassigned'),
                    $ord->guest_rating ?? 'Unrated',
                    $csvSafe($ord->guest_feedback ?? ''),
                    $ord->created_at?->toDateTimeString(),
                ]);
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
