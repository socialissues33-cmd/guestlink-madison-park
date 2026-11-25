<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\RequestCategory;
use App\Models\RequestStatusLog;
use App\Models\ServiceRequest;
use App\Models\TicketMessage;
use App\Models\User;
use App\Services\GeminiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class RequestController extends Controller
{
    private function isRestaurantOnly(): bool
    {
        $user = auth()->user();
        return $user
            && $user->roles()->exists()
            && $user->hasRole('restaurant')
            && !$user->hasAnyRole(['admin', 'owner', 'front_desk', 'maintenance', 'maintenance_supervisor', 'housekeeping']);
    }

    public function index(Request $request)
    {
        if ($this->isRestaurantOnly()) {
            return redirect()->route('food.orders')
                ->with('error', 'Kitchen accounts only have access to Food Orders & Times Square Menu.');
        }

        \App\Services\StatusProgressionService::runAutoProgression();

        // Service & Maintenance Requests: Rolling 24h Operations Cycle by default
        $period = $request->query('period', '24h');

        $query = ServiceRequest::with(['room', 'category', 'assignedTo', 'aiTriage'])
            ->withCount([
                'messages',
                'messages as unread_guest_messages_count' => fn($q) => $q->where('sender_type', 'guest')->where('is_read', false),
            ]);

        // Date period scoping
        if ($period === 'current_month') {
            $query->where('created_at', '>=', now()->startOfMonth());
        } elseif ($period === 'last_month') {
            $query->whereBetween('created_at', [
                now()->subMonth()->startOfMonth(),
                now()->subMonth()->endOfMonth(),
            ]);
        } elseif ($period === '24h') {
            $query->where('created_at', '>=', now()->subHours(24));
        }
        // If $period === 'all', show all-time history

        // Auto-scope Maintenance personnel to Maintenance / Engineering / Assigned tickets
        $authUser = auth()->user();
        $isMaintOnly = $authUser && $authUser->hasAnyRole(['maintenance', 'maintenance_supervisor'])
            && !$authUser->hasAnyRole(['admin', 'owner', 'front_desk', 'housekeeping']);

        if ($isMaintOnly && !$request->filled('department') && !$request->filled('room')) {
            $query->where(function ($q) use ($authUser) {
                $q->where('type', 'maintenance')
                  ->orWhereRaw("LOWER(COALESCE(department, '')) IN ('maintenance', 'engineering')")
                  ->orWhere('assigned_to', $authUser->id);
            });
        }

        if ($request->filled('type')) {
            if ($request->type === 'event') {
                $query->where(function ($q) {
                    $q->whereHas('category', fn($c) => $c->whereRaw("(LOWER(name) LIKE '%event%' OR LOWER(name) LIKE '%function hall%')"))
                      ->orWhere('description', 'like', '%FUNCTION HALL%');
                });
            } else {
                $query->where('type', $request->type);
            }
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('priority')) {
            $query->whereRaw('LOWER(priority) = ?', [strtolower((string) $request->priority)]);
        }
        if ($request->filled('department')) {
            $deptFilter = strtolower(trim((string) $request->department));
            if ($deptFilter === 'maintenance') {
                $query->whereRaw("LOWER(COALESCE(department, '')) IN ('maintenance', 'engineering')");
            } else {
                $query->whereRaw("LOWER(COALESCE(department, '')) = ?", [$deptFilter]);
            }
        }
        if ($request->filled('room')) {
            $roomNum = trim((string) $request->room);
            $query->whereHas('room', fn($r) => $r->where('room_number', $roomNum));
        }
        if ($request->filled('assigned')) {
            $assignedParam = trim((string) $request->assigned);
            if ($assignedParam === 'me') {
                $query->where('assigned_to', auth()->id());
            } elseif ($assignedParam === 'unassigned') {
                $query->whereNull('assigned_to');
            } elseif (is_numeric($assignedParam)) {
                $query->where('assigned_to', (int) $assignedParam);
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

        $requests = $query->orderByRaw("
            CASE LOWER(COALESCE(priority, 'low')) 
                WHEN 'urgent' THEN 1 
                WHEN 'high' THEN 2 
                WHEN 'medium' THEN 3 
                WHEN 'low' THEN 4 
                ELSE 5 
            END ASC
        ")->latest()->paginate(15)->withQueryString();
        try {
            $maintenanceStaff = User::role(['maintenance', 'maintenance_supervisor'])->where('is_active', true)->get();
        } catch (\Throwable $e) {
            $maintenanceStaff = collect();
        }
        $assignableStaff = User::where('is_active', true)
            ->whereDoesntHave('roles', fn($r) => $r->where('name', 'restaurant'))
            ->orderBy('department')
            ->orderBy('name')
            ->get();

        $currentMonthLabel = now()->format('F Y');
        $currentMonthCount = ServiceRequest::where('created_at', '>=', now()->startOfMonth())->count();
        $allTimeCount      = ServiceRequest::count();

        return view('staff.requests.index', compact(
            'requests',
            'maintenanceStaff',
            'assignableStaff',
            'period',
            'currentMonthLabel',
            'currentMonthCount',
            'allTimeCount'
        ));
    }

    public function show(ServiceRequest $serviceRequest)
    {
        if ($this->isRestaurantOnly()) {
            return redirect()->route('food.orders')
                ->with('error', 'Kitchen accounts only have access to Food Orders & Times Square Menu.');
        }

        \App\Services\StatusProgressionService::runAutoProgression();
        $serviceRequest->refresh();
        $serviceRequest->load(['room', 'category', 'assignedTo', 'aiTriage.reviewedBy', 'statusLogs.changedBy', 'messages.user']);
        
        // Mark guest messages as read when staff views the ticket
        $serviceRequest->messages()->where('sender_type', 'guest')->where('is_read', false)->update(['is_read' => true]);

        try {
            $maintenanceStaff = User::role(['maintenance', 'maintenance_supervisor'])->where('is_active', true)->get();
        } catch (\Throwable $e) {
            $maintenanceStaff = collect();
        }
        $assignableStaff = User::where('is_active', true)
            ->whereDoesntHave('roles', fn($r) => $r->where('name', 'restaurant'))
            ->orderBy('department')
            ->orderBy('name')
            ->get();
        $requestCategories = RequestCategory::where('is_active', true)->orderBy('type')->orderBy('name')->get();
        return view('staff.requests.show', compact('serviceRequest', 'maintenanceStaff', 'assignableStaff', 'requestCategories'));
    }

    public function updateStatus(Request $request, ServiceRequest $serviceRequest)
    {
        $user = auth()->user();
        if ($user && $user->roles()->exists() && $user->hasRole('restaurant') && !$user->hasAnyRole(['admin', 'owner', 'front_desk', 'maintenance', 'maintenance_supervisor', 'housekeeping'])) {
            abort(403, 'Role Access Restricted: Restaurant personnel are only authorized for Kitchen & Dining modules.');
        }

        $validated = $request->validate([
            'status'      => 'required|in:pending,assigned,in_progress,resolved,closed',
            'priority'    => 'nullable|in:Low,Medium,High,Urgent,low,medium,high,urgent',
            'department'  => 'nullable|string|max:60',
            'assigned_to' => ['nullable', Rule::exists('users', 'id')->where('is_active', true)],
            'notes'       => 'nullable|string|max:500',
        ]);

        $oldStatus   = $serviceRequest->status;
        $oldAssigned = $serviceRequest->assigned_to;

        // Forward-Only & Terminal-Lock Policy (blocks backward jumps; only Admin can reopen closed)
        $transitionCheck = \App\Services\StatusProgressionService::canTransitionRequest(
            $oldStatus,
            $validated['status'],
            (bool) (auth()->user()?->hasRole('admin'))
        );
        if (!$transitionCheck['allowed']) {
            return back()->with('error', $transitionCheck['error']);
        }

        $cleanPriority = !empty($validated['priority'])
            ? strtolower(trim($validated['priority']))
            : strtolower((string) ($serviceRequest->priority ?? 'medium'));

        $cleanDepartment = $serviceRequest->department ?? 'Front Desk';
        if (!empty($validated['department'])) {
            $rawDept = strtolower(trim($validated['department']));
            $cleanDepartment = str_contains($rawDept, 'housekeep')
                ? 'Housekeeping'
                : (preg_match('/maint|engineer|facilit|technic/', $rawDept) ? 'Maintenance' : 'Front Desk');
        }

        $newAssignedTo = $request->has('assigned_to') ? ($validated['assigned_to'] ?: null) : $serviceRequest->assigned_to;
        $newStatus     = $validated['status'];

        // Auto-advance from pending to assigned if a staff/technician is newly assigned
        if ($oldStatus === 'pending' && $newStatus === 'pending' && !empty($newAssignedTo) && $newAssignedTo != $oldAssigned) {
            $newStatus = 'assigned';
        }

        $logNotes = $validated['notes'] ?? null;
        if ($oldStatus === 'closed' && $newStatus !== 'closed') {
            $logNotes = "Ticket re-opened by Administrator " . auth()->user()->name . ($logNotes ? ": {$logNotes}" : ".");
        } elseif ($newAssignedTo != $oldAssigned && !empty($newAssignedTo)) {
            $assignedUser = User::find($newAssignedTo);
            $assignedLabel = $assignedUser ? "Assigned to {$assignedUser->name} (" . ($assignedUser->department ?? 'Staff') . ")" : "Assigned to staff";
            $logNotes = $logNotes ? "{$assignedLabel} — {$logNotes}" : "{$assignedLabel}.";
        }

        DB::transaction(function () use ($serviceRequest, $newStatus, $cleanPriority, $cleanDepartment, $newAssignedTo, $oldStatus, $logNotes) {
            $serviceRequest->update([
                'status'      => $newStatus,
                'priority'    => $cleanPriority,
                'department'  => $cleanDepartment,
                'assigned_to' => $newAssignedTo,
            ]);

            // Prevent duplicate logs like Pending -> Pending unless there is a meaningful note or assignment change
            if ($oldStatus !== $newStatus || !empty($logNotes)) {
                RequestStatusLog::create([
                    'service_request_id' => $serviceRequest->id,
                    'from_status'        => $oldStatus,
                    'to_status'          => $newStatus,
                    'notes'              => $logNotes,
                    'changed_by'         => auth()->id(),
                ]);
            }
        });

        return back()->with('success', 'Request updated successfully.');
    }

    /**
     * Dedicated Technician / Maintenance Feedback & Status Report endpoint.
     * Logs technician field notes directly to Front Office timeline & Guest Live Tracker.
     */
    public function submitTechnicianFeedback(Request $request, ServiceRequest $serviceRequest)
    {
        $user = auth()->user();
        if ($user && $user->roles()->exists() && $user->hasRole('restaurant') && !$user->hasAnyRole(['admin', 'owner', 'front_desk', 'maintenance', 'maintenance_supervisor', 'housekeeping'])) {
            abort(403, 'Role Access Restricted.');
        }

        if ($serviceRequest->status === 'closed' && (!$user || !$user->hasRole('admin'))) {
            return back()->with('error', 'Ticket is closed. Only an Administrator can update a closed ticket.');
        }

        $validated = $request->validate([
            'status'       => 'required|in:assigned,in_progress,resolved',
            'feedback'     => 'required|string|min:3|max:600',
            'notify_guest' => 'nullable|boolean',
        ]);

        $oldStatus = $serviceRequest->status;
        $newStatus = $validated['status'];

        $check = \App\Services\StatusProgressionService::canTransitionRequest($oldStatus, $newStatus, (bool) ($user?->hasRole('admin')));
        if (!$check['allowed']) {
            return back()->with('error', $check['error']);
        }
        $techName  = $user?->name ?? 'Maintenance Technician';
        $techDept  = $user?->department ?? 'Maintenance';

        $updateData = ['status' => $newStatus];
        if (empty($serviceRequest->assigned_to) && $user) {
            $updateData['assigned_to'] = $user->id;
        }
        $serviceRequest->update($updateData);

        $formattedNote = "[Technician Feedback — {$techName} ({$techDept})]: " . trim($validated['feedback']);

        RequestStatusLog::create([
            'service_request_id' => $serviceRequest->id,
            'from_status'        => $oldStatus,
            'to_status'          => $newStatus,
            'notes'              => $formattedNote,
            'changed_by'         => $user?->id,
        ]);

        if ($request->boolean('notify_guest')) {
            TicketMessage::create([
                'service_request_id' => $serviceRequest->id,
                'user_id'            => $user?->id,
                'sender_type'        => 'staff',
                'sender_name'        => "{$techName} ({$techDept})",
                'message'            => trim($validated['feedback']),
                'is_read'            => true,
            ]);
        }

        return back()->with('success', 'Technician feedback logged to Front Office timeline and synced with Guest Tracker.');
    }

    public function reviewAi(Request $request, ServiceRequest $serviceRequest)
    {
        $user = auth()->user();
        if ($user && $user->roles()->exists() && $user->hasRole('restaurant') && !$user->hasAnyRole(['admin', 'owner', 'front_desk', 'maintenance', 'maintenance_supervisor', 'housekeeping'])) {
            abort(403, 'Role Access Restricted: Restaurant personnel are not authorized to override AI Triage.');
        }

        if ($serviceRequest->status === 'closed') {
            return back()->with('error', 'Cannot apply AI review to a closed ticket. Please have an Administrator re-open it first.');
        }
        $validated = $request->validate([
            'action'               => 'required|in:accept,modify',
            'suggested_category'   => 'nullable|string',
            'suggested_priority'   => 'nullable|in:Low,Medium,High,Urgent,low,medium,high,urgent',
            'suggested_department' => 'nullable|string',
        ]);

        if (!$serviceRequest->aiTriage) {
            return back()->with('error', 'No AI triage record found for this ticket.');
        }

        $targetPriority   = strtolower($serviceRequest->priority ?? 'medium');
        $targetDepartment = $serviceRequest->department ?? 'Front Desk';

        if ($serviceRequest->aiTriage) {
            $normalizedPriority = !empty($validated['suggested_priority'])
                ? strtolower(trim($validated['suggested_priority']))
                : strtolower($serviceRequest->aiTriage->suggested_priority ?? 'medium');

            $rawDept = strtolower(trim((string) ($validated['suggested_department'] ?? $serviceRequest->aiTriage->suggested_department ?? 'Front Desk')));
            $normalizedDepartment = str_contains($rawDept, 'housekeep')
                ? 'Housekeeping'
                : (preg_match('/maint|engineer|facilit|technic/', $rawDept) ? 'Maintenance' : 'Front Desk');

            $targetCategory = trim((string) ($validated['suggested_category'] ?? $serviceRequest->aiTriage->suggested_category ?? 'General Inquiry'));

            $targetPriority   = $normalizedPriority;
            $targetDepartment = $normalizedDepartment;

            DB::transaction(function () use ($serviceRequest, $validated, $targetCategory, $normalizedPriority, $normalizedDepartment, $targetDepartment, $targetPriority) {
                $serviceRequest->aiTriage->update([
                    'status'               => $validated['action'] === 'accept' ? 'accepted' : 'modified',
                    'suggested_category'   => $targetCategory,
                    'suggested_priority'   => $normalizedPriority,
                    'suggested_department' => $normalizedDepartment,
                    'reviewed_by'          => auth()->id(),
                    'reviewed_at'          => now(),
                ]);

                $updateData = [
                    'priority'   => $targetPriority,
                    'department' => $targetDepartment,
                ];

                // Dynamically arrange and assign Category
                if (!empty($targetCategory)) {
                    $categoryModel = RequestCategory::arrangeCategory($targetCategory, $serviceRequest->type ?? 'maintenance');
                    if ($categoryModel) {
                        $updateData['category_id'] = $categoryModel->id;
                    }
                }

                // When AI recommendation is accepted/reviewed, auto-advance from pending to assigned!
                $oldStatus = $serviceRequest->status;
                if ($oldStatus === 'pending') {
                    $updateData['status'] = 'assigned';
                }

                $serviceRequest->update($updateData);

                $prioLabel = ucfirst($targetPriority);
                if ($oldStatus === 'pending') {
                    $actionText = $validated['action'] === 'accept' ? 'accepted' : 'overridden by staff';
                    RequestStatusLog::create([
                        'service_request_id' => $serviceRequest->id,
                        'from_status'        => 'pending',
                        'to_status'          => 'assigned',
                        'notes'              => "Madi AI triage {$actionText}. Auto-assigned and dispatched to {$targetDepartment} ({$prioLabel} · {$targetCategory}).",
                        'changed_by'         => auth()->id(),
                    ]);
                } elseif ($validated['action'] === 'modify') {
                    RequestStatusLog::create([
                        'service_request_id' => $serviceRequest->id,
                        'from_status'        => $oldStatus,
                        'to_status'          => $serviceRequest->status,
                        'notes'              => "Madi AI triage overridden by " . auth()->user()->name . ": {$targetCategory} · {$prioLabel} · {$targetDepartment}.",
                        'changed_by'         => auth()->id(),
                    ]);
                }
            });
        }

        $prioDisplay = ucfirst($targetPriority);
        $flashMessage = ($validated['action'] ?? 'accept') === 'modify'
            ? "Madi AI triage overridden & ticket updated ({$targetDepartment} · {$prioDisplay})."
            : "Madi AI recommendation accepted & dispatched to {$targetDepartment}.";

        return back()->with('success', $flashMessage);
    }

    /**
     * Fetch messages for this ticket (Staff view). Supports optional since_id for delta polling.
     */
    public function getMessages(Request $request, ServiceRequest $serviceRequest)
    {
        if ($this->isRestaurantOnly()) {
            abort(403, 'Role Access Restricted: Restaurant personnel are only authorized for Kitchen & Dining modules.');
        }

        // Mark unread guest messages as read
        $serviceRequest->messages()->where('sender_type', 'guest')->where('is_read', false)->update(['is_read' => true]);

        $query = $serviceRequest->messages()->with('user');

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
            'is_read'        => $m->is_read,
        ]);

        $serviceRequest->load('aiTriage');
        $aiContext = $serviceRequest->aiTriage?->reason;

        return response()->json([
            'success'          => true,
            'messages'         => $messages,
            'ai_context'       => $aiContext,
            'auto_reply_bot'   => (bool) $serviceRequest->auto_reply_bot,
            'cancel_requested' => $serviceRequest->status !== 'closed' && str_starts_with((string) $aiContext, '[Cancellation Requested]'),
        ]);
    }

    /**
     * Send a staff response message to the guest.
     */
    public function sendMessage(Request $request, ServiceRequest $serviceRequest)
    {
        if ($this->isRestaurantOnly()) {
            abort(403, 'Role Access Restricted: Restaurant personnel are only authorized for Kitchen & Dining modules.');
        }

        if ($serviceRequest->status === 'closed') {
            return response()->json([
                'success' => false,
                'error'   => 'This request has been closed. Re-open the ticket to send messages.'
            ], 422);
        }

        $validated = $request->validate([
            'message' => 'required|string|min:1|max:1000',
        ]);

        $user = auth()->user();
        $roleName = $user->getRoleNames()->first() ?? 'Front Desk';
        $roleLabel = ucwords(str_replace('_', ' ', $roleName));
        $senderName = "{$user->name} ({$roleLabel})";

        $msg = TicketMessage::create([
            'service_request_id' => $serviceRequest->id,
            'sender_type'        => 'staff',
            'sender_name'        => $senderName,
            'user_id'            => $user->id,
            'message'            => trim($validated['message']),
            'is_read'            => true,
        ]);

        // Human Takeover Auto-Pause: when human staff replies manually, pause Live Bot so AI never talks over or contradicts staff
        $botPausedByHuman = false;
        if ($serviceRequest->auto_reply_bot) {
            $serviceRequest->update(['auto_reply_bot' => false]);
            $botPausedByHuman = true;
        } else {
            $serviceRequest->touch();
        }

        return response()->json([
            'success'             => true,
            'auto_reply_bot'      => (bool) $serviceRequest->auto_reply_bot,
            'bot_paused_by_human' => $botPausedByHuman,
            'message'             => [
                'id'             => $msg->id,
                'sender_type'    => $msg->sender_type,
                'sender_name'    => $msg->sender_name,
                'message'        => $msg->message,
                'time_ago'       => $msg->time_ago,
                'formatted_time' => $msg->formatted_time,
                'created_at'     => $msg->created_at->toIso8601String(),
            ],
        ]);
    }

    /**
     * AI-assisted draft reply generation using Gemini.
     */
    public function draftAiReply(Request $request, ServiceRequest $serviceRequest, GeminiService $gemini)
    {
        if ($this->isRestaurantOnly()) {
            abort(403, 'Role Access Restricted.');
        }

        if ($serviceRequest->status === 'closed') {
            return response()->json([
                'success' => false,
                'error'   => 'This request has been closed.'
            ], 422);
        }

        $latestGuestMsg = $serviceRequest->messages()
            ->where('sender_type', 'guest')
            ->latest()
            ->value('message');

        $draft = $gemini->draftStaffReply($serviceRequest, $latestGuestMsg);

        return response()->json([
            'success' => true,
            'draft'   => $draft,
        ]);
    }

    /**
     * 1-Click Instant AI Auto-Respond & Send directly to Guest.
     */
    public function autoReplyAi(Request $request, ServiceRequest $serviceRequest, GeminiService $gemini)
    {
        if ($this->isRestaurantOnly()) {
            abort(403, 'Role Access Restricted.');
        }

        if ($serviceRequest->status === 'closed') {
            return response()->json([
                'success' => false,
                'error'   => 'This request has been closed. Re-open ticket to reply.'
            ], 422);
        }

        $latestGuestMsg = $serviceRequest->messages()
            ->where('sender_type', 'guest')
            ->latest()
            ->value('message');

        $replyText = $gemini->draftStaffReply($serviceRequest, $latestGuestMsg);

        if (empty($replyText)) {
            return response()->json([
                'success' => false,
                'error'   => 'Unable to generate AI reply. Please try again.'
            ], 500);
        }

        $user = auth()->user();
        $senderName = $user ? "{$user->name} (via Madi AI Concierge)" : "Madi AI (Auto-Reply)";

        $msg = TicketMessage::create([
            'service_request_id' => $serviceRequest->id,
            'sender_type'        => 'staff',
            'sender_name'        => $senderName,
            'user_id'            => $user?->id,
            'message'            => $replyText,
            'is_read'            => true,
        ]);

        $serviceRequest->touch();

        return response()->json([
            'success' => true,
            'message' => [
                'id'             => $msg->id,
                'sender_type'    => $msg->sender_type,
                'sender_name'    => $msg->sender_name,
                'message'        => $msg->message,
                'time_ago'       => $msg->time_ago,
                'formatted_time' => $msg->formatted_time,
                'created_at'     => $msg->created_at->toIso8601String(),
            ],
        ]);
    }

    /**
     * Toggle Live Bot Auto-Responder for this service request.
     */
    public function toggleAutoReplyBot(Request $request, ServiceRequest $serviceRequest)
    {
        if ($this->isRestaurantOnly()) {
            abort(403, 'Role Access Restricted.');
        }

        $serviceRequest->auto_reply_bot = !$serviceRequest->auto_reply_bot;
        $serviceRequest->save();

        // Persist this bot preference to the staff member's account as well
        if ($user = $request->user()) {
            $user->forceFill(['bot_auto_reply_enabled' => (bool) $serviceRequest->auto_reply_bot])->save();
            if ($user->hasAnyRole(['admin', 'owner', 'front_desk'])) {
                \Illuminate\Support\Facades\Cache::forever('guestlink_hotel_bot_auto_reply', (bool) $serviceRequest->auto_reply_bot);
            }
        }

        return response()->json([
            'success'        => true,
            'auto_reply_bot' => (bool) $serviceRequest->auto_reply_bot,
            'message'        => $serviceRequest->auto_reply_bot
                ? 'Madi AI Auto-Responder turned ON and saved to your account.'
                : 'Madi AI Auto-Responder turned OFF and saved to your account.',
        ]);
    }

    /**
     * Delete entire conversation history for this service request.
     */
    public function deleteConversation(Request $request, ServiceRequest $serviceRequest)
    {
        if ($this->isRestaurantOnly()) {
            abort(403, 'Role Access Restricted.');
        }

        $count = $serviceRequest->messages()->count();
        $serviceRequest->messages()->delete();
        $serviceRequest->touch();

        return response()->json([
            'success'       => true,
            'deleted_count' => $count,
            'message'       => "Conversation history cleared ({$count} messages removed).",
        ]);
    }
}
