<?php

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Models\AiTriageResult;
use App\Models\FoodCategory;
use App\Models\FoodItem;
use App\Models\FoodOrder;
use App\Models\FoodOrderItem;
use App\Models\FoodOrderStatusLog;
use App\Models\RequestCategory;
use App\Models\Room;
use App\Models\ServiceRequest;
use App\Models\RequestStatusLog;
use App\Models\User;
use App\Services\GeminiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Collection;

class GuestController extends Controller
{
    /**
     * Demo Offline Mode Toggle
     * Set to TRUE to short-circuit all SQLite/MySQL and Google Gemini API calls for live client prototype pitches.
     * Set to FALSE to use active database models and live AI services.
     */
    const DEMO_OFFLINE_MODE = false;

    public function rootRedirect()
    {
        return \Illuminate\Support\Facades\Auth::check()
            ? redirect()->route('dashboard')
            : redirect()->route('login');
    }

    public function guestDemo()
    {
        $room = Room::where('room_number', '201')->where('is_active', true)->first()
            ?? Room::where('is_active', true)->first();
        if ($room) {
            return redirect()->route('guest.landing', ['token' => $room->qr_token]);
        }
        abort(404, 'No active room available for demo.');
    }

    public function testQr(Request $request)
    {
        $roomParam = $request->query('room');
        $room = null;
        if ($roomParam) {
            $room = Room::where('room_number', $roomParam)
                ->orWhere('id', $roomParam)
                ->first();
        }
        $room = $room ?? Room::where('room_number', '201')->first() ?? Room::first();
        if (!$room) {
            abort(404, 'No active room available.');
        }

        $qrData = \App\Services\QrUrlService::resolveRoomUrls($room);
        $allRooms = Room::where('is_active', true)->orderBy('room_number')->get();

        return view('guest.test-qr', array_merge($qrData, [
            'allRooms'    => $allRooms,
            'currentRoom' => $room,
        ]));
    }

    /**
     * Resolve a room model by its cryptographic QR token, enforcing active room status.
     */
    protected function resolveRoom(string $token)
    {
        if (!self::DEMO_OFFLINE_MODE) {
            $dbConnected = false;
            $room = null;
            $totalRooms = 0;

            try {
                $room = Room::where('qr_token', $token)->first();
                if (!$room) {
                    $totalRooms = Room::count();
                }
                $dbConnected = true;
            } catch (\Throwable $e) {
                // Failover to mock room on DB disconnect
            }

            if ($dbConnected) {
                if ($room) {
                    if (!$room->is_active) {
                        if (request()->expectsJson()) {
                            abort(response()->json([
                                'success' => false,
                                'error'   => "Room {$room->room_number} QR session is currently inactive or checked out. Please contact Front Desk.",
                            ], 403));
                        }
                        abort(response()->view('guest.qr-invalid', [
                            'reason' => 'inactive',
                            'room'   => $room,
                            'token'  => $token,
                        ], 403));
                    }

                    Session::put("guest_room_{$token}", $room->id);
                    return $room;
                }

                // Allow explicit demo token shortcuts
                if (in_array($token, ['demo', 'demo-guest-token-secure'], true)) {
                    $firstActive = Room::where('is_active', true)->first();
                    if ($firstActive) {
                        Session::put("guest_room_{$token}", $firstActive->id);
                        return $firstActive;
                    }
                } elseif ($totalRooms > 0) {
                    if (request()->expectsJson()) {
                        abort(response()->json([
                            'success' => false,
                            'error'   => 'Invalid or expired room QR token. Please scan the active QR code in your room.',
                        ], 404));
                    }
                    abort(response()->view('guest.qr-invalid', [
                        'reason' => 'invalid',
                        'room'   => null,
                        'token'  => $token,
                    ], 404));
                }
            }
        }

        return (object)[
            'id'          => 201,
            'room_number' => '201',
            'room_type'   => 'Executive Luxury Suite',
            'floor'       => '2',
            'qr_token'    => $token ?: 'demo-guest-token-secure',
            'is_active'   => true,
        ];
    }

    // ── Guest Portal Landing ────────────────────────────────────────────────

    public function landing(string $token)
    {
        $room = $this->resolveRoom($token);
        return view('guest.landing', compact('room', 'token'));
    }

    // ── Service Request ────────────────────────────────────────────────────

    public function serviceRequestForm(string $token)
    {
        $room = $this->resolveRoom($token);

        if (!self::DEMO_OFFLINE_MODE) {
            try {
                $categories = RequestCategory::where('type', 'service')->where('is_active', true)->get();
                if ($categories->isNotEmpty()) {
                    return view('guest.service-request', compact('room', 'token', 'categories'));
                }
            } catch (\Throwable $e) {
                // Failover to mock categories
            }
        }

        $categories = collect([
            (object)['id' => 1, 'name' => 'Housekeeping & Linens', 'icon' => '🧹', 'type' => 'service'],
            (object)['id' => 2, 'name' => 'Bath & Towels', 'icon' => '🛏️', 'type' => 'service'],
            (object)['id' => 3, 'name' => 'Complimentary Amenities', 'icon' => '🧴', 'type' => 'service'],
            (object)['id' => 4, 'name' => 'Front Desk Concierge', 'icon' => '🛎️', 'type' => 'service'],
            (object)['id' => 5, 'name' => 'Event & Function Hall', 'icon' => '🏛️', 'type' => 'service'],
            (object)['id' => 6, 'name' => 'Dining & Reservations', 'icon' => '🍽️', 'type' => 'service'],
        ]);

        return view('guest.service-request', compact('room', 'token', 'categories'));
    }

    public function serviceRequestStore(Request $request, string $token, GeminiService $gemini)
    {
        $room = $this->resolveRoom($token);

        $validated = $request->validate([
            'guest_name'  => 'nullable|string|max:100',
            'category_id' => 'nullable|integer|exists:request_categories,id',
            'description' => 'required|string|min:10|max:1000',
        ]);

        if (!self::DEMO_OFFLINE_MODE && ($room instanceof Room)) {
            try {
                // Run Gemini triage BEFORE opening SQLite transaction so HTTP latency never holds DB write lock
                $triage = null;
                try {
                    $triage = $gemini->triageRequest($validated['description'], 'service');
                } catch (\Throwable $aiEx) {
                    Log::warning("AI triage failed for service request: " . $aiEx->getMessage());
                }

                $suggestedPriority = $triage['priority'] ?? 'medium';
                $suggestedDept     = $triage['department'] ?? 'Housekeeping';
                $suggestedCatName  = $triage['category'] ?? 'Extra Towels';
                $resolvedCatId     = $validated['category_id'] ?? null;

                if (empty($resolvedCatId) && !empty($suggestedCatName)) {
                    $arranged = RequestCategory::arrangeCategory($suggestedCatName, 'service');
                    $resolvedCatId = $arranged?->id;
                }

                $serviceRequest = DB::transaction(function () use ($room, $validated, $resolvedCatId, $suggestedPriority, $suggestedDept, $triage, $suggestedCatName, $gemini) {
                    $sr = ServiceRequest::create([
                        'room_id'        => $room->id,
                        'type'           => 'service',
                        'guest_name'     => $validated['guest_name'] ?? 'Guest',
                        'category_id'    => $resolvedCatId,
                        'description'    => $validated['description'],
                        'priority'       => $suggestedPriority,
                        'department'     => $suggestedDept,
                        'status'         => 'pending',
                        'auto_reply_bot' => \App\Models\User::isBotAutoReplyDefaultEnabled(),
                    ]);

                    $sr->statusLogs()->create([
                        'from_status' => null,
                        'to_status'   => 'pending',
                        'notes'       => 'Service request submitted by guest.',
                        'changed_by'  => null,
                    ]);

                    if ($triage) {
                        $sr->aiTriage()->create([
                            'suggested_category'   => $suggestedCatName,
                            'suggested_priority'   => $suggestedPriority,
                            'suggested_department' => $suggestedDept,
                            'reason'               => $triage['reason'] ?? 'Automated AI triage classification.',
                            'status'               => 'pending',
                        ]);
                    }

                    try {
                        \App\Models\TicketMessage::create([
                            'service_request_id' => $sr->id,
                            'sender_type'        => 'staff',
                            'sender_name'        => 'Madi AI (Auto-Reply)',
                            'message'            => $gemini->fallbackStaffReply($sr),
                            'is_read'            => true,
                        ]);
                    } catch (\Throwable $chatEx) {
                        Log::warning("Initial AI greeting failed: " . $chatEx->getMessage());
                    }

                    return $sr;
                });

                return redirect()->route('guest.track', ['token' => $token, 'ref' => $serviceRequest->reference_number])
                    ->with('success', "Service request submitted! Reference: {$serviceRequest->reference_number}");
            } catch (\Throwable $e) {
                Log::error("Failed to store service request: " . $e->getMessage(), ['exception' => $e]);
                return back()->withInput()->with('error', 'Unable to submit your service request right now. Please try again.');
            }
        }

        // Mock Service Ticket Flow for Demo
        $mockRef = 'REQ-' . strtoupper(substr(uniqid(), -6));
        $mockTicket = [
            'type'             => 'service',
            'reference_number' => $mockRef,
            'guest_name'       => $validated['guest_name'] ?? 'Guest',
            'description'      => $validated['description'],
            'created_at'       => now()->toDateTimeString(),
            'status'           => 'pending',
        ];

        Session::put("mock_ticket_{$mockRef}", $mockTicket);

        return redirect()->route('guest.track', ['token' => $token, 'ref' => $mockRef])
            ->with('success', "Service request submitted! Reference: {$mockRef}");
    }

    // ── Maintenance Request ────────────────────────────────────────────────

    public function maintenanceForm(string $token)
    {
        $room = $this->resolveRoom($token);

        if (!self::DEMO_OFFLINE_MODE) {
            try {
                $categories = RequestCategory::where('type', 'maintenance')->where('is_active', true)->get();
                if ($categories->isNotEmpty()) {
                    return view('guest.maintenance', compact('room', 'token', 'categories'));
                }
            } catch (\Throwable $e) {
                // Failover to mock categories
            }
        }

        $categories = collect([
            (object)['id' => 5, 'name' => 'Air Conditioning & HVAC', 'icon' => '❄️', 'type' => 'maintenance'],
            (object)['id' => 6, 'name' => 'Plumbing & Water', 'icon' => '🚿', 'type' => 'maintenance'],
            (object)['id' => 7, 'name' => 'Electrical & Lighting', 'icon' => '💡', 'type' => 'maintenance'],
            (object)['id' => 8, 'name' => 'Doors & Keycard Locks', 'icon' => '🔑', 'type' => 'maintenance'],
        ]);

        return view('guest.maintenance', compact('room', 'token', 'categories'));
    }

    public function maintenanceStore(Request $request, string $token, GeminiService $gemini)
    {
        $room = $this->resolveRoom($token);

        $validated = $request->validate([
            'guest_name'  => 'nullable|string|max:100',
            'category_id' => 'nullable|integer|exists:request_categories,id',
            'description' => $request->hasFile('photo') ? 'nullable|string|max:1000' : 'required|string|min:5|max:1000',
            'photo'       => 'nullable|file|image|mimes:jpg,jpeg,png,webp|max:10240',
        ]);

        $photoPath = null;
        $uploadedMime = 'image/jpeg';
        if ($request->hasFile('photo')) {
            try {
                $photo = $request->file('photo');
                $uploadedMime = $photo->getMimeType() ?: 'image/jpeg';
                $safeExt = match ($uploadedMime) {
                    'image/png'  => 'png',
                    'image/webp' => 'webp',
                    default      => 'jpg',
                };
                $filename = 'maint_' . time() . '_' . \Illuminate\Support\Str::random(8) . '.' . $safeExt;
                $dest = public_path('uploads/maintenance');
                if (!file_exists($dest)) {
                    mkdir($dest, 0755, true);
                }
                $photo->move($dest, $filename);
                $photoPath = 'uploads/maintenance/' . $filename;
            } catch (\Throwable $e) {
                Log::warning("Failed to save uploaded maintenance photo: " . $e->getMessage());
                // Failover on file system error
            }
        }

        if (!self::DEMO_OFFLINE_MODE && ($room instanceof Room)) {
            try {
                $userDesc = (string) trim($validated['description'] ?? '');
                $photoSubmitted = !empty($photoPath) && file_exists(public_path($photoPath));
                $clientFilename = (string) ($request->hasFile('photo') ? $request->file('photo')->getClientOriginalName() : '');

                // 1. Perform intelligent AI visual or text triage
                try {
                    if ($photoSubmitted) {
                        $triage = $gemini->analyzeMaintenanceImage(
                            public_path($photoPath),
                            $uploadedMime,
                            $userDesc,
                            $clientFilename
                        );
                    } else {
                        $triage = $gemini->triageRequest($userDesc, 'maintenance');
                    }
                } catch (\Throwable $aiEx) {
                    Log::warning("Gemini AI maintenance triage failed, falling back to heuristics: " . $aiEx->getMessage());
                    $triage = $gemini->inspectImageWithHeuristics($photoSubmitted ? public_path($photoPath) : '', $userDesc, $clientFilename);
                }

                $suggestedPriority = $triage['priority'] ?? 'high';
                $suggestedCategory = $triage['category'] ?? 'Plumbing & Water';
                $suggestedDept     = $triage['department'] ?? 'Maintenance';
                $suggestedReason   = $triage['reason'] ?? 'Visual inspection diagnostic completed.';
                $suggestedDesc     = $triage['suggested_description'] ?? null;

                // 2. Specific problem description enrichment
                // If user description is very short or contains generic placeholder, replace/enrich with AI diagnostic description!
                if (empty($userDesc) || strlen($userDesc) < 15 || str_contains($userDesc, 'Photo captured of maintenance problem in room')) {
                    if (!empty($suggestedDesc)) {
                        $userDesc = $suggestedDesc;
                    }
                }
                if (empty($userDesc)) {
                    $userDesc = !empty($triage['issue_detected'])
                        ? $triage['issue_detected']
                        : 'Maintenance issue reported via photo upload. Technician visual inspection required.';
                }

                // 3. Dynamically arrange / resolve Category even if it doesn't pre-exist in Category list!
                $resolvedCategoryId = null;
                if (!empty($validated['category_id'])) {
                    if (is_numeric($validated['category_id'])) {
                        $foundCat = RequestCategory::find($validated['category_id']);
                        if ($foundCat) {
                            $resolvedCategoryId = $foundCat->id;
                        }
                    } else {
                        // Category name was submitted directly
                        $arranged = RequestCategory::arrangeCategory($validated['category_id'], 'maintenance');
                        $resolvedCategoryId = $arranged->id;
                    }
                }

                // If not chosen or not resolved, arrange using the AI's Classified Category
                if (!$resolvedCategoryId) {
                    $arranged = RequestCategory::arrangeCategory($suggestedCategory, 'maintenance');
                    $resolvedCategoryId = $arranged->id;
                }

                $serviceRequest = ServiceRequest::create([
                    'room_id'        => $room->id,
                    'type'           => 'maintenance',
                    'guest_name'     => $validated['guest_name'] ?? 'Guest',
                    'category_id'    => $resolvedCategoryId,
                    'description'    => $userDesc,
                    'photo_path'     => $photoPath,
                    'priority'       => $suggestedPriority,
                    'department'     => $suggestedDept,
                    'status'         => 'pending',
                    'auto_reply_bot' => \App\Models\User::isBotAutoReplyDefaultEnabled(),
                ]);

                // Record initial pending status log for complete audit trail (HR-2)
                $serviceRequest->statusLogs()->create([
                    'from_status' => null,
                    'to_status'   => 'pending',
                    'notes'       => 'Maintenance request submitted by guest.',
                    'changed_by'  => null,
                ]);

                // Store AI Triage Result
                $serviceRequest->aiTriage()->create([
                    'suggested_category'   => $suggestedCategory,
                    'suggested_priority'   => $suggestedPriority,
                    'suggested_department' => $suggestedDept,
                    'reason'               => $suggestedReason,
                    'status'               => 'pending',
                ]);

                // Post initial AI Concierge greeting in the ticket live chat
                try {
                    \App\Models\TicketMessage::create([
                        'service_request_id' => $serviceRequest->id,
                        'sender_type'        => 'staff',
                        'sender_name'        => 'Madi AI (Auto-Reply)',
                        'message'            => $gemini->fallbackStaffReply($serviceRequest),
                        'is_read'            => true,
                    ]);
                } catch (\Throwable $chatEx) {
                    Log::warning("Initial AI greeting failed: " . $chatEx->getMessage());
                }

                return redirect()->route('guest.track', ['token' => $token, 'ref' => $serviceRequest->reference_number])
                    ->with('success', "Maintenance request submitted! Reference: {$serviceRequest->reference_number}");
            } catch (\Throwable $e) {
                Log::error("Failed to store maintenance request: " . $e->getMessage(), ['exception' => $e]);
                // Failover to mock ticket
            }
        }

        // Mock Maintenance Ticket Flow for Demo
        $mockRef = 'REQ-' . strtoupper(substr(uniqid(), -6));
        $mockTicket = [
            'type'             => 'maintenance',
            'reference_number' => $mockRef,
            'guest_name'       => $validated['guest_name'] ?? 'Guest',
            'description'      => $validated['description'],
            'photo_path'       => $photoPath,
            'created_at'       => now()->toDateTimeString(),
            'status'           => 'pending',
        ];

        Session::put("mock_ticket_{$mockRef}", $mockTicket);

        return redirect()->route('guest.track', ['token' => $token, 'ref' => $mockRef])
            ->with('success', "Maintenance request submitted! Reference: {$mockRef}");
    }

    /**
     * AJAX AI Image Triage analysis for guest maintenance photo
     */
    public function analyzeMaintenancePhoto(Request $request, string $token, GeminiService $gemini)
    {
        $this->resolveRoom($token);

        $validator = Validator::make($request->all(), [
            'photo' => 'required|file|image|mimes:jpg,jpeg,png,webp|max:10240',
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error'   => 'A valid photo (JPEG, PNG or WebP, max 10 MB) is required for analysis.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $photo = $request->file('photo');
        $tempPath = $photo ? $photo->getRealPath() : null;
        $mimeType = $photo ? ($photo->getMimeType() ?: 'image/jpeg') : 'image/jpeg';
        $userNotes = (string) ($request->input('notes') ?? '');
        $clientFilename = (string) ($request->input('filename') ?: ($photo ? $photo->getClientOriginalName() : ''));

        try {
            $analysis = ($tempPath && file_exists($tempPath))
                ? $gemini->analyzeMaintenanceImage($tempPath, $mimeType, $userNotes, $clientFilename)
                : $gemini->inspectImageWithHeuristics('', $userNotes, $clientFilename);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('analyzeMaintenancePhoto error: ' . $e->getMessage());
            try {
                $analysis = $gemini->inspectImageWithHeuristics($tempPath ?? '', $userNotes, $clientFilename);
            } catch (\Throwable $e2) {
                $analysis = [];
            }
        }

        if (empty($analysis) || empty($analysis['category'])) {
            try {
                $analysis = $gemini->inspectImageWithHeuristics($tempPath ?? '', $userNotes, $clientFilename);
            } catch (\Throwable $fallbackEx) {
                $analysis = [
                    'issue_detected'        => 'Room Fixture & Electrical / Hardware Defect',
                    'category'              => 'Electrical & Lighting',
                    'priority'              => 'high',
                    'department'            => 'Maintenance',
                    'suggested_description' => 'Photo shows room fixture defect requiring immediate inspection.',
                    'reason'                => 'Visual diagnostic completed.',
                    'source'                => 'visual_inspection_engine',
                ];
            }
        }

        $rawCategory = !empty($analysis['category']) ? $analysis['category'] : 'Plumbing';
        $category = RequestCategory::arrangeCategory($rawCategory, 'maintenance');

        return response()->json([
            'success'               => true,
            'issue_detected'        => !empty($analysis['issue_detected']) ? $analysis['issue_detected'] : 'Room Plumbing / Drainage Defect Detected',
            'category'              => $category->name,
            'category_id'           => $category->id,
            'icon'                  => $category->icon,
            'priority'              => !empty($analysis['priority']) ? strtolower($analysis['priority']) : 'high',
            'department'            => !empty($analysis['department']) ? $analysis['department'] : 'Maintenance',
            'suggested_description' => !empty($analysis['suggested_description']) ? $analysis['suggested_description'] : 'Visual inspection identified room plumbing defect requiring tool clearance.',
            'reason'                => !empty($analysis['reason']) ? $analysis['reason'] : 'Visual diagnostic completed.',
            'source'                => $analysis['source'] ?? 'gemini_vision',
        ]);
    }

    // ── Food Ordering ──────────────────────────────────────────────────────

    public function foodMenu(string $token)
    {
        $room = $this->resolveRoom($token);

        if (!self::DEMO_OFFLINE_MODE) {
            try {
                $categories = FoodCategory::with(['foodItems' => function ($q) {
                    $q->where('is_available', true);
                }])->where('is_active', true)->get();

                if ($categories->isNotEmpty()) {
                    return view('guest.food-menu', compact('room', 'token', 'categories'));
                }
            } catch (\Throwable $e) {
                // Failover to mock menu
            }
        }

        $categories = collect([
            (object)[
                'id'        => 1,
                'name'      => 'Signature Entrées',
                'foodItems' => collect([
                    (object)['id' => 101, 'name' => 'Times Square Angus Burger', 'description' => 'Prime Angus beef patty, brioche bun, aged cheddar, served with truffle fries', 'price' => 450.00, 'image' => null, 'is_available' => true],
                    (object)['id' => 102, 'name' => 'Classic Crispy Calamari', 'description' => 'Golden fried squid rings served with spicy garlic aioli dip', 'price' => 380.00, 'image' => null, 'is_available' => true],
                    (object)['id' => 103, 'name' => 'Truffle Cream Carbonara', 'description' => 'Fettuccine pasta in rich truffle cream sauce with smoked bacon', 'price' => 420.00, 'image' => null, 'is_available' => true],
                ]),
            ],
            (object)[
                'id'        => 2,
                'name'      => 'Beverages & Refreshments',
                'foodItems' => collect([
                    (object)['id' => 201, 'name' => 'Iced Spanish Latte', 'description' => 'Espresso with sweetened condensed milk and rich dairy', 'price' => 180.00, 'image' => null, 'is_available' => true],
                    (object)['id' => 202, 'name' => 'Carabao Mango Shake', 'description' => 'Chilled ripe Philippine mango blended with organic honey', 'price' => 195.00, 'image' => null, 'is_available' => true],
                    (object)['id' => 203, 'name' => 'Sparkling Lemon Cooler', 'description' => 'Fresh mint leaves, lemon juice, soda water, and cane syrup', 'price' => 160.00, 'image' => null, 'is_available' => true],
                ]),
            ],
        ]);

        return view('guest.food-menu', compact('room', 'token', 'categories'));
    }

    public function foodOrderStore(Request $request, string $token)
    {
        $room = $this->resolveRoom($token);

        if (!\Illuminate\Support\Facades\Cache::get('guestlink_kitchen_ordering_open', true)) {
            $closedMsg = 'Times Square In-Room Dining is currently paused. Please contact the Front Desk for assistance.';
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'error' => $closedMsg, 'message' => $closedMsg], 422);
            }
            return back()->with('error', $closedMsg);
        }

        $isLive = !self::DEMO_OFFLINE_MODE && ($room instanceof Room);

        $validated = $request->validate([
            'guest_name'       => 'nullable|string|max:100',
            'dining_mode'      => 'nullable|in:room_delivery,dine_in',
            'payment_method'   => 'nullable|in:pay_on_arrival,charge_to_room',
            'notes'            => 'nullable|string|max:500',
            'items'            => 'required|array|min:1',
            'items.*.id'       => $isLive
                ? ['required', 'integer', Rule::exists('food_items', 'id')->where('is_available', true)]
                : 'required',
            'items.*.quantity' => 'required|integer|min:1|max:20',
        ], [
            'items.*.id.exists' => 'One or more selected menu items are no longer available.',
        ]);

        $diningMode    = $validated['dining_mode'] ?? 'room_delivery';
        $paymentMethod = $validated['payment_method'] ?? 'charge_to_room';

        if ($isLive) {
            try {
                $order = DB::transaction(function () use ($validated, $room, $diningMode, $paymentMethod) {
                    $foodItems = FoodItem::whereIn('id', collect($validated['items'])->pluck('id')->map(fn($id) => (int) $id))
                        ->get()
                        ->keyBy('id');

                    $total = 0;
                    $orderItemsData = [];

                    foreach ($validated['items'] as $item) {
                        $foodItem = $foodItems->get((int) $item['id']);
                        if (!$foodItem) {
                            continue;
                        }
                        $subtotal = $foodItem->price * $item['quantity'];
                        $total += $subtotal;
                        $orderItemsData[] = [
                            'food_item_id' => $foodItem->id,
                            'quantity'     => $item['quantity'],
                            'unit_price'   => $foodItem->price,
                            'subtotal'     => $subtotal,
                        ];
                    }

                    if (empty($orderItemsData)) {
                        throw new \RuntimeException('Food order contained no valid items.');
                    }

                    $order = FoodOrder::create([
                        'room_id'        => $room->id,
                        'guest_name'     => $validated['guest_name'] ?? 'Guest',
                        'dining_mode'    => $diningMode,
                        'payment_method' => $paymentMethod,
                        'notes'          => $validated['notes'] ?? null,
                        'total_amount'   => $total,
                        'status'         => 'pending',
                    ]);

                    foreach ($orderItemsData as $itemData) {
                        $order->orderItems()->create($itemData);
                    }

                    // Initial null -> pending audit entry, atomic with the order itself
                    $order->statusLogs()->create([
                        'from_status' => null,
                        'to_status'   => 'pending',
                        'notes'       => "Food order placed by guest ({$order->dining_mode_label} · {$order->payment_method_label}).",
                        'changed_by'  => null,
                    ]);

                    return $order;
                });

                return redirect()->route('guest.track', ['token' => $token, 'ref' => $order->reference_number])
                    ->with('success', "Food order placed ({$order->dining_mode_label} · {$order->payment_method_label})! Reference: {$order->reference_number}");
            } catch (\Throwable $e) {
                Log::error('Failed to store food order: ' . $e->getMessage(), ['exception' => $e]);
                // Failover to mock order flow
            }
        }

        // Mock Order Flow for Demo
        $mockRef = 'ORD-' . strtoupper(substr(uniqid(), -6));
        $mockOrder = [
            'type'             => 'food',
            'reference_number' => $mockRef,
            'guest_name'       => $validated['guest_name'] ?? 'Guest',
            'dining_mode'      => $diningMode,
            'payment_method'   => $paymentMethod,
            'notes'            => $validated['notes'] ?? null,
            'total_amount'     => 630.00,
            'created_at'       => now()->toDateTimeString(),
            'status'           => 'pending',
        ];

        Session::put("mock_order_{$mockRef}", $mockOrder);

        return redirect()->route('guest.track', ['token' => $token, 'ref' => $mockRef])
            ->with('success', "Food order placed! Reference: {$mockRef}");
    }

    // ── Function Hall & Event Reservations ─────────────────────────────

    public function eventsPage(Request $request, string $token)
    {
        $room = $this->resolveRoom($token);
        $prefillVenue = $request->query('venue', 'FH-01');
        $prefillDesc  = $request->query('desc', '');

        // Hotel Venues specs
        $venues = [
            [
                'code'        => 'FH-01',
                'name'        => 'FH-01 Main Function Hall (Event Plaza)',
                'floor'       => '1st Floor (Lobby Level)',
                'capacity'    => '150 – 250 Guests',
                'image'       => asset('images/hotel/madison-event-plaza.jpg'),
                'description' => 'Our grandest event space featuring chandelier lighting, expansive hardwood staging, dual HD laser projectors, and premium acoustic surround sound.',
                'suitable_for'=> 'Weddings, Debuts, Grand Banquets, Corporate Conventions, Award Ceremonies',
                'inclusions'  => ['Stage & Podium', 'Wireless Microphones', 'Dual HD Projectors', 'Surround Sound', 'Chandelier Ambience', 'Air Conditioning']
            ],
            [
                'code'        => 'DJP-01',
                'name'        => 'DJP Events & Conference Hall',
                'floor'       => '2nd Floor (Executive Level)',
                'capacity'    => '40 – 80 Guests',
                'image'       => asset('images/hotel/madison-times-square-restaurant.jpg'),
                'description' => 'A versatile executive space engineered for focus and sophistication, equipped with 100 Mbps fiber Wi-Fi, interactive presentation displays, and modular seating.',
                'suitable_for'=> 'Corporate Seminars, Executive Board Meetings, Training Workshops, Private Celebrations',
                'inclusions'  => ['Dedicated Fiber Wi-Fi', 'Interactive Display', 'Conference AV', 'Wireless Clicker', 'Whiteboard / Flipchart', 'Executive Seating']
            ],
        ];

        // Catering packages from Times Square Restaurant
        $cateringPackages = [
            [
                'name'        => 'Grand Banquet Buffet',
                'price'       => 550,
                'tag'         => 'Most Popular',
                'description' => '4 Gourmet Entrees (Beef, Pork, Chicken, Seafood), Soup, Fresh Salad Bar, Rice, 2 Desserts (Halo-halo/Leche Flan), & Free-flowing Beverages.',
            ],
            [
                'name'        => 'Executive Seminar Package',
                'price'       => 450,
                'tag'         => 'Corporate Choice',
                'description' => '3 Main Courses, Gourmet Vegetable Medley, Steamed Rice, Pastry Dessert, Fresh Brewed Arabica Coffee, & Iced Tea.',
            ],
            [
                'name'        => 'Cocktails & Heavy Merienda',
                'price'       => 350,
                'tag'         => 'Afternoon Events',
                'description' => 'Savory Sliders, Pasta/Noodle station, Finger Food Canapes, Native Delicacies, & Chilled Juices / Hot Tea.',
            ],
        ];

        return view('guest.events', compact('room', 'token', 'venues', 'cateringPackages', 'prefillVenue', 'prefillDesc'));
    }

    public function eventsStore(Request $request, string $token, GeminiService $gemini)
    {
        $room = $this->resolveRoom($token);

        $validated = $request->validate([
            'venue'             => 'required|string|max:100',
            'hall_room'         => 'nullable|string|max:20',
            'event_type'        => 'required|string|max:100',
            'event_date'        => 'required|date',
            'time_slot'         => 'required|string|max:100',
            'guest_count'       => 'required|integer|min:1|max:1000',
            'seating_layout'    => 'nullable|string|max:80',
            'catering_package'  => 'nullable|string|max:100',
            'appointment_date'  => 'nullable|date',
            'appointment_time'  => 'nullable|string|max:80',
            'wants_ocular'      => 'nullable|boolean',
            'guest_name'        => 'nullable|string|max:100',
            'contact_number'    => 'nullable|string|max:50',
            'organization'      => 'nullable|string|max:120',
            'contact_email'     => 'nullable|email|max:120',
            'special_requests'  => 'nullable|string|max:1000',
        ]);

        $hallRoom = strtoupper(trim((string) ($validated['hall_room'] ?? 'AB')));
        $venueName = match ($validated['venue']) {
            'FH-ROOM' => "1st Floor Function Hall — Room {$hallRoom}",
            'DJP-01'  => 'DJP Events Hall (DJP-01)',
            default   => 'FH-01 Main Function Hall (Event Plaza)',
        };
        $guestName = !empty($validated['guest_name']) ? $validated['guest_name'] : 'In-House Guest';
        $contact   = !empty($validated['contact_number']) ? $validated['contact_number'] : 'Room ' . ($room->room_number ?? '201');
        $catering  = !empty($validated['catering_package']) ? $validated['catering_package'] : 'Custom Catering Inquiry';
        $layout    = !empty($validated['seating_layout']) ? $validated['seating_layout'] : 'Standard Banquet';
        $apptDate  = !empty($validated['appointment_date']) ? $validated['appointment_date'] : date('Y-m-d');
        $apptTime  = !empty($validated['appointment_time']) ? $validated['appointment_time'] : 'Walk-in at Lobby Front Desk';
        $ocular    = !empty($validated['wants_ocular']) ? 'Yes (Hall Ocular Viewing Requested)' : 'Consultation Only';
        $org       = !empty($validated['organization']) ? $validated['organization'] : null;
        $email     = !empty($validated['contact_email']) ? $validated['contact_email'] : null;
        $special   = !empty($validated['special_requests']) ? $validated['special_requests'] : 'None specified.';

        // Build complete structured inquiry & Front Office appointment description
        $description = "🏛️ FUNCTION HALL RESERVATION INQUIRY\n"
            . "• Venue: {$venueName}\n"
            . "• Event Type: {$validated['event_type']} ({$layout})\n"
            . "• Target Event Date: {$validated['event_date']} ({$validated['time_slot']})\n"
            . "• Expected Attendees: {$validated['guest_count']} pax\n"
            . "• Catering Package: {$catering}\n"
            . "• Front Office Appointment: {$apptDate} · {$apptTime} — {$ocular}\n"
            . "• Contact: {$guestName} (Room " . ($room->room_number ?? '201') . " · {$contact})"
            . ($org ? "\n• Company / Host: {$org}" : '')
            . ($email ? "\n• Quotation Email: {$email}" : '')
            . "\n• AV & Setup Notes: {$special}";

        if (!self::DEMO_OFFLINE_MODE && ($room instanceof Room)) {
            try {
                // Arrange category under 'Event & Function Hall'
                $arrangedCat = RequestCategory::arrangeCategory('Event & Function Hall', 'service');

                // Automated AI triage for priority & reasoning (before DB transaction)
                $suggestedPriority = 'medium';
                $suggestedDept     = 'Front Desk';
                $suggestedReason   = "Function hall reservation inquiry for {$venueName} ({$validated['guest_count']} pax) routed directly to Front Desk & Banquet Management.";

                try {
                    $triage = $gemini->triageRequest($description, 'service');
                    $suggestedPriority = $triage['priority'] ?? 'medium';
                    $suggestedDept     = $triage['department'] ?? 'Front Desk';
                    $suggestedReason   = $triage['reason'] ?? $suggestedReason;
                } catch (\Throwable $aiEx) {
                    Log::warning("AI Triage fallback for event reservation: " . $aiEx->getMessage());
                }

                $serviceRequest = DB::transaction(function () use ($room, $guestName, $arrangedCat, $description, $suggestedPriority, $suggestedDept, $venueName, $suggestedReason, $validated, $layout, $apptDate, $apptTime) {
                    $sr = ServiceRequest::create([
                        'room_id'        => $room->id,
                        'type'           => 'service',
                        'guest_name'     => $guestName,
                        'category_id'    => $arrangedCat->id,
                        'description'    => $description,
                        'priority'       => $suggestedPriority,
                        'department'     => $suggestedDept,
                        'status'         => 'pending',
                        'auto_reply_bot' => \App\Models\User::isBotAutoReplyDefaultEnabled(),
                    ]);

                    $sr->statusLogs()->create([
                        'from_status' => null,
                        'to_status'   => 'pending',
                        'notes'       => "Event reservation submitted by guest for {$venueName}.",
                        'changed_by'  => null,
                    ]);

                    $sr->aiTriage()->create([
                        'suggested_category'   => 'Event & Function Hall',
                        'suggested_priority'   => $suggestedPriority,
                        'suggested_department' => $suggestedDept,
                        'reason'               => $suggestedReason,
                        'status'               => 'pending',
                    ]);

                    $sr->messages()->create([
                        'sender_type'        => 'staff',
                        'sender_name'        => 'Madi AI (Auto-Reply)',
                        'message'            => "Warm greetings, {$guestName}! Your tentative hold inquiry for {$venueName} on {$validated['event_date']} ({$validated['guest_count']} pax, {$layout}) has been logged with our Front Office team. We have noted your preferred Lobby Front Desk appointment on {$apptDate} ({$apptTime}). Please kindly proceed to the Ground Floor Front Office Desk to finalize your hall viewing and official booking.",
                        'is_read'            => true,
                    ]);

                    return $sr;
                });

                return redirect()->route('guest.track', ['token' => $token, 'ref' => $serviceRequest->reference_number])
                    ->with('success', "Event inquiry & tentative hold logged! Reference: {$serviceRequest->reference_number}. Please visit the Front Office Desk (Ground Floor Lobby) to arrange an appointment and finalize your booking.");
            } catch (\Throwable $e) {
                Log::error('Failed to store event reservation: ' . $e->getMessage(), ['exception' => $e]);
                return back()->withInput()->with('error', 'Unable to submit your event inquiry right now. Please try again.');
            }
        }

        // Mock Ticket Flow for Demo
        $mockRef = 'REQ-EVT-' . strtoupper(substr(uniqid(), -5));
        $mockTicket = [
            'type'             => 'service',
            'reference_number' => $mockRef,
            'guest_name'       => $guestName,
            'description'      => $description,
            'created_at'       => now()->toDateTimeString(),
            'status'           => 'pending',
        ];

        Session::put("mock_ticket_{$mockRef}", $mockTicket);

        return redirect()->route('guest.track', ['token' => $token, 'ref' => $mockRef])
            ->with('success', "Function Hall reservation request submitted! Reference: {$mockRef}. Front Desk will review availability and message you shortly.");
    }

    // ── Track Status ───────────────────────────────────────────────────────

    public function track(Request $request, string $token)
    {
        \App\Services\StatusProgressionService::runAutoProgression();

        $room = $this->resolveRoom($token);
        $refNumber = $request->input('ref');
        $request_  = null;
        $order     = null;
        $activeChatRequest = null;
        $recentRequests = collect();
        $recentOrders   = collect();

        if (!self::DEMO_OFFLINE_MODE && ($room instanceof Room)) {
            try {
                $stayStart = $room->checked_in_at;

                $recentRequests = ServiceRequest::where('room_id', $room->id)
                    ->when($stayStart, fn($q) => $q->where('created_at', '>=', $stayStart))
                    ->latest()
                    ->take(5)
                    ->get();

                $recentOrders = FoodOrder::where('room_id', $room->id)
                    ->when($stayStart, fn($q) => $q->where('created_at', '>=', $stayStart))
                    ->latest()
                    ->take(5)
                    ->get();

                if (!$refNumber) {
                    $latestReq = $recentRequests->first();
                    $latestOrd = $recentOrders->first();
                    if ($latestReq && $latestOrd) {
                        $refNumber = $latestReq->created_at > $latestOrd->created_at 
                            ? $latestReq->reference_number 
                            : $latestOrd->reference_number;
                    } elseif ($latestReq) {
                        $refNumber = $latestReq->reference_number;
                    } elseif ($latestOrd) {
                        $refNumber = $latestOrd->reference_number;
                    }
                }

                if ($refNumber) {
                    if (str_starts_with($refNumber, 'REQ-')) {
                        $request_ = ServiceRequest::with(['room', 'category', 'statusLogs', 'aiTriage', 'messages'])
                            ->where('reference_number', $refNumber)
                            ->where('room_id', $room->id)
                            ->first();
                    } elseif (str_starts_with($refNumber, 'ORD-')) {
                        $order = FoodOrder::with(['orderItems.foodItem', 'statusLogs'])
                            ->where('reference_number', $refNumber)
                            ->where('room_id', $room->id)
                            ->first();
                    }
                }

                // Always resolve an active chat ServiceRequest for the room if one exists
                if ($request_ instanceof ServiceRequest) {
                    $activeChatRequest = $request_;
                } else {
                    $activeChatRequest = ServiceRequest::with(['room', 'category', 'statusLogs', 'aiTriage', 'messages'])
                        ->where('room_id', $room->id)
                        ->when($stayStart, fn($q) => $q->where('created_at', '>=', $stayStart))
                        ->where('status', '!=', 'closed')
                        ->latest()
                        ->first()
                        ?? ServiceRequest::with(['room', 'category', 'statusLogs', 'aiTriage', 'messages'])
                            ->where('room_id', $room->id)
                            ->when($stayStart, fn($q) => $q->where('created_at', '>=', $stayStart))
                            ->latest()
                            ->first();
                }

                // Compute current stay dining folio summary for the guest
                $deliveredStayOrders = FoodOrder::where('room_id', $room->id)
                    ->when($stayStart, fn($q) => $q->where('created_at', '>=', $stayStart))
                    ->where('status', 'delivered')
                    ->get();
                $activeStayOrdersCount = FoodOrder::where('room_id', $room->id)
                    ->when($stayStart, fn($q) => $q->where('created_at', '>=', $stayStart))
                    ->whereIn('status', ['pending', 'confirmed', 'preparing', 'ready'])
                    ->count();
                $roomChargedOrders  = $deliveredStayOrders->filter(fn($o) => $o->isChargeToRoom());
                $paidDirectOrders   = $deliveredStayOrders->filter(fn($o) => $o->isPayOnArrival());
                $folioSubtotal      = (float) $roomChargedOrders->sum('total_amount');
                $folioServiceCharge = round($folioSubtotal * 0.10, 2);
                $paidDirectTotal    = round((float) $paidDirectOrders->sum('total_amount'), 2);
                $stayFolio = [
                    'delivered_count'   => $deliveredStayOrders->count(),
                    'room_charge_count' => $roomChargedOrders->count(),
                    'paid_direct_count' => $paidDirectOrders->count(),
                    'paid_direct_total' => $paidDirectTotal,
                    'active_count'      => $activeStayOrdersCount,
                    'subtotal'          => $folioSubtotal,
                    'service_charge'    => $folioServiceCharge,
                    'grand_total'       => round($folioSubtotal + $folioServiceCharge, 2),
                ];

                return view('guest.track', compact(
                    'room', 'token', 'refNumber', 'request_', 'order', 'activeChatRequest',
                    'recentRequests', 'recentOrders', 'stayFolio'
                ));
            } catch (\Throwable $e) {
                // Failover to mock track screen
            }
        }

        // Mock Track State for Live Demonstration
        if (!$refNumber) {
            $refNumber = 'REQ-104-7892';
        }

        if (str_starts_with($refNumber, 'ORD-')) {
            $order = (object)[
                'id'               => 1,
                'reference_number' => $refNumber,
                'status'           => 'preparing',
                'status_badge'     => 'primary',
                'guest_name'       => 'Executive Guest',
                'notes'            => 'Deliver to Room 104.',
                'total_amount'     => 630.00,
                'created_at'       => now()->subMinutes(12),
                'room'             => $room,
                'orderItems'       => collect([
                    (object)[
                        'quantity'   => 1,
                        'unit_price' => 450.00,
                        'subtotal'   => 450.00,
                        'foodItem'   => (object)['name' => 'Times Square Angus Burger'],
                    ],
                    (object)[
                        'quantity'   => 1,
                        'unit_price' => 180.00,
                        'subtotal'   => 180.00,
                        'foodItem'   => (object)['name' => 'Iced Spanish Latte'],
                    ],
                ]),
                'statusLogs'       => collect([
                    (object)['status' => 'pending', 'notes' => 'Order transmitted to Times Square Restaurant kitchen.', 'created_at' => now()->subMinutes(12)],
                    (object)['status' => 'confirmed', 'notes' => 'Kitchen acknowledged order.', 'created_at' => now()->subMinutes(9)],
                    (object)['status' => 'preparing', 'notes' => 'Chef cooking dishes for Room 104.', 'created_at' => now()->subMinutes(4)],
                ]),
            ];
        } else {
            $mockSaved = Session::get("mock_ticket_{$refNumber}", []);
            $request_ = (object)[
                'id'               => 1,
                'reference_number' => $refNumber,
                'type'             => $mockSaved['type'] ?? (str_contains($refNumber, 'HVAC') ? 'maintenance' : 'service'),
                'status'           => $mockSaved['status'] ?? 'in_progress',
                'status_badge'     => 'primary',
                'guest_name'       => $mockSaved['guest_name'] ?? 'Executive Guest',
                'description'      => $mockSaved['description'] ?? 'Request submitted via Madison Park Digital Concierge.',
                'photo_path'       => $mockSaved['photo_path'] ?? null,
                'created_at'       => now()->subMinutes(14),
                'updated_at'       => now()->subMinutes(4),
                'priority'         => 'Medium',
                'department'       => 'Engineering',
                'room'             => $room,
                'category'         => (object)['name' => ($mockSaved['type'] ?? '') === 'maintenance' ? 'Air Conditioning & HVAC' : 'Housekeeping & Linens'],
                'aiTriage'         => (object)[
                    'suggested_category'   => ($mockSaved['type'] ?? '') === 'maintenance' ? 'Air Conditioning & HVAC' : 'Housekeeping',
                    'suggested_priority'   => 'High',
                    'suggested_department' => ($mockSaved['type'] ?? '') === 'maintenance' ? 'Engineering' : 'Floor Service',
                    'reason'               => 'AI Vision: Visual defect and room operational status analyzed for priority dispatch.',
                    'status'               => 'triaged',
                ],
                'statusLogs'       => collect([
                    (object)['status' => 'pending', 'notes' => 'Request & photo received by Digital Concierge.', 'created_at' => now()->subMinutes(14)],
                    (object)['status' => 'assigned', 'notes' => 'Assigned to Engineering Technician.', 'created_at' => now()->subMinutes(10)],
                    (object)['status' => 'in_progress', 'notes' => 'Technician dispatched to Room 104.', 'created_at' => now()->subMinutes(4)],
                ]),
            ];
        }

        $recentRequests = collect([
            (object)[
                'reference_number' => 'REQ-104-7892',
                'type'             => 'service',
                'description'      => '2 Extra clean bath towels requested',
                'status'           => 'in_progress',
                'status_badge'     => 'primary',
                'created_at'       => now()->subMinutes(14),
            ],
        ]);

        $recentOrders = collect([
            (object)['reference_number' => 'ORD-104-5514', 'total_amount' => 630.00, 'status' => 'preparing', 'status_badge' => 'primary', 'created_at' => now()->subMinutes(25)],
        ]);

        $activeChatRequest = $request_;

        return view('guest.track', compact(
            'room', 'token', 'refNumber', 'request_', 'order', 'activeChatRequest',
            'recentRequests', 'recentOrders'
        ));
    }

    /**
     * Real-time polling endpoint for guest portal (requests & food orders).
     */
    public function pollGuestStatus(Request $request, string $token)
    {
        $room = $this->resolveRoom($token);
        $ref  = trim((string)$request->query('ref', ''));

        $trackedData = null;

        if ($room instanceof Room) {
            \App\Services\StatusProgressionService::runAutoProgression();

            $stayStart = $room->checked_in_at;
            $clientLastUpdated = (string) $request->query('last_updated', '');

            // Fast preliminary room state signature (~2ms) scoped to current stay session
            $latestRoomReqUpdate = ServiceRequest::where('room_id', $room->id)
                ->when($stayStart, fn($q) => $q->where('created_at', '>=', $stayStart))
                ->max('updated_at') ?? '';
            $latestRoomOrdUpdate = FoodOrder::where('room_id', $room->id)
                ->when($stayStart, fn($q) => $q->where('created_at', '>=', $stayStart))
                ->max('updated_at') ?? '';
            $sig = "room_{$room->id}_{$latestRoomReqUpdate}_{$latestRoomOrdUpdate}_{$ref}";

            if (!empty($ref)) {
                if (str_starts_with($ref, 'REQ-')) {
                    $reqMeta = ServiceRequest::where('reference_number', $ref)
                        ->where('room_id', $room->id)
                        ->select('id', 'status', 'updated_at')
                        ->first();
                    if ($reqMeta) {
                        $msgMaxId = \App\Models\TicketMessage::where('service_request_id', $reqMeta->id)->max('id') ?? 0;
                        $msgCount = \App\Models\TicketMessage::where('service_request_id', $reqMeta->id)->count();
                        $sig .= "_{$reqMeta->status}_{$reqMeta->updated_at}_{$msgMaxId}_{$msgCount}";
                    }
                } elseif (str_starts_with($ref, 'ORD-')) {
                    $ordMeta = FoodOrder::where('reference_number', $ref)
                        ->where('room_id', $room->id)
                        ->select('id', 'status', 'updated_at')
                        ->first();
                    if ($ordMeta) {
                        $sig .= "_{$ordMeta->status}_{$ordMeta->updated_at}";
                    }
                }
            }

            $fastHash = md5($sig);

            // Fast exit: if client already has this exact state, return in ~2ms without running heavy multi-table queries!
            if (!empty($clientLastUpdated) && $clientLastUpdated === $fastHash) {
                return response()->json([
                    'success'      => true,
                    'changed'      => false,
                    'last_updated' => $fastHash,
                ]);
            }

            // If tracking a specific ticket or food order (strictly scoped to this room!)
            if (!empty($ref)) {
                if (str_starts_with($ref, 'REQ-')) {
                    $reqObj = ServiceRequest::with(['statusLogs', 'messages'])
                        ->where('reference_number', $ref)
                        ->where('room_id', $room->id)
                        ->first();

                    if ($reqObj) {
                        $mappedStatus = $reqObj->status;
                        $stepIdx = array_search($mappedStatus, ['pending', 'assigned', 'in_progress', 'resolved']);
                        if ($stepIdx === false) {
                            $stepIdx = ($mappedStatus === 'closed') ? 3 : 0;
                        }
                        $pct = ($stepIdx / 3) * 100;

                        $latestLog = $reqObj->statusLogs->sortByDesc('created_at')->first();

                        $messagesList = $reqObj->messages->map(fn($m) => [
                            'id'             => $m->id,
                            'sender_type'    => $m->sender_type,
                            'sender_name'    => $m->sender_name ?? ($m->sender_type === 'staff' ? 'Front Office' : 'Guest'),
                            'message'        => $m->message,
                            'time_ago'       => $m->time_ago,
                            'formatted_time' => $m->formatted_time,
                            'created_at'     => $m->created_at ? $m->created_at->toIso8601String() : null,
                        ]);

                        $trackedData = [
                            'type'             => 'request',
                            'is_event'         => $reqObj->isEventInquiry(),
                            'reference_number' => $reqObj->reference_number,
                            'status'           => $reqObj->status,
                            'status_label'     => $reqObj->display_status,
                            'status_badge'     => $reqObj->status_badge ?? 'primary',
                            'step_index'       => $stepIdx,
                            'progress_pct'     => round($pct),
                            'priority'         => $reqObj->priority,
                            'department'       => $reqObj->department,
                            'latest_note'      => $latestLog?->notes ?? '',
                            'updated_at_human' => $reqObj->updated_at ? $reqObj->updated_at->diffForHumans() : 'Just now',
                            'is_resolved'      => in_array($reqObj->status, ['resolved', 'closed']),
                            'messages'         => $messagesList,
                        ];
                    }
                } elseif (str_starts_with($ref, 'ORD-')) {
                    $ordObj = FoodOrder::with(['statusLogs'])
                        ->where('reference_number', $ref)
                        ->where('room_id', $room->id)
                        ->first();

                    if ($ordObj) {
                        $orderSteps = ['pending', 'confirmed', 'preparing', 'ready', 'delivered'];
                        $stepIdx = array_search($ordObj->status, $orderSteps);
                        if ($stepIdx === false) {
                            $stepIdx = ($ordObj->status === 'cancelled') ? -1 : 0;
                        }
                        $pct = ($stepIdx >= 0) ? ($stepIdx / (count($orderSteps) - 1)) * 100 : 0;

                        $latestLog = $ordObj->statusLogs->sortByDesc('created_at')->first();

                        $trackedData = [
                            'type'             => 'order',
                            'reference_number' => $ordObj->reference_number,
                            'status'           => $ordObj->status,
                            'status_label'     => ucfirst($ordObj->status),
                            'status_badge'     => $ordObj->status_badge ?? 'primary',
                            'step_index'       => $stepIdx,
                            'progress_pct'     => round($pct),
                            'total_amount'     => number_format($ordObj->total_amount, 2),
                            'latest_note'      => $latestLog?->notes ?? '',
                            'updated_at_human' => $ordObj->updated_at ? $ordObj->updated_at->diffForHumans() : 'Just now',
                            'is_resolved'      => in_array($ordObj->status, ['ready', 'delivered']),
                        ];
                    }
                }
            }

            // Fetch room's recent requests (for active badge counts and activity list)
            $stayStart = $room->checked_in_at;
            $recentReqs = ServiceRequest::where('room_id', $room->id)
                ->when($stayStart, fn($q) => $q->where('created_at', '>=', $stayStart))
                ->latest()
                ->take(5)
                ->get()
                ->map(fn($r) => [
                    'reference_number' => $r->reference_number,
                    'type'             => $r->type,
                    'description'      => \Illuminate\Support\Str::limit($r->description, 40),
                    'status'           => $r->status,
                    'status_label'     => $r->display_status,
                    'status_badge'     => $r->status_badge ?? 'primary',
                    'updated_at_human' => $r->updated_at ? $r->updated_at->diffForHumans() : 'Just now',
                ]);

            // Fetch room's recent food orders
            $recentOrds = FoodOrder::where('room_id', $room->id)
                ->when($stayStart, fn($q) => $q->where('created_at', '>=', $stayStart))
                ->latest()
                ->take(5)
                ->get()
                ->map(fn($o) => [
                    'reference_number' => $o->reference_number,
                    'status'           => $o->status,
                    'status_label'     => ucfirst($o->status),
                    'status_badge'     => $o->status_badge ?? 'primary',
                    'total_amount'     => number_format($o->total_amount, 2),
                    'updated_at_human' => $o->updated_at ? $o->updated_at->diffForHumans() : 'Just now',
                ]);

            // Check if any status transition log occurred for this room within the last 15 seconds (excluding initial guest submission)
            $recentReqLog = RequestStatusLog::whereNotNull('from_status')
                ->whereHas('serviceRequest', fn($q) => $q->where('room_id', $room->id)
                    ->when($stayStart, fn($sq) => $sq->where('created_at', '>=', $stayStart)))
                ->where('created_at', '>=', now()->subSeconds(15))
                ->latest()
                ->first();

            $recentOrdLog = FoodOrderStatusLog::whereNotNull('from_status')
                ->whereHas('foodOrder', fn($q) => $q->where('room_id', $room->id)
                    ->when($stayStart, fn($sq) => $sq->where('created_at', '>=', $stayStart)))
                ->where('created_at', '>=', now()->subSeconds(15))
                ->latest()
                ->first();

            $liveNotification = null;
            $chosenLog = null;
            $chosenType = null;

            if ($recentReqLog && $recentOrdLog) {
                if ($recentReqLog->created_at >= $recentOrdLog->created_at) {
                    $chosenLog = $recentReqLog;
                    $chosenType = 'request';
                } else {
                    $chosenLog = $recentOrdLog;
                    $chosenType = 'order';
                }
            } elseif ($recentReqLog) {
                $chosenLog = $recentReqLog;
                $chosenType = 'request';
            } elseif ($recentOrdLog) {
                $chosenLog = $recentOrdLog;
                $chosenType = 'order';
            }

            if ($chosenType === 'request' && $chosenLog && $chosenLog->serviceRequest) {
                $sr = $chosenLog->serviceRequest;
                $statusLabel = $sr->display_status ?: ucwords(str_replace('_', ' ', $chosenLog->to_status));
                $shortTitle = match ($chosenLog->to_status) {
                    'assigned'    => $sr->isEventInquiry() ? 'Tentative Hold Placed' : 'Request Assigned',
                    'in_progress' => $sr->isEventInquiry() ? 'FO Appointment Ready' : 'Team On The Way',
                    'resolved'    => $sr->isEventInquiry() ? 'Booking Confirmed' : 'Request Completed',
                    'closed'      => 'Request Closed',
                    default       => 'Request ' . $statusLabel,
                };
                $liveNotification = [
                    'id'               => 'req_' . $chosenLog->id,
                    'type'             => 'request',
                    'reference_number' => $sr->reference_number,
                    'status'           => $chosenLog->to_status,
                    'status_label'     => $statusLabel,
                    'title'            => $shortTitle,
                    'message'          => $this->formatGuestStatusMessage($sr, $chosenLog),
                    'url'              => route('guest.track', ['token' => $token, 'ref' => $sr->reference_number]),
                    'is_resolved'      => ($chosenLog->to_status === 'resolved'),
                ];
            } elseif ($chosenType === 'order' && $chosenLog && $chosenLog->foodOrder) {
                $fo = $chosenLog->foodOrder;
                $orderTitle = match ($chosenLog->to_status) {
                    'confirmed' => 'Order Confirmed',
                    'preparing' => 'Kitchen Preparing',
                    'ready'     => $fo->isDineIn() ? 'Ready at Times Square' : 'Order On The Way',
                    'delivered' => 'Order Delivered',
                    'cancelled' => 'Order Cancelled',
                    default     => 'Order ' . ucfirst($chosenLog->to_status),
                };
                $liveNotification = [
                    'id'               => 'ord_' . $chosenLog->id,
                    'type'             => 'order',
                    'reference_number' => $fo->reference_number,
                    'status'           => $chosenLog->to_status,
                    'status_label'     => ucfirst($chosenLog->to_status),
                    'title'            => $orderTitle,
                    'message'          => $this->formatGuestOrderMessage($fo, $chosenLog),
                    'url'              => route('guest.track', ['token' => $token, 'ref' => $fo->reference_number]),
                    'is_resolved'      => in_array($chosenLog->to_status, ['ready', 'delivered']),
                ];
            }

            $payload = [
                'success'           => true,
                'room_number'       => $room->room_number,
                'tracked_item'      => $trackedData,
                'recent_requests'   => $recentReqs,
                'recent_orders'     => $recentOrds,
                'live_notification' => $liveNotification,
            ];

            $payload['changed']      = true;
            $payload['last_updated'] = $fastHash;

            return response()->json($payload);
        }

        // Mock offline fallback
        return response()->json([
            'success'           => true,
            'changed'           => true,
            'last_updated'      => null,
            'room_number'       => '201',
            'tracked_item'      => null,
            'recent_requests'   => [],
            'recent_orders'     => [],
            'live_notification' => null,
        ]);
    }

    /**
     * Recursively strip volatile, time-relative keys so unchanged state yields a stable hash.
     */
    /**
     * Fetch messages for a specific ticket (Guest view). Supports optional since_id for delta polling.
     */
    public function getTicketMessages(Request $request, string $token, string $ref)
    {
        $room = $this->resolveRoom($token);
        $stayStart = ($room instanceof Room) ? $room->checked_in_at : null;

        $serviceRequest = ($room instanceof Room)
            ? ServiceRequest::with('room')
                ->where('reference_number', $ref)
                ->where('room_id', $room->id)
                ->when($stayStart, fn($q) => $q->where('created_at', '>=', $stayStart))
                ->first()
            : null;

        if (!$serviceRequest && ($room instanceof Room) && ($ref === 'NEW' || str_starts_with($ref, 'ORD-'))) {
            $serviceRequest = ServiceRequest::with('room')
                ->where('room_id', $room->id)
                ->when($stayStart, fn($q) => $q->where('created_at', '>=', $stayStart))
                ->where('status', '!=', 'closed')
                ->latest()
                ->first()
                ?? ServiceRequest::with('room')
                    ->where('room_id', $room->id)
                    ->when($stayStart, fn($q) => $q->where('created_at', '>=', $stayStart))
                    ->latest()
                    ->first();
        }

        if (!$serviceRequest) {
            $mockMessages = Session::get("mock_messages_{$ref}", []);
            return response()->json([
                'success'          => true,
                'reference_number' => $ref,
                'messages'         => $mockMessages,
            ]);
        }

        $query = $serviceRequest->messages();

        if ($request->has('since_id') && is_numeric($request->since_id)) {
            $query->where('id', '>', (int)$request->since_id);
            $rawMessages = $query->reorder('id', 'asc')->get();
        } else {
            // Safety limit to the latest 100 messages chronologically (reorder clears relationship ORDER BY)
            $rawMessages = $query->reorder('id', 'desc')->take(100)->get()->reverse()->values();
        }

        $messages = $rawMessages->map(fn($m) => [
            'id'             => $m->id,
            'sender_type'    => $m->sender_type,
            'sender_name'    => $m->sender_name ?? ($m->sender_type === 'staff' ? 'Front Office' : 'Guest'),
            'message'        => $m->message,
            'time_ago'       => $m->time_ago,
            'formatted_time' => $m->formatted_time,
            'created_at'     => $m->created_at ? $m->created_at->toIso8601String() : null,
        ]);

        return response()->json([
            'success'          => true,
            'reference_number' => $serviceRequest->reference_number,
            'messages'         => $messages,
        ]);
    }

    /**
     * Send a message from the Guest to Front Office for a specific ticket.
     */
    public function sendTicketMessage(Request $request, string $token, string $ref)
    {
        $room = $this->resolveRoom($token);

        $validated = $request->validate([
            'message' => 'required|string|min:1|max:1000',
        ]);

        $stayStart = ($room instanceof Room) ? $room->checked_in_at : null;

        $serviceRequest = ($room instanceof Room)
            ? ServiceRequest::with('room')
                ->where('reference_number', $ref)
                ->where('room_id', $room->id)
                ->when($stayStart, fn($q) => $q->where('created_at', '>=', $stayStart))
                ->first()
            : null;

        // If guest passed an explicit foreign/invalid REQ-... reference, reject cleanly instead of creating a phantom ticket
        if (!$serviceRequest && ($room instanceof Room) && str_starts_with($ref, 'REQ-') && !Session::has("mock_ticket_{$ref}")) {
            return response()->json([
                'success' => false,
                'error'   => 'Request not found for your room session.',
                'message' => 'Request not found for your room session.',
            ], 404);
        }

        // If chatting from an order screen (ORD-...) or empty room tracker (NEW),
        // attach to this stay's latest open ticket or auto-create a real ServiceRequest in the database!
        if (!$serviceRequest && ($room instanceof Room) && ($ref === 'NEW' || str_starts_with($ref, 'ORD-'))) {
            $serviceRequest = ServiceRequest::with('room')
                ->where('room_id', $room->id)
                ->when($stayStart, fn($q) => $q->where('created_at', '>=', $stayStart))
                ->whereNotIn('status', ['resolved', 'closed'])
                ->latest()
                ->first();

            if (!$serviceRequest) {
                $cat = RequestCategory::arrangeCategory('Front Desk Concierge', 'service');
                $descPrefix = str_starts_with($ref, 'ORD-') ? "Dining Order #{$ref} Inquiry: " : "Live Guest Chat: ";
                $serviceRequest = DB::transaction(function () use ($room, $cat, $descPrefix, $validated) {
                    $sr = ServiceRequest::create([
                        'room_id'        => $room->id,
                        'type'           => 'service',
                        'guest_name'     => 'Room ' . $room->room_number . ' Guest',
                        'category_id'    => $cat->id,
                        'description'    => $descPrefix . trim($validated['message']),
                        'priority'       => 'medium',
                        'department'     => 'Front Desk',
                        'status'         => 'pending',
                        'auto_reply_bot' => \App\Models\User::isBotAutoReplyDefaultEnabled(),
                    ]);
                    $sr->statusLogs()->create([
                        'from_status' => null,
                        'to_status'   => 'pending',
                        'notes'       => 'Live chat inquiry initiated by guest from Room ' . $room->room_number . '.',
                        'changed_by'  => null,
                    ]);
                    $sr->aiTriage()->create([
                        'suggested_category'   => $cat->name,
                        'suggested_priority'   => 'medium',
                        'suggested_department' => 'Front Desk',
                        'reason'               => 'Guest initiated live chat inquiry via GuestLink Tracker.',
                        'status'               => 'pending',
                    ]);
                    return $sr;
                });
                $serviceRequest->load('room');
            }
        }

        if (!$serviceRequest) {
            // Support session mock ticket fallback for live demonstration
            $msgId = time() . rand(100, 999);
            $mockMsg = [
                'id'             => $msgId,
                'sender_type'    => 'guest',
                'sender_name'    => 'Room ' . ($room->room_number ?? '201') . ' Guest',
                'message'        => trim($validated['message']),
                'time_ago'       => 'Just now',
                'formatted_time' => now()->format('g:i A'),
                'created_at'     => now()->toIso8601String(),
            ];
            $existing = Session::get("mock_messages_{$ref}", []);
            $existing[] = $mockMsg;
            Session::put("mock_messages_{$ref}", $existing);

            return response()->json([
                'success'          => true,
                'reference_number' => $ref,
                'message'          => $mockMsg,
            ]);
        }

        if ($serviceRequest->status === 'closed') {
            return response()->json([
                'success' => false,
                'error'   => 'This request has been closed. Live chat is ended and archived.'
            ], 422);
        }

        $senderRoomNum = $serviceRequest->room?->room_number ?? ($room->room_number ?? '201');
        $senderName = $serviceRequest->guest_name ?: ('Room ' . $senderRoomNum . ' Guest');

        $msg = \App\Models\TicketMessage::create([
            'service_request_id' => $serviceRequest->id,
            'sender_type'        => 'guest',
            'sender_name'        => $senderName,
            'message'            => trim($validated['message']),
            'is_read'            => false,
        ]);

        // Touch the service request so its updated_at timestamp changes!
        // This guarantees that staff polling immediately picks it up!
        $serviceRequest->touch();

        // 🤖 If Live Bot Auto-Responder is enabled on this ticket, auto-reply safely with human-takeover re-check
        $botReplyMsg = null;
        if (!empty($serviceRequest->auto_reply_bot) && $serviceRequest->status !== 'closed') {
            $lock = \Illuminate\Support\Facades\Cache::lock("ticket_ai_bot_{$serviceRequest->id}", 12);
            if ($lock->get()) {
                try {
                    $gemini = app(\App\Services\GeminiService::class);
                    $botDraft = $gemini->draftStaffReply($serviceRequest, $msg->message);
                    $freshReq = $serviceRequest->fresh();
                    // Re-verify human staff did not take over or close the ticket while Gemini was generating
                    if (!empty($botDraft) && $freshReq && !empty($freshReq->auto_reply_bot) && $freshReq->status !== 'closed') {
                        $botMsg = \App\Models\TicketMessage::create([
                            'service_request_id' => $serviceRequest->id,
                            'sender_type'        => 'staff',
                            'sender_name'        => 'Madi AI (Auto-Reply)',
                            'message'            => $botDraft,
                            'is_read'            => true,
                        ]);
                        $serviceRequest->touch();
                        $botReplyMsg = [
                            'id'             => $botMsg->id,
                            'sender_type'    => $botMsg->sender_type,
                            'sender_name'    => $botMsg->sender_name,
                            'message'        => $botMsg->message,
                            'time_ago'       => $botMsg->time_ago,
                            'formatted_time' => $botMsg->formatted_time,
                            'created_at'     => $botMsg->created_at->toIso8601String(),
                        ];
                    }
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning('AI Concierge auto-reply error: ' . $e->getMessage());
                } finally {
                    optional($lock)->release();
                }
            }
        } elseif ($serviceRequest->status !== 'closed') {
            // Even when Live Bot is OFF, still refresh the AI Ticket Context Summary with new guest details
            try {
                $gemini = app(\App\Services\GeminiService::class);
                $gemini->refreshTicketContextFromChat($serviceRequest, $msg->message);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('AI context sync error: ' . $e->getMessage());
            }
        }

        return response()->json([
            'success'          => true,
            'reference_number' => $serviceRequest->reference_number,
            'message'          => [
                'id'             => $msg->id,
                'sender_type'    => $msg->sender_type,
                'sender_name'    => $msg->sender_name,
                'message'        => $msg->message,
                'time_ago'       => $msg->time_ago,
                'formatted_time' => $msg->formatted_time,
                'created_at'     => $msg->created_at->toIso8601String(),
            ],
            'bot_reply'        => $botReplyMsg,
        ]);
    }

    /**
     * Interactive AI Concierge Assistant Endpoint.
     * Answers questions regarding function halls, food prices, dining recommendations, reservations,
     * and supports multimodal photo inspection & voice queries.
     */
    /**
     * Format polite, guest-facing toast text from RequestStatusLog, stripping internal staff/AI audit strings.
     */
    private function formatGuestStatusMessage(ServiceRequest $sr, RequestStatusLog $log): string
    {
        $raw = trim((string) ($log->notes ?? ''));
        $lower = strtolower($raw);
        $dept = $sr->department ?: 'Front Desk';

        $isInternalAudit = $raw === ''
            || str_contains($lower, 'ai triage')
            || str_contains($lower, 'overridden by staff')
            || str_contains($lower, 'accepted by staff')
            || str_contains($lower, 'auto-assigned')
            || str_contains($lower, 'auto-progressed')
            || str_contains($lower, 'triage accepted')
            || str_contains($lower, 'triage modified')
            || str_contains($lower, 'triage overridden')
            || str_contains($lower, 'created by madi');

        if (!$isInternalAudit) {
            return \Illuminate\Support\Str::limit($raw, 110);
        }

        if ($sr->isEventInquiry()) {
            return match ($log->to_status) {
                'assigned'    => 'Your tentative event hold has been logged with our Front Office team.',
                'in_progress' => 'Our Front Office team is ready for your event consultation at the lobby desk.',
                'resolved'    => 'Your event booking details have been confirmed. Thank you!',
                'closed'      => 'Your event inquiry has been closed. Message us anytime.',
                default       => 'Our Front Office team updated your event inquiry.',
            };
        }

        return match ($log->to_status) {
            'assigned'    => "Assigned to our {$dept} team — we're attending to your request.",
            'in_progress' => "Our {$dept} team is actively working on your request now.",
            'resolved'    => 'Your request is complete. Tap View to check details or rate our service.',
            'closed'      => 'This request has been closed. Thank you for staying with us.',
            default       => "Your request was updated by our {$dept} team.",
        };
    }

    /**
     * Format clean, guest-facing toast text from FoodOrderStatusLog.
     */
    private function formatGuestOrderMessage(FoodOrder $fo, FoodOrderStatusLog $log): string
    {
        $raw = trim((string) ($log->notes ?? ''));
        $lower = strtolower($raw);
        $isInternal = $raw === '' || str_contains($lower, 'auto-progressed');

        if (!$isInternal) {
            return \Illuminate\Support\Str::limit($raw, 110);
        }

        return match ($log->to_status) {
            'confirmed' => 'Times Square Kitchen has confirmed your order.',
            'preparing' => 'Our chefs are freshly preparing your order now.',
            'ready'     => $fo->isDineIn()
                ? 'Your dine-in order is ready at Times Square Restaurant (Ground Floor).'
                : 'Your order is ready and on its way to your suite.',
            'delivered' => 'Enjoy your meal! Tap View to rate your dining experience.',
            'cancelled' => 'Your food order has been cancelled.',
            default     => 'Your food order status has been updated.',
        };
    }

    /**
     * Detect whether the guest explicitly wants Madi AI to create a live ticket and notify staff
     * (e.g., talking to Front Office/human staff, notifying Front Desk, or requesting ticket creation).
     */
    private function detectConciergeDirectTicketIntent(string $question, array $aiResponse): ?array
    {
        $q = strtolower(trim($question));
        if ($q === '') {
            return null;
        }

        // 1. Explicit request to talk/speak/chat with Front Office / Front Desk / human staff or notify them
        $wantsHumanFo = (bool) preg_match(
            '/\b(talk|speak|chat|connect|transfer|message|reach|call|notify|alert|inform|escalate)\b.{0,40}\b(front\s*desk|front\s*office|fo\b|reception|receptionist|human|staff|agent|manager|concierge|someone|person)\b/i',
            $q
        ) || (bool) preg_match(
            '/\b(front\s*desk|front\s*office|fo\b|reception)\b.{0,30}\b(talk|speak|chat|connect|notify|alert|help\s*me|contact\s*me|call\s*me)\b/i',
            $q
        );

        // 2. Explicit command asking Madi to create/file/submit/open a ticket or request
        $wantsTicketCreated = (bool) preg_match(
            '/\b(create|make|file|submit|open|log|send|raise|book|dispatch)\b.{0,30}\b(ticket|request|report|complaint|maintenance|repair|housekeeping|attendant|technician|front\s*desk|front\s*office|fo\b)\b/i',
            $q
        );

        if (!$wantsHumanFo && !$wantsTicketCreated) {
            return null;
        }

        $actionType = strtolower((string) ($aiResponse['action_type'] ?? ''));
        $prefill = is_array($aiResponse['prefill_ticket'] ?? null) ? $aiResponse['prefill_ticket'] : [];

        // Determine canonical department, ticket type, and category
        $isMaint = $actionType === 'request_maintenance'
            || ($prefill['type'] ?? '') === 'maintenance'
            || (bool) preg_match('/\b(aircon|ac\b|a\/c|leak|leaking|plumbing|pipe|water\s*heater|hot\s*water|toilet|sink|clog|electrical|outlet|spark|light|bulb|tv|remote|door|lock|repair|broken|not\s*working|maintenance)\b/i', $q);

        $isHousekeeping = !$isMaint && (
            $actionType === 'request_housekeeping'
            || (bool) preg_match('/\b(towel|pillow|blanket|linen|sheet|clean|cleaning|housekeeping|toiletries|soap|shampoo|water\s*bottle|bottled\s*water|trash|laundry|slipper)\b/i', $q)
        );

        if ($wantsHumanFo && !$isMaint && !$isHousekeeping) {
            return [
                'type'          => 'service',
                'department'    => 'Front Desk',
                'category_hint' => 'Front Desk Concierge',
                'priority'      => 'medium',
                'human_handoff' => true,
                'description'   => "Guest requested to speak with Front Desk via Madi AI: \"{$question}\"",
            ];
        }

        if ($isMaint) {
            return [
                'type'          => 'maintenance',
                'department'    => 'Maintenance',
                'category_hint' => $prefill['category'] ?? 'Air Conditioning',
                'priority'      => preg_match('/\b(urgent|emergency|spark|fire|flood|heavy\s*leak|leaking\s*water|locked\s*out|no\s*power)\b/i', $q) ? 'urgent' : 'high',
                'human_handoff' => $wantsHumanFo,
                'description'   => "Submitted via Madi AI: {$question}",
            ];
        }

        if ($isHousekeeping) {
            return [
                'type'          => 'service',
                'department'    => 'Housekeeping',
                'category_hint' => $prefill['category'] ?? 'Extra Towels',
                'priority'      => 'low',
                'human_handoff' => $wantsHumanFo,
                'description'   => "Submitted via Madi AI: {$question}",
            ];
        }

        return [
            'type'          => 'service',
            'department'    => 'Front Desk',
            'category_hint' => $prefill['category'] ?? 'Front Desk Concierge',
            'priority'      => 'medium',
            'human_handoff' => true,
            'description'   => "Submitted via Madi AI: {$question}",
        ];
    }

    /**
     * Create (or append to recent open) ServiceRequest + StatusLog + AiTriageResult + unread TicketMessage
     * so the Staff/Front Desk dashboard receives an immediate live alert chime & notification.
     */
    private function dispatchConciergeTicket(Room $room, array $spec, string $rawGuestMessage, GeminiService $gemini): array
    {
        $department   = in_array($spec['department'] ?? '', ['Front Desk', 'Maintenance', 'Housekeeping'], true)
            ? $spec['department']
            : 'Front Desk';
        $type         = ($spec['type'] ?? 'service') === 'maintenance' ? 'maintenance' : 'service';
        $humanHandoff = (bool) ($spec['human_handoff'] ?? false);
        $description  = trim((string) ($spec['description'] ?? $rawGuestMessage));

        $stayStart = $room->checked_in_at;

        // Smart anti-duplicate: if an open ticket for the same room + department was created in the last 5 mins,
        // append the guest's new message so Front Desk gets an immediate unread alert without duplicate tickets.
        $existing = ServiceRequest::where('room_id', $room->id)
            ->where('department', $department)
            ->whereIn('status', ['pending', 'assigned', 'in_progress'])
            ->when($stayStart, fn($q) => $q->where('created_at', '>=', $stayStart))
            ->where('created_at', '>=', now()->subMinutes(5))
            ->latest('id')
            ->first();

        if ($existing) {
            if ($humanHandoff && $existing->auto_reply_bot) {
                $existing->update(['auto_reply_bot' => false]);
            }
            $existing->messages()->create([
                'sender_type' => 'guest',
                'sender_name' => "Room {$room->room_number} Guest",
                'message'     => $rawGuestMessage,
                'is_read'     => false,
            ]);
            $existing->touch();

            return [
                'reference_number' => $existing->reference_number,
                'department'       => $existing->department,
                'priority'         => strtolower((string) $existing->priority),
                'status'           => $existing->status,
                'human_handoff'    => $humanHandoff,
                'is_existing'      => true,
                'track_url'        => route('guest.track', ['token' => $room->qr_token, 'ref' => $existing->reference_number]),
            ];
        }

        // Resolve matching active DB RequestCategory
        $catHint = strtolower(trim((string) ($spec['category_hint'] ?? '')));
        $category = RequestCategory::where('is_active', true)->get()->first(function ($c) use ($catHint) {
            return $catHint !== '' && (
                strtolower($c->name) === $catHint
                || str_contains(strtolower($c->name), $catHint)
                || str_contains($catHint, strtolower($c->name))
            );
        });

        if (!$category) {
            $fallbackName = match ($department) {
                'Maintenance'  => 'Air Conditioning',
                'Housekeeping' => 'Extra Towels',
                default        => 'Front Desk Concierge',
            };
            $category = RequestCategory::where('is_active', true)->where('name', $fallbackName)->first()
                ?? RequestCategory::where('is_active', true)->where('type', $type)->first()
                ?? RequestCategory::where('is_active', true)->first();
        }

        $priority = strtolower((string) ($spec['priority'] ?? 'medium'));
        if (!in_array($priority, ['low', 'medium', 'high', 'urgent'], true)) {
            $priority = 'medium';
        }

        // Create ticket, initial log, AI triage record, and unread guest message atomically
        $serviceRequest = DB::transaction(function () use ($room, $type, $category, $description, $priority, $department, $humanHandoff, $rawGuestMessage) {
            $sr = ServiceRequest::create([
                'room_id'        => $room->id,
                'category_id'    => $category?->id,
                'type'           => $type,
                'guest_name'     => "Room {$room->room_number} Guest",
                'description'    => $description,
                'status'         => 'pending',
                'priority'       => $priority,
                'department'     => $department,
                'auto_reply_bot' => $humanHandoff ? false : User::isBotAutoReplyDefaultEnabled(),
            ]);

            RequestStatusLog::create([
                'service_request_id' => $sr->id,
                'from_status'        => null,
                'to_status'          => 'pending',
                'changed_by'         => null,
                'notes'              => "Created by Madi AI Concierge on behalf of Room {$room->room_number}.",
            ]);

            AiTriageResult::create([
                'service_request_id'   => $sr->id,
                'suggested_category'   => $category?->name ?? 'Front Desk Concierge',
                'suggested_priority'   => $priority,
                'suggested_department' => $department,
                'reason'               => $humanHandoff
                    ? "Guest directly requested live {$department} assistance via Madi AI Concierge; routed immediately with live chat enabled."
                    : "Direct guest request submitted through Madi AI Concierge and routed to {$department}.",
                'status'               => 'pending',
            ]);

            $sr->messages()->create([
                'sender_type' => 'guest',
                'sender_name' => "Room {$room->room_number} Guest",
                'message'     => $rawGuestMessage,
                'is_read'     => false,
            ]);

            return $sr;
        });

        return [
            'reference_number' => $serviceRequest->reference_number,
            'department'       => $serviceRequest->department,
            'priority'         => strtolower((string) $serviceRequest->priority),
            'status'           => $serviceRequest->status,
            'human_handoff'    => $humanHandoff,
            'is_existing'      => false,
            'track_url'        => route('guest.track', ['token' => $room->qr_token, 'ref' => $serviceRequest->reference_number]),
        ];
    }

    /**
     * AI Concierge Interactive Chat Endpoint (supports live text, voice transcripts, and photo attachments).
     */
    public function askAiConcierge(Request $request, string $token, GeminiService $gemini)
    {
        $room = $this->resolveRoom($token);
        $roomNumber = $room instanceof Room ? $room->room_number : '201';

        $request->validate([
            'question'   => 'nullable|string|max:1000',
            'history'    => 'nullable',
            'photo'      => 'nullable|file|image|mimes:jpg,jpeg,png,webp|max:10240',
            'photo_data' => 'nullable|string|max:15000000',
        ]);

        $question = trim((string)($request->input('question') ?? ''));
        $history  = $request->input('history', []);
        if (is_string($history)) {
            $decodedHistory = json_decode($history, true);
            $history = is_array($decodedHistory) ? $decodedHistory : [];
        }

        // Handle photo attachment (either multipart file or validated base64 image data)
        $imagePath = null;
        $imageMime = 'image/jpeg';
        $tempFileCreated = false;

        if ($request->hasFile('photo')) {
            $photo = $request->file('photo');
            $imagePath = $photo->getRealPath();
            $imageMime = $photo->getMimeType() ?: 'image/jpeg';
        } elseif ($request->filled('photo_data') && str_contains($request->input('photo_data'), 'base64,')) {
            $rawPhotoData = (string) $request->input('photo_data');
            $parts = explode('base64,', $rawPhotoData, 2);
            $decoded = base64_decode($parts[1] ?? '', true);
            $imgInfo = ($decoded !== false && strlen($decoded) > 0) ? @getimagesizefromstring($decoded) : false;
            if ($decoded !== false && is_array($imgInfo) && in_array($imgInfo['mime'] ?? '', ['image/jpeg', 'image/png', 'image/webp'], true)) {
                $tempFile = tempnam(sys_get_temp_dir(), 'ai_guest_');
                file_put_contents($tempFile, $decoded);
                $imagePath = $tempFile;
                $imageMime = $imgInfo['mime'];
                $tempFileCreated = true;
            }
        }

        if (empty($question) && !empty($imagePath)) {
            $question = "I have attached a photo from Room {$roomNumber}. Please inspect it and assist me.";
        } elseif (empty($question)) {
            $question = "Hello Madison!";
        }

        try {
            $response = $gemini->askHotelConcierge(
                $question,
                $roomNumber,
                $history,
                $imagePath,
                $imageMime
            );
        } catch (\Throwable $e) {
            Log::error('AI Concierge ask failed: ' . $e->getMessage(), ['exception' => $e]);
            $response = $gemini->fallbackConciergeAnswer($question, $roomNumber, $imagePath);
        } finally {
            if ($tempFileCreated && $imagePath && file_exists($imagePath)) {
                @unlink($imagePath);
            }
        }

        // True Agentic Execution: if guest explicitly asked to talk to Front Desk / notify staff / create a ticket,
        // create the actual DB ticket & unread staff alert immediately so Front Desk is notified in real time.
        if ($room instanceof Room) {
            $ticketIntent = $this->detectConciergeDirectTicketIntent($question, $response);
            if ($ticketIntent !== null) {
                try {
                    $created = $this->dispatchConciergeTicket($room, $ticketIntent, $question, $gemini);
                    $response['created_ticket'] = $created;

                    $ref  = $created['reference_number'];
                    $dept = $created['department'];
                    if ($created['is_existing']) {
                        $response['reply'] = "I've forwarded your message directly to our **{$dept}** team on your active ticket **{$ref}** and triggered a live alert on their desk console.\n\n"
                            . "Tap **Open Live Chat** below to continue messaging our team directly, or dial **Ext. 0** for an immediate voice call.";
                    } else {
                        $response['reply'] = "Done! I've created ticket **{$ref}** for **Room {$roomNumber}** and immediately alerted our **{$dept}** team on their live operations console.\n\n"
                            . ($created['human_handoff']
                                ? "A live **{$dept}** staff member will respond to you directly in Live Chat shortly. Tap **Open Live Chat** below to track updates or send additional details."
                                : "You can track live progress or message our **{$dept}** team anytime by tapping **Open Live Chat** below.");
                    }
                } catch (\Throwable $e) {
                    Log::error('Madi AI auto-ticket dispatch failed: ' . $e->getMessage());
                }
            }
        }

        return response()->json([
            'success' => true,
            'data'    => $response,
        ]);
    }

    /**
     * 1-Click Direct Ticket Creation endpoint triggered from Madi AI Concierge action buttons.
     */
    public function createTicketFromConcierge(Request $request, string $token, GeminiService $gemini)
    {
        $room = $this->resolveRoom($token);
        if (!($room instanceof Room)) {
            return response()->json(['success' => false, 'message' => 'Invalid room session.'], 404);
        }

        $validated = $request->validate([
            'type'          => 'nullable|in:service,maintenance',
            'department'    => 'nullable|string|max:60',
            'category'      => 'nullable|string|max:100',
            'description'   => 'required|string|max:1000',
            'human_handoff' => 'nullable|boolean',
        ]);

        $deptRaw = strtolower(trim((string) ($validated['department'] ?? '')));
        $department = match (true) {
            str_contains($deptRaw, 'maint') || str_contains($deptRaw, 'eng') || ($validated['type'] ?? '') === 'maintenance' => 'Maintenance',
            str_contains($deptRaw, 'house') => 'Housekeeping',
            default => 'Front Desk',
        };

        $desc = trim($validated['description']);
        $created = $this->dispatchConciergeTicket($room, [
            'type'          => ($validated['type'] ?? ($department === 'Maintenance' ? 'maintenance' : 'service')),
            'department'    => $department,
            'category_hint' => $validated['category'] ?? ($department === 'Front Desk' ? 'Front Desk Concierge' : ''),
            'priority'      => $department === 'Maintenance' ? 'high' : ($department === 'Housekeeping' ? 'low' : 'medium'),
            'human_handoff' => (bool) ($validated['human_handoff'] ?? ($department === 'Front Desk')),
            'description'   => $desc,
        ], $desc, $gemini);

        return response()->json([
            'success' => true,
            'ticket'  => $created,
            'message' => "Ticket {$created['reference_number']} created and {$created['department']} has been notified.",
        ]);
    }

    /**
     * Allow guest to cancel their own pending food order before kitchen confirms.
     */
    public function cancelFoodOrder(Request $request, string $token, string $ref)
    {
        $room = $this->resolveRoom($token);
        if (!($room instanceof Room)) {
            return back()->with('error', 'Unable to cancel order in demo mode.');
        }

        $order = FoodOrder::where('reference_number', $ref)
            ->where('room_id', $room->id)
            ->first();

        if (!$order) {
            return back()->with('error', 'Order not found for your room.');
        }

        if ($order->status !== 'pending') {
            return back()->with('error', "Order {$order->reference_number} can no longer be cancelled because it is already {$order->status}.");
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($order) {
            $order->update(['status' => 'cancelled']);
            FoodOrderStatusLog::create([
                'food_order_id' => $order->id,
                'from_status'   => 'pending',
                'to_status'     => 'cancelled',
                'notes'         => 'Cancelled by guest via In-Room QR Portal.',
                'changed_by'    => null,
            ]);
        });

        return redirect()->route('guest.track', ['token' => $token, 'ref' => $order->reference_number])
            ->with('success', "Order {$order->reference_number} has been cancelled.");
    }

    /**
     * Allow guest to manually confirm they received their Ready food order (marks Delivered & Completed/Locked).
     */
    public function confirmFoodOrderReceived(Request $request, string $token, string $ref)
    {
        $room = $this->resolveRoom($token);
        if (!($room instanceof Room)) {
            return back()->with('error', 'Unable to confirm delivery in demo mode.');
        }

        $order = FoodOrder::where('reference_number', $ref)
            ->where('room_id', $room->id)
            ->first();

        if (!$order) {
            return back()->with('error', 'Order not found for your room.');
        }

        if ($order->status === 'delivered') {
            return redirect()->route('guest.track', ['token' => $token, 'ref' => $order->reference_number])
                ->with('success', "Order {$order->reference_number} is already marked as Completed.");
        }

        if ($order->status !== 'ready') {
            return back()->with('error', 'You can confirm receipt once your order is Ready for delivery.');
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($order) {
            $order->update(['status' => 'delivered']);
            FoodOrderStatusLog::create([
                'food_order_id' => $order->id,
                'from_status'   => 'ready',
                'to_status'     => 'delivered',
                'notes'         => 'Guest confirmed order received & completed via In-Room QR Portal (locked).',
                'changed_by'    => null,
            ]);
        });

        return redirect()->route('guest.track', ['token' => $token, 'ref' => $order->reference_number])
            ->with('success', "Thank you! Order {$order->reference_number} is now Completed. Enjoy your meal!");
    }

    /**
     * Allow guest to self-cancel their own pending/assigned service or maintenance request.
     */
    public function cancelServiceRequest(Request $request, string $token, string $ref)
    {
        $room = $this->resolveRoom($token);
        if (!($room instanceof Room)) {
            return back()->with('error', 'Unable to cancel request in demo mode.');
        }

        $sr = ServiceRequest::where('reference_number', $ref)
            ->where('room_id', $room->id)
            ->first();

        if (!$sr) {
            return back()->with('error', 'Request not found for your room.');
        }

        if (!in_array($sr->status, ['pending', 'assigned'], true)) {
            return back()->with('error', "Request {$sr->reference_number} can no longer be self-cancelled because it is already " . str_replace('_', ' ', $sr->status) . ".");
        }

        $oldStatus = $sr->status;

        \Illuminate\Support\Facades\DB::transaction(function () use ($sr, $oldStatus) {
            $sr->update([
                'status'         => 'closed',
                'auto_reply_bot' => false,
            ]);
            RequestStatusLog::create([
                'service_request_id' => $sr->id,
                'from_status'        => $oldStatus,
                'to_status'          => 'closed',
                'notes'              => 'Cancelled by guest via In-Room QR Portal.',
                'changed_by'         => null,
            ]);
        });

        return redirect()->route('guest.track', ['token' => $token, 'ref' => $sr->reference_number])
            ->with('success', "Request {$sr->reference_number} has been cancelled.");
    }

    /**
     * Save guest 1-5 star rating & feedback on a completed service request or delivered food order.
     */
    public function submitRating(Request $request, string $token)
    {
        $room = $this->resolveRoom($token);
        if (!($room instanceof Room)) {
            return back()->with('error', 'Ratings require an active room session.');
        }

        $validated = $request->validate([
            'type'     => 'required|in:request,order',
            'ref'      => 'required|string|max:50',
            'rating'   => 'required|integer|min:1|max:5',
            'feedback' => 'nullable|string|max:500',
        ]);

        if ($validated['type'] === 'request') {
            $record = ServiceRequest::where('reference_number', $validated['ref'])
                ->where('room_id', $room->id)
                ->first();

            if (!$record || !in_array($record->status, ['resolved', 'closed'], true)) {
                return back()->with('error', 'You can rate a request once it is resolved.');
            }
        } else {
            $record = FoodOrder::where('reference_number', $validated['ref'])
                ->where('room_id', $room->id)
                ->first();

            if (!$record || $record->status !== 'delivered') {
                return back()->with('error', 'You can rate an order once it is delivered.');
            }
        }

        $record->update([
            'guest_rating'   => $validated['rating'],
            'guest_feedback' => $validated['feedback'] ?? null,
        ]);

        return redirect()->route('guest.track', ['token' => $token, 'ref' => $validated['ref']])
            ->with('success', 'Thank you for rating your Madison Park Hotel experience.');
    }
}

