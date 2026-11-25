<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\FoodOrder;
use App\Models\ServiceRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        \App\Services\StatusProgressionService::runAutoProgression();

        $user = auth()->user();

        // Restaurant-only personnel go straight to Kitchen Orders Queue so they aren't distracted by Maintenance/FO tickets
        if ($user && $user->hasRole('restaurant') && !$user->hasAnyRole(['admin', 'owner', 'front_desk', 'maintenance', 'maintenance_supervisor', 'housekeeping'])) {
            return redirect()->route('food.orders');
        }

        $isMaintOnly = $user && $user->hasAnyRole(['maintenance', 'maintenance_supervisor'])
            && !$user->hasAnyRole(['admin', 'owner', 'front_desk', 'housekeeping']);
        $isHousekeepingOnly = $user && $user->hasRole('housekeeping')
            && !$user->hasAnyRole(['admin', 'owner', 'front_desk', 'maintenance', 'maintenance_supervisor', 'restaurant']);

        $filter = $request->query('filter', 'all');

        // Operations Dashboard: Strictly scoped to 24-hour operational cycle
        $since24h = now()->subHours(24);

        $maintScope = function ($q) use ($isMaintOnly, $isHousekeepingOnly, $user) {
            if ($isMaintOnly && $user) {
                $q->where(function ($sub) use ($user) {
                    $sub->where('type', 'maintenance')
                        ->orWhereRaw("LOWER(COALESCE(department, '')) IN ('maintenance', 'engineering')")
                        ->orWhere('assigned_to', $user->id);
                });
            } elseif ($isHousekeepingOnly && $user) {
                $q->where(function ($sub) use ($user) {
                    $sub->whereRaw("LOWER(COALESCE(department, '')) = 'housekeeping'")
                        ->orWhere('assigned_to', $user->id);
                });
            }
        };

        $stats = [
            'pending_requests'     => ServiceRequest::where('created_at', '>=', $since24h)->tap($maintScope)->where('status', 'pending')->count(),
            'in_progress_requests' => ServiceRequest::where('created_at', '>=', $since24h)->tap($maintScope)->whereIn('status', ['assigned', 'in_progress'])->count(),
            'resolved_today'       => ServiceRequest::where('updated_at', '>=', $since24h)->tap($maintScope)->where('status', 'resolved')->count(),
            'pending_orders'       => FoodOrder::where('created_at', '>=', $since24h)->whereIn('status', ['pending', 'confirmed', 'preparing'])->count(),
            'urgent_requests'      => ServiceRequest::where('created_at', '>=', $since24h)->tap($maintScope)->whereRaw('LOWER(priority) = ?', ['urgent'])->whereNotIn('status', ['resolved', 'closed'])->count(),
            'ai_triaged'           => \App\Models\AiTriageResult::where('created_at', '>=', $since24h)->count(),
            'total_requests_24h'   => ServiceRequest::where('created_at', '>=', $since24h)->tap($maintScope)->count(),
        ];

        // Active urgent requests queue (last 24 hours)
        $urgentRequests = ServiceRequest::with(['room', 'category', 'assignedTo', 'aiTriage'])
            ->where('created_at', '>=', $since24h)
            ->tap($maintScope)
            ->whereRaw('LOWER(priority) = ?', ['urgent'])
            ->whereNotIn('status', ['resolved', 'closed'])
            ->latest()
            ->get();

        // 24-Hour Live Service & Maintenance Stream with Filter Support
        $query = ServiceRequest::with(['room', 'category', 'assignedTo', 'aiTriage'])
            ->where('created_at', '>=', $since24h)
            ->tap($maintScope);

        // 1. KPI Filter tab
        switch ($filter) {
            case 'urgent':
                $query->whereRaw('LOWER(priority) = ?', ['urgent'])->whereNotIn('status', ['resolved', 'closed']);
                break;
            case 'pending':
                $query->where('status', 'pending');
                break;
            case 'in_progress':
                $query->whereIn('status', ['assigned', 'in_progress']);
                break;
            case 'resolved':
                $query->where('status', 'resolved');
                break;
            case 'ai':
                $query->has('aiTriage');
                break;
            default:
                break;
        }

        // 2. Toolbar filters (from Live Stream Search & Filters bar)
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('priority')) {
            $query->whereRaw('LOWER(priority) = ?', [strtolower((string) $request->priority)]);
        }
        if ($request->filled('department')) {
            $query->whereRaw('LOWER(department) = ?', [strtolower(trim((string) $request->department))]);
        }
        if ($request->filled('assigned')) {
            $assignedParam = trim((string) $request->assigned);
            if ($assignedParam === 'me') {
                $query->where('assigned_to', auth()->id());
            } elseif ($assignedParam === 'unassigned') {
                $query->whereNull('assigned_to');
            }
        }
        if ($request->filled('search')) {
            $searchTerm = trim($request->search);
            $query->where(function ($q) use ($searchTerm) {
                $q->where('description', 'like', "%{$searchTerm}%")
                  ->orWhere('reference_number', 'like', "%{$searchTerm}%")
                  ->orWhereHas('room', fn($r) => $r->where('room_number', 'like', "%{$searchTerm}%"));
            });
        }

        $recentRequests = $query->latest()->take(30)->get();

        // Active kitchen orders (last 24 hours)
        $recentOrders = FoodOrder::with(['room', 'orderItems'])
            ->where('created_at', '>=', $since24h)
            ->latest()
            ->take(10)
            ->get();

        return view('staff.dashboard', compact(
            'stats',
            'recentRequests',
            'recentOrders',
            'urgentRequests',
            'filter'
        ));
    }

    /**
     * Real-time polling endpoint for staff dashboard & notification alerts (strictly role-scoped).
     */
    public function pollNotifications(Request $request)
    {
        \App\Services\StatusProgressionService::runAutoProgression();

        $user = auth()->user();
        $hasRoles = $user && $user->roles()->exists();
        $isRestaurantOnly = $hasRoles
            && $user->hasRole('restaurant')
            && !$user->hasAnyRole(['admin', 'owner', 'front_desk', 'maintenance', 'maintenance_supervisor', 'housekeeping']);
        $isMaintOnly = $hasRoles
            && $user->hasAnyRole(['maintenance', 'maintenance_supervisor'])
            && !$user->hasAnyRole(['admin', 'owner', 'front_desk', 'housekeeping', 'restaurant']);
        $isHousekeepingOnly = $hasRoles
            && $user->hasRole('housekeeping')
            && !$user->hasAnyRole(['admin', 'owner', 'front_desk', 'maintenance', 'maintenance_supervisor', 'restaurant']);

        $canSeeRequests  = !$isRestaurantOnly;
        $canSeeOrders    = !$isMaintOnly && !$isHousekeepingOnly;
        $canSeeGuestChat = !$isRestaurantOnly && !$isMaintOnly && !$isHousekeepingOnly;

        $scopeRequestsForRole = function ($query) use ($isMaintOnly, $isHousekeepingOnly, $user) {
            if ($isMaintOnly && $user) {
                $query->where(function ($q) use ($user) {
                    $q->where('type', 'maintenance')
                      ->orWhereRaw("LOWER(COALESCE(department, '')) IN ('maintenance', 'engineering')")
                      ->orWhere('assigned_to', $user->id);
                });
            } elseif ($isHousekeepingOnly && $user) {
                $query->where(function ($q) use ($user) {
                    $q->whereRaw("LOWER(COALESCE(department, '')) = 'housekeeping'")
                      ->orWhere('assigned_to', $user->id);
                });
            }
            return $query;
        };

        $lastRequestId = (int)$request->query('last_request_id', 0);
        $lastOrderId   = (int)$request->query('last_order_id', 0);
        $lastCheck     = $request->query('last_check');
        $isInitial     = $request->boolean('is_initial', false);

        $latestReqId     = $canSeeRequests ? (ServiceRequest::max('id') ?? 0) : 0;
        $latestOrdId     = $canSeeOrders ? (FoodOrder::max('id') ?? 0) : 0;
        $latestMsgId     = $canSeeGuestChat ? (\App\Models\TicketMessage::max('id') ?? 0) : 0;
        $latestReqUpdate = $canSeeRequests ? (ServiceRequest::max('updated_at') ?? '') : '';
        $latestOrdUpdate = $canSeeOrders ? (FoodOrder::max('updated_at') ?? '') : '';

        $roleScopeKey = $isRestaurantOnly ? 'kds' : ($isMaintOnly ? 'maint' : ($isHousekeepingOnly ? 'hk' : 'fo'));
        $currentSignature = "{$roleScopeKey}_{$latestReqId}_{$latestOrdId}_{$latestMsgId}_{$latestReqUpdate}_{$latestOrdUpdate}";

        // Fast delta check: if nothing changed in database, return 200 with changed=false in ~1ms
        if (!$isInitial && $lastCheck && $lastCheck === $currentSignature && $lastRequestId >= $latestReqId && $lastOrderId >= $latestOrdId) {
            return response()->json([
                'success'   => true,
                'changed'   => false,
                'signature' => $currentSignature,
            ]);
        }

        // 1. New Service / Maintenance Requests (skipped completely for Kitchen/Restaurant)
        $newRequests = collect();
        if ($canSeeRequests && $lastRequestId > 0) {
            $newRequestsQuery = $scopeRequestsForRole(
                ServiceRequest::with(['room', 'category', 'aiTriage'])->where('id', '>', $lastRequestId)
            );

            $newRequests = $newRequestsQuery
                ->latest()
                ->get()
                ->map(function ($req) {
                    return [
                        'id'               => $req->id,
                        'reference_number' => $req->reference_number,
                        'room_number'      => $req->room?->room_number ?? '201',
                        'type'             => $req->type,
                        'type_label'       => $req->type === 'maintenance' ? '🔧 Maintenance' : '🛎️ Service',
                        'guest_name'       => $req->guest_name ?? 'In-House Guest',
                        'description'      => \Illuminate\Support\Str::limit($req->description, 75),
                        'priority'         => $req->priority ?? ($req->aiTriage?->suggested_priority ?? 'Medium'),
                        'priority_badge'   => $req->priority_badge ?? 'secondary',
                        'status'           => $req->status,
                        'status_label'     => $req->display_status,
                        'status_badge'     => $req->status_badge ?? 'warning',
                        'department'       => $req->department ?? ($req->aiTriage?->suggested_department ?? 'Front Desk'),
                        'has_photo'        => !empty($req->photo_path),
                        'photo_path'       => $req->photo_path ? asset($req->photo_path) : null,
                        'is_ai_triaged'    => !empty($req->aiTriage),
                        'created_at_human' => $req->created_at ? $req->created_at->diffForHumans(null, true) . ' ago' : 'Just now',
                        'updated_at_human' => $req->updated_at ? $req->updated_at->diffForHumans(null, true) . ' ago' : 'Just now',
                        'view_url'         => route('requests.show', $req),
                    ];
                });
        }

        // 2. New Food Orders (Kitchen, Front Desk, Admin, Owner only)
        $newOrders = collect();
        if ($canSeeOrders && $lastOrderId > 0) {
            $newOrders = FoodOrder::with(['room', 'orderItems.foodItem'])
                ->where('id', '>', $lastOrderId)
                ->latest()
                ->get()
                ->map(function ($ord) {
                    return [
                        'id'               => $ord->id,
                        'reference_number' => $ord->reference_number,
                        'room_number'      => $ord->room?->room_number ?? '201',
                        'guest_name'       => $ord->guest_name ?? 'Guest',
                        'status'           => $ord->status,
                        'status_badge'     => $ord->status_badge ?? 'warning',
                        'total_amount'     => number_format($ord->total_amount, 2),
                        'items_count'      => $ord->orderItems->sum('quantity'),
                        'created_at_human' => $ord->created_at ? $ord->created_at->diffForHumans(null, true) . ' ago' : 'Just now',
                        'view_url'         => route('food.orders.show', $ord),
                    ];
                });
        }

        // 3. Recently Updated Requests (skipped for Kitchen)
        $updatedRequests = collect();
        if ($canSeeRequests) {
            $updatedRequests = $scopeRequestsForRole(
                ServiceRequest::with(['room', 'category', 'aiTriage'])
                    ->where('updated_at', '>=', now()->subSeconds(45))
            )
                ->latest('updated_at')
                ->take(15)
                ->get()
                ->map(function ($req) {
                    return [
                        'id'               => $req->id,
                        'reference_number' => $req->reference_number,
                        'room_number'      => $req->room?->room_number ?? '201',
                        'type'             => $req->type,
                        'type_label'       => $req->type === 'maintenance' ? '🔧 Maintenance' : '🛎️ Service',
                        'guest_name'       => $req->guest_name ?? 'In-House Guest',
                        'description'      => \Illuminate\Support\Str::limit($req->description, 75),
                        'status'           => $req->status,
                        'status_label'     => $req->display_status,
                        'status_badge'     => $req->status_badge ?? 'warning',
                        'priority'         => $req->priority ?? ($req->aiTriage?->suggested_priority ?? 'Medium'),
                        'priority_badge'   => $req->priority_badge ?? 'secondary',
                        'department'       => $req->department ?? ($req->aiTriage?->suggested_department ?? 'Front Desk'),
                        'has_photo'        => !empty($req->photo_path),
                        'photo_path'       => $req->photo_path ? asset($req->photo_path) : null,
                        'is_ai_triaged'    => !empty($req->aiTriage),
                        'created_at_human' => $req->created_at ? $req->created_at->diffForHumans(null, true) . ' ago' : 'Just now',
                        'updated_at_human' => $req->updated_at ? $req->updated_at->diffForHumans(null, true) . ' ago' : 'Just now',
                        'view_url'         => route('requests.show', $req),
                    ];
                });
        }

        // 4. Recently Updated Food Orders (skipped for Maintenance/Housekeeping)
        $updatedOrders = collect();
        if ($canSeeOrders) {
            $updatedOrders = FoodOrder::with(['room'])
                ->where('updated_at', '>=', now()->subSeconds(45))
                ->latest('updated_at')
                ->take(10)
                ->get()
                ->map(function ($ord) {
                    return [
                        'id'               => $ord->id,
                        'reference_number' => $ord->reference_number,
                        'room_number'      => $ord->room?->room_number ?? '201',
                        'guest_name'       => $ord->guest_name ?? 'Guest',
                        'status'           => $ord->status,
                        'status_label'     => ucfirst($ord->status),
                        'status_badge'     => $ord->status_badge ?? 'primary',
                        'view_url'         => route('food.orders.show', $ord),
                    ];
                });
        }

        // 5. Recent Guest Chat Messages (Front Desk / Admin / Owner only)
        $recentGuestMessages = collect();
        if ($canSeeGuestChat) {
            $recentGuestMessages = \App\Models\TicketMessage::with(['serviceRequest.room'])
                ->where('sender_type', 'guest')
                ->where('created_at', '>=', now()->subSeconds(45))
                ->latest()
                ->take(5)
                ->get()
                ->map(function ($msg) {
                    return [
                        'id'               => $msg->id,
                        'request_id'       => $msg->service_request_id,
                        'reference_number' => $msg->serviceRequest?->reference_number,
                        'room_number'      => $msg->serviceRequest?->room?->room_number ?? '201',
                        'sender_name'      => $msg->sender_name,
                        'message'          => \Illuminate\Support\Str::limit($msg->message, 80),
                        'view_url'         => $msg->serviceRequest ? route('requests.show', $msg->serviceRequest) : '#',
                    ];
                });
        }

        // Current real-time system counts & 24h rolling stats — ONE aggregated query (SUM(CASE WHEN ...) per table).
        $since = now()->subHours(24)->toDateTimeString();
        $row = DB::selectOne("
            SELECT
                sr.pending_requests, sr.urgent_requests, sr.maint_active,
                sr.pending_requests_24h, sr.in_progress_requests_24h, sr.resolved_today_24h,
                sr.urgent_requests_24h, sr.total_requests_24h,
                fo.pending_orders, ai.ai_triaged_24h, tm.unread_guest_messages
            FROM
                (SELECT
                    COALESCE(SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END), 0) AS pending_requests,
                    COALESCE(SUM(CASE WHEN LOWER(priority) = 'urgent' AND status NOT IN ('resolved','closed') THEN 1 ELSE 0 END), 0) AS urgent_requests,
                    COALESCE(SUM(CASE WHEN type = 'maintenance' AND status NOT IN ('resolved','closed') THEN 1 ELSE 0 END), 0) AS maint_active,
                    COALESCE(SUM(CASE WHEN created_at >= ? AND status = 'pending' THEN 1 ELSE 0 END), 0) AS pending_requests_24h,
                    COALESCE(SUM(CASE WHEN created_at >= ? AND status IN ('assigned','in_progress') THEN 1 ELSE 0 END), 0) AS in_progress_requests_24h,
                    COALESCE(SUM(CASE WHEN updated_at >= ? AND status = 'resolved' THEN 1 ELSE 0 END), 0) AS resolved_today_24h,
                    COALESCE(SUM(CASE WHEN created_at >= ? AND LOWER(priority) = 'urgent' AND status NOT IN ('resolved','closed') THEN 1 ELSE 0 END), 0) AS urgent_requests_24h,
                    COALESCE(SUM(CASE WHEN created_at >= ? THEN 1 ELSE 0 END), 0) AS total_requests_24h
                 FROM service_requests) sr,
                (SELECT COALESCE(SUM(CASE WHEN status IN ('pending','confirmed','preparing') THEN 1 ELSE 0 END), 0) AS pending_orders
                 FROM food_orders) fo,
                (SELECT COALESCE(SUM(CASE WHEN created_at >= ? THEN 1 ELSE 0 END), 0) AS ai_triaged_24h
                 FROM ai_triage_results) ai,
                (SELECT COALESCE(SUM(CASE WHEN sender_type = 'guest' AND is_read = 0 THEN 1 ELSE 0 END), 0) AS unread_guest_messages
                 FROM ticket_messages) tm
        ", array_fill(0, 6, $since));

        $counts = [
            'pending_requests'          => (int) $row->pending_requests,
            'urgent_requests'           => (int) $row->urgent_requests,
            'pending_orders'            => (int) $row->pending_orders,
            'maint_active'              => (int) $row->maint_active,
            'unread_guest_messages'     => (int) $row->unread_guest_messages,
            // 24-hour dashboard live stats
            'pending_requests_24h'      => (int) $row->pending_requests_24h,
            'in_progress_requests_24h'  => (int) $row->in_progress_requests_24h,
            'resolved_today_24h'        => (int) $row->resolved_today_24h,
            'urgent_requests_24h'       => (int) $row->urgent_requests_24h,
            'ai_triaged_24h'            => (int) $row->ai_triaged_24h,
            'total_requests_24h'        => (int) $row->total_requests_24h,
        ];

        $latestReqId = ServiceRequest::max('id') ?? 0;
        $latestOrdId = FoodOrder::max('id') ?? 0;

        // Recent alerts for initial topbar notification population (strictly role-scoped!)
        $initialAlerts = collect();
        if ($isInitial) {
            if ($isRestaurantOnly) {
                $initialAlerts = FoodOrder::with(['room', 'orderItems'])
                    ->where('created_at', '>=', now()->subHours(12))
                    ->latest()
                    ->take(8)
                    ->get()
                    ->map(function ($ord) {
                        $modeLabel = method_exists($ord, 'isDineIn') && $ord->isDineIn() ? 'Times Sq. Dine-In' : 'Room Delivery';
                        return [
                            'id'             => 'ord-' . $ord->id,
                            'type'           => 'order',
                            'title'          => "Room " . ($ord->room?->room_number ?? '201') . " • 🍽️ {$modeLabel}",
                            'description'    => $ord->reference_number . ' · ' . $ord->orderItems->sum('quantity') . ' item(s) · ₱' . number_format((float) $ord->total_amount, 2),
                            'priority'       => ucfirst($ord->status),
                            'priority_badge' => $ord->status_badge ?? 'warning',
                            'time'           => $ord->created_at ? $ord->created_at->diffForHumans(null, true) . ' ago' : 'Recently',
                            'url'            => route('food.orders.show', $ord),
                            'unread'         => in_array($ord->status, ['pending', 'confirmed', 'preparing']),
                        ];
                    });
            } else {
                $initialAlerts = $scopeRequestsForRole(
                    ServiceRequest::with(['room', 'aiTriage'])
                        ->where('created_at', '>=', now()->subHours(12))
                )
                    ->latest()
                    ->take(8)
                    ->get()
                    ->map(function ($req) {
                        return [
                            'id'             => 'req-' . $req->id,
                            'type'           => $req->type,
                            'title'          => "Room " . ($req->room?->room_number ?? '201') . " • " . ($req->type === 'maintenance' ? '🔧 Maintenance' : '🛎️ Service'),
                            'description'    => \Illuminate\Support\Str::limit($req->description, 65),
                            'priority'       => $req->priority,
                            'priority_badge' => $req->priority_badge ?? 'secondary',
                            'time'           => $req->created_at ? $req->created_at->diffForHumans(null, true) . ' ago' : 'Recently',
                            'url'            => route('requests.show', $req),
                            'unread'         => in_array($req->status, ['pending', 'assigned']),
                        ];
                    });
            }
        }

        return response()->json([
            'success'                => true,
            'changed'                => true,
            'signature'              => $currentSignature,
            'has_new'                => $newRequests->isNotEmpty() || $newOrders->isNotEmpty() || $recentGuestMessages->isNotEmpty(),
            'latest_request_id'      => $latestReqId,
            'latest_order_id'        => $latestOrdId,
            'new_requests'           => $newRequests,
            'new_orders'             => $newOrders,
            'updated_requests'       => $updatedRequests,
            'updated_orders'         => $updatedOrders,
            'recent_guest_messages'  => $recentGuestMessages,
            'initial_alerts'         => $initialAlerts,
            'counts'                 => $counts,
        ]);
    }
}
