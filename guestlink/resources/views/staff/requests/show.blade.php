@extends('layouts.app')
@section('title', 'Request #' . $serviceRequest->reference_number)
@section('page-title', 'Request Details')

@push('styles')
<style>
    .request-hero-card {
        background: linear-gradient(155deg, rgba(18, 28, 46, 0.96) 0%, rgba(11, 18, 31, 0.98) 100%);
        border: 1px solid rgba(201, 162, 74, 0.24);
        border-radius: 16px;
        box-shadow: 0 12px 32px rgba(0, 0, 0, 0.36);
    }
    .ticket-meta-strip {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 0.75rem;
        padding: 0.85rem 1rem;
        border-radius: 12px;
        background: rgba(8, 13, 24, 0.72);
        border: 1px solid rgba(246, 241, 231, 0.08);
    }
    @media (max-width: 767.98px) {
        .ticket-meta-strip {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }
    .ticket-meta-label {
        font-size: 0.64rem;
        text-transform: uppercase;
        letter-spacing: 0.09em;
        color: #7E8899;
        font-weight: 700;
        margin-bottom: 0.2rem;
    }
    .ticket-meta-value {
        font-size: 0.84rem;
        font-weight: 600;
        color: #F6F1E7;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .ticket-stage-rail {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 0.5rem;
        position: relative;
    }
    .ticket-stage-step {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.48rem 0.65rem;
        border-radius: 10px;
        background: rgba(255, 255, 255, 0.025);
        border: 1px solid rgba(246, 241, 231, 0.07);
        min-width: 0;
    }
    .ticket-stage-step.is-done {
        background: rgba(201, 162, 74, 0.08);
        border-color: rgba(201, 162, 74, 0.28);
    }
    .ticket-stage-step.is-current {
        background: rgba(201, 162, 74, 0.16);
        border-color: rgba(201, 162, 74, 0.62);
        box-shadow: 0 0 0 1px rgba(201, 162, 74, 0.2) inset;
    }
    .ticket-stage-dot {
        width: 22px;
        height: 22px;
        border-radius: 50%;
        flex-shrink: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 0.68rem;
        font-weight: 700;
        background: rgba(255, 255, 255, 0.06);
        color: #7E8899;
    }
    .ticket-stage-step.is-done .ticket-stage-dot {
        background: rgba(201, 162, 74, 0.24);
        color: #E6CA7A;
    }
    .ticket-stage-step.is-current .ticket-stage-dot {
        background: linear-gradient(135deg, #B89135, #E6CA7A);
        color: #140F04;
    }
    @media (max-width: 767.98px) {
        .ticket-stage-rail {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }
    .guest-quote-box {
        background: rgba(8, 13, 24, 0.8);
        border: 1px solid rgba(201, 162, 74, 0.22);
        border-left: 3px solid var(--gold);
        border-radius: 12px;
        padding: 1.05rem 1.25rem;
        position: relative;
    }
    .ai-compact-bar {
        background: linear-gradient(145deg, rgba(17, 27, 44, 0.94) 0%, rgba(11, 18, 31, 0.98) 100%);
        border: 1px solid rgba(201, 162, 74, 0.24);
        border-radius: 14px;
    }
    .ai-param-pill {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.34rem 0.68rem;
        border-radius: 9px;
        background: rgba(8, 13, 24, 0.72);
        border: 1px solid rgba(246, 241, 231, 0.09);
        font-size: 0.76rem;
    }
    .form-dark-control {
        background: rgba(9, 15, 27, 0.88) !important;
        border: 1px solid rgba(201, 162, 74, 0.26) !important;
        color: #F6F1E7 !important;
        border-radius: 10px;
        padding: 0.56rem 0.82rem;
        font-size: 0.82rem;
        transition: all 0.2s ease;
    }
    .form-dark-control:focus {
        border-color: var(--gold) !important;
        box-shadow: 0 0 0 3px rgba(201, 162, 74, 0.16) !important;
        background: rgba(13, 22, 38, 0.96) !important;
    }
    .form-dark-control option {
        background: #0F1A2C;
        color: #F6F1E7;
    }
    .timeline-scroll {
        max-height: 470px;
        overflow-y: auto;
        padding-right: 4px;
    }
    .timeline-node {
        position: relative;
        padding-left: 1.55rem;
        margin-bottom: 1.05rem;
    }
    .timeline-node:last-child {
        margin-bottom: 0;
    }
    .timeline-node::before {
        content: '';
        position: absolute;
        left: 5px;
        top: 15px;
        bottom: -14px;
        width: 2px;
        background: rgba(201, 162, 74, 0.2);
    }
    .timeline-node:last-child::before {
        display: none;
    }
    .timeline-dot {
        position: absolute;
        left: 0;
        top: 3px;
        width: 12px;
        height: 12px;
        border-radius: 50%;
        background: var(--gold);
        border: 2px solid #0E1728;
    }
    .timeline-dot.urgent { background: #ef4444; }
    .timeline-dot.success { background: #22c55e; }
    .timeline-dot.ai { background: #E6CA7A; }
</style>
@endpush

@section('content')
@php
    $currAuthUser = auth()->user();
    $isFrontOffice = !$currAuthUser
        || !$currAuthUser->roles()->exists()
        || $currAuthUser->hasAnyRole(['admin', 'owner', 'front_desk', 'staff']);
    $isEventInquiry = $serviceRequest->isEventInquiry();
    $prioLower = strtolower($serviceRequest->priority ?? '');
    $stageSteps = $isEventInquiry
        ? [
            'pending'     => ['label' => 'Inquiry Received', 'icon' => 'bi-inbox'],
            'assigned'    => ['label' => 'Tentative Hold',   'icon' => 'bi-pin-angle'],
            'in_progress' => ['label' => 'FO Appointment',   'icon' => 'bi-calendar2-check'],
            'resolved'    => ['label' => 'Confirmed',        'icon' => 'bi-check-lg'],
        ]
        : [
            'pending'     => ['label' => 'Pending',     'icon' => 'bi-inbox'],
            'assigned'    => ['label' => 'Assigned',    'icon' => 'bi-person-check'],
            'in_progress' => ['label' => 'In Progress', 'icon' => 'bi-lightning-charge'],
            'resolved'    => ['label' => 'Resolved',    'icon' => 'bi-check-lg'],
        ];
    $stageKeys = array_keys($stageSteps);
    $effectiveStageKey = $serviceRequest->status === 'closed' ? 'resolved' : $serviceRequest->status;
    $currentStageIdx = array_search($effectiveStageKey, $stageKeys);
    if ($currentStageIdx === false) { $currentStageIdx = 0; }
@endphp

{{-- Top Command Bar: Back, Reference, Live Status, & 1-Click Stage Actions --}}
<div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
    <div class="d-flex align-items-center gap-2 flex-wrap">
        <a href="{{ route('requests.index') }}" class="btn-ghost px-3 py-1 text-decoration-none d-inline-flex align-items-center gap-1" style="font-size:0.78rem; border-radius:999px;">
            <i class="bi bi-arrow-left"></i> Back to Requests
        </a>
        <code class="px-2.5 py-1 rounded-pill" style="background:rgba(201,162,74,0.1); border:1px solid rgba(201,162,74,0.28); color:#E6CA7A; font-size:0.8rem; font-weight:700;">
            {{ $serviceRequest->reference_number }}
        </code>
        <span class="badge bg-{{ $serviceRequest->status_badge }} px-2.5 py-1" style="font-size:0.72rem; border-radius:999px;">
            {{ $serviceRequest->display_status }}
        </span>
        @if($serviceRequest->priority)
        <span class="badge {{ $prioLower === 'urgent' ? 'badge-status-urgent' : ($prioLower === 'high' ? 'badge-status-high' : 'bg-' . $serviceRequest->priority_badge) }} px-2.5 py-1" style="font-size:0.72rem; border-radius:999px;">
            {{ ucfirst($prioLower) }} Priority
        </span>
        @endif
    </div>

    {{-- 1-Click Forward-Only Stage Actions --}}
    <div class="d-flex align-items-center gap-1.5 flex-wrap">
        @if(in_array($serviceRequest->status, ['pending', 'assigned'], true))
        <form method="POST" action="{{ route('requests.status', $serviceRequest) }}" class="m-0">
            @csrf
            <input type="hidden" name="status" value="in_progress">
            <input type="hidden" name="notes" value="{{ $isEventInquiry ? 'Scheduled in-person Front Office appointment & hall viewing by ' . auth()->user()->name : 'Started work & marked in progress by ' . auth()->user()->name }}">
            <button type="submit" class="btn btn-sm px-3 py-1.5 d-inline-flex align-items-center gap-1.5" style="font-size:0.75rem; font-weight:600; border-radius:999px; background:rgba(201,162,74,0.14); border:1px solid rgba(201,162,74,0.4); color:#E6CA7A;">
                <i class="bi {{ $isEventInquiry ? 'bi-calendar2-check' : 'bi-play-circle' }}"></i>
                {{ $isEventInquiry ? 'FO Appointment' : 'Start Work' }}
            </button>
        </form>
        @endif

        @if(!in_array($serviceRequest->status, ['resolved', 'closed'], true))
        <form method="POST" action="{{ route('requests.status', $serviceRequest) }}" class="m-0">
            @csrf
            <input type="hidden" name="status" value="resolved">
            <input type="hidden" name="notes" value="{{ $isEventInquiry ? 'Event booking officially confirmed at Front Desk by ' . auth()->user()->name : 'Marked resolved by ' . auth()->user()->name }}">
            <button type="submit" class="btn btn-sm px-3 py-1.5 d-inline-flex align-items-center gap-1.5" style="font-size:0.75rem; font-weight:600; border-radius:999px; background:rgba(34,197,94,0.14); border:1px solid rgba(34,197,94,0.4); color:#86efac;">
                <i class="bi bi-check2-circle"></i>
                {{ $isEventInquiry ? 'Confirm Booking' : 'Mark Resolved' }}
            </button>
        </form>
        @endif

        @if($isFrontOffice)
            @if($serviceRequest->status !== 'closed')
            <form method="POST" action="{{ route('requests.status', $serviceRequest) }}" class="m-0">
                @csrf
                <input type="hidden" name="status" value="closed">
                <input type="hidden" name="notes" value="{{ $isEventInquiry ? 'Event inquiry closed/archived by ' . auth()->user()->name : 'Ticket closed by ' . auth()->user()->name }}">
                <button type="submit" class="btn btn-sm px-3 py-1.5 d-inline-flex align-items-center gap-1.5" style="font-size:0.75rem; border-radius:999px; background:rgba(148,163,184,0.1); border:1px solid rgba(148,163,184,0.26); color:#cbd5e1;">
                    <i class="bi bi-lock"></i> {{ $isEventInquiry ? 'Close Inquiry' : 'Close' }}
                </button>
            </form>
            @elseif(auth()->user()->hasRole('admin'))
            <form method="POST" action="{{ route('requests.status', $serviceRequest) }}" class="m-0">
                @csrf
                <input type="hidden" name="status" value="in_progress">
                <input type="hidden" name="notes" value="Ticket re-opened by {{ auth()->user()->name }}">
                <button type="submit" class="btn btn-sm px-3 py-1.5 d-inline-flex align-items-center gap-1.5" style="font-size:0.75rem; border-radius:999px; background:rgba(201,162,74,0.15); border:1px solid rgba(201,162,74,0.4); color:#E6CA7A;">
                    <i class="bi bi-arrow-counterclockwise"></i> Re-open
                </button>
            </form>
            @else
            <span class="badge d-inline-flex align-items-center gap-1" style="font-size:0.72rem; border-radius:999px; background:rgba(148,163,184,0.12); border:1px solid rgba(148,163,184,0.25); color:#94a3b8; padding:0.35rem 0.75rem;" title="Only an Administrator can re-open a closed ticket">
                <i class="bi bi-lock-fill"></i> Closed
            </span>
            @endif
        @endif
    </div>
</div>

{{-- Urgent Priority Attention Strip (Only when Urgent & Open) --}}
@if($prioLower === 'urgent' && !in_array($serviceRequest->status, ['resolved', 'closed'], true))
<div class="mb-3 px-3.5 py-2.5 rounded-3 d-flex align-items-center justify-content-between flex-wrap gap-2" style="background:rgba(244,63,94,0.1); border:1px solid rgba(244,63,94,0.38);">
    <div class="d-flex align-items-center gap-2.5">
        <i class="bi bi-shield-exclamation text-danger" style="font-size:1.15rem;"></i>
        <div style="font-size:0.82rem; color:#fecdd3;">
            <strong style="color:#fff;">Urgent Ticket — Room {{ $serviceRequest->room?->room_number ?? '201' }}</strong>
            <span class="ms-1" style="color:#fda4af;">({{ $serviceRequest->created_at->diffForHumans() }} &bull; {{ $serviceRequest->department ?? 'Front Desk' }})</span>
        </div>
    </div>
    @if($serviceRequest->status !== 'in_progress')
    <form method="POST" action="{{ route('requests.status', $serviceRequest) }}" class="m-0">
        @csrf
        <input type="hidden" name="status" value="in_progress">
        <input type="hidden" name="notes" value="Dispatched immediately via urgent alert bar">
        <button type="submit" class="btn btn-sm px-3 py-1 d-inline-flex align-items-center gap-1" style="font-size:0.74rem; font-weight:700; border-radius:999px; background:rgba(244,63,94,0.22); border:1px solid rgba(244,63,94,0.5); color:#fff;">
            <i class="bi bi-lightning-charge-fill"></i> Dispatch Now
        </button>
    </form>
    @endif
</div>
@endif

<div class="row g-3 align-items-start">
    {{-- Left / Primary Ticket Workspace --}}
    <div class="col-xl-8 col-12">

        {{-- 1. Clean Hero Ticket Brief Card --}}
        <div class="request-hero-card p-4 mb-3">
            {{-- Title Row --}}
            <div class="d-flex align-items-center justify-content-between gap-3 mb-3 flex-wrap">
                <div class="d-flex align-items-center gap-3 min-w-0">
                    <div style="width:44px; height:44px; border-radius:12px; background:rgba(201,162,74,0.12); border:1px solid rgba(201,162,74,0.28); display:flex; align-items:center; justify-content:center; font-size:1.25rem; flex-shrink:0;">
                        <i class="bi {{ $isEventInquiry ? 'bi-calendar-event' : ($serviceRequest->type === 'maintenance' ? 'bi-tools' : 'bi-bell') }} text-gold"></i>
                    </div>
                    <div class="min-w-0">
                        <h1 style="font-family:'Playfair Display',serif; font-size:1.3rem; margin:0; color:#F6F1E7; line-height:1.2;">
                            {{ $isEventInquiry ? 'Event Inquiry & Tentative Hold' : ucfirst($serviceRequest->type) . ' Request' }}
                        </h1>
                        <div style="font-size:0.75rem; color:#8C94A4; margin-top:0.2rem;">
                            Logged {{ $serviceRequest->created_at->format('M d, Y \a\t g:i A') }} &bull; {{ $serviceRequest->created_at->diffForHumans() }}
                        </div>
                    </div>
                </div>
                <span class="px-2.5 py-1 rounded-pill" style="background:rgba(255,255,255,0.04); border:1px solid rgba(246,241,231,0.1); font-size:0.74rem; color:#E7E2D7; white-space:nowrap;">
                    {{ $serviceRequest->category?->icon ?? '🏷️' }}
                    {{ $serviceRequest->category?->name ?? ($serviceRequest->aiTriage?->suggested_category ?? 'General') }}
                </span>
            </div>

            {{-- 4-Stage Visual Lifecycle Stepper (Applies to EVERY Ticket Type) --}}
            <div class="ticket-stage-rail mb-3">
                @foreach($stageSteps as $sKey => $sInfo)
                @php
                    $sIdx = array_search($sKey, $stageKeys);
                    $isDone = $sIdx < $currentStageIdx || $serviceRequest->status === 'closed';
                    $isCur  = !$isDone && $sIdx === $currentStageIdx;
                @endphp
                <div class="ticket-stage-step {{ $isCur ? 'is-current' : ($isDone ? 'is-done' : '') }}">
                    <span class="ticket-stage-dot">
                        @if($isDone)
                            <i class="bi bi-check-lg"></i>
                        @else
                            <i class="bi {{ $sInfo['icon'] }}"></i>
                        @endif
                    </span>
                    <span class="text-truncate" style="font-size:0.73rem; font-weight:{{ $isCur ? '700' : '600' }}; color:{{ $isCur ? '#F6F1E7' : ($isDone ? '#E6CA7A' : '#7E8899') }};">
                        {{ $sInfo['label'] }}
                    </span>
                </div>
                @endforeach
            </div>

            @if($isEventInquiry)
            <div class="mb-3 px-3 py-2 rounded-3 d-flex align-items-center gap-2" style="background:rgba(201,162,74,0.08); border:1px solid rgba(201,162,74,0.28); font-size:0.76rem; color:#E7E2D7;">
                <i class="bi bi-building-check text-gold shrink-0"></i>
                <span><strong style="color:#E6CA7A;">Front Desk Appointment Policy:</strong> Invite the guest to the Ground Floor Lobby Desk for hall viewing, contract &amp; deposit.</span>
            </div>
            @endif

            {{-- Guest Request Description (Hero Focal Point, Pre-Line Preserved for Structured Inquiries) --}}
            <div class="guest-quote-box mb-3">
                <div class="d-flex align-items-center justify-content-between mb-1.5">
                    <span style="color:#C9A24A; font-size:0.68rem; text-transform:uppercase; letter-spacing:0.12em; font-weight:700;">
                        Guest Request Details
                    </span>
                    <span style="font-size:0.7rem; color:#7E8899;">
                        Room {{ $serviceRequest->room?->room_number ?? '201' }} &middot; Verified QR
                    </span>
                </div>
                <div style="font-size:0.95rem; line-height:1.68; color:#F6F1E7; white-space:pre-line;">{{ trim($serviceRequest->description) }}</div>

                @if(!empty($serviceRequest->photo_path))
                <div class="mt-3 pt-3" style="border-top:1px dashed rgba(201,162,74,0.24);">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span style="color:#E6CA7A; font-size:0.72rem; font-weight:700; text-transform:uppercase; letter-spacing:0.08em;">
                            <i class="bi bi-camera me-1"></i>Attached Guest Photo
                        </span>
                        <a href="{{ asset($serviceRequest->photo_path) }}" target="_blank" class="btn-ghost py-0.5 px-2 text-decoration-none" style="font-size:0.7rem; border-radius:999px;">
                            <i class="bi bi-arrows-fullscreen me-1"></i>Full Size
                        </a>
                    </div>
                    <a href="{{ asset($serviceRequest->photo_path) }}" target="_blank" class="d-inline-block rounded-3 overflow-hidden" style="border:1px solid rgba(201,162,74,0.38);">
                        <img src="{{ asset($serviceRequest->photo_path) }}" alt="Guest Attachment" style="max-height:240px; max-width:100%; object-fit:cover; display:block;">
                    </a>
                </div>
                @endif

                @if(!empty($serviceRequest->guest_rating))
                <div class="mt-3 pt-2.5 d-flex align-items-center justify-content-between flex-wrap gap-2" style="border-top:1px dashed rgba(201,162,74,0.24);">
                    <span style="color:#E6CA7A; font-size:0.72rem; font-weight:700; text-transform:uppercase; letter-spacing:0.08em;">
                        <i class="bi bi-star-fill text-warning me-1"></i>Guest Satisfaction Rating
                    </span>
                    <span style="color:#FBBF24; font-size:0.84rem; font-weight:700;">
                        @for($i = 1; $i <= 5; $i++)
                            <i class="bi {{ $i <= $serviceRequest->guest_rating ? 'bi-star-fill' : 'bi-star' }}"></i>
                        @endfor
                        <span class="ms-1 text-light" style="font-size:0.76rem;">({{ $serviceRequest->guest_rating }}/5)</span>
                    </span>
                    @if(!empty($serviceRequest->guest_feedback))
                    <div class="w-100" style="font-size:0.82rem; color:#CBD5E1; font-style:italic;">
                        "{{ $serviceRequest->guest_feedback }}"
                    </div>
                    @endif
                </div>
                @endif
            </div>

            {{-- Slim 4-Item Ticket Summary Strip (Replaces 3 Bloated Duplicate Cards) --}}
            <div class="ticket-meta-strip">
                <div class="min-w-0">
                    <div class="ticket-meta-label">Guest</div>
                    <div class="ticket-meta-value">
                        {{ $serviceRequest->guest_name ?? 'In-House Guest' }}
                        <i class="bi bi-patch-check-fill text-success ms-1" style="font-size:0.72rem;" title="Verified Room QR"></i>
                    </div>
                </div>
                <div class="min-w-0">
                    <div class="ticket-meta-label">Room</div>
                    <div class="ticket-meta-value" style="color:#E6CA7A;">
                        Room {{ $serviceRequest->room?->room_number ?? '201' }}
                        <span style="font-size:0.72rem; color:#8C94A4; font-weight:500;">· F{{ $serviceRequest->room?->floor ?? 1 }}</span>
                    </div>
                </div>
                <div class="min-w-0">
                    <div class="ticket-meta-label">Department</div>
                    <div class="ticket-meta-value">{{ $serviceRequest->department ?? 'Front Desk' }}</div>
                </div>
                <div class="min-w-0">
                    <div class="ticket-meta-label">Assigned To</div>
                    <div class="ticket-meta-value" style="color:{{ $serviceRequest->assignedTo ? '#86efac' : '#7E8899' }};">
                        {{ $serviceRequest->assignedTo?->name ?? 'Unassigned' }}
                    </div>
                </div>
            </div>
        </div>

        {{-- Dedicated Maintenance / Technician Field Feedback Card (Maintenance / Field Staff Only) --}}
        @if(!$isFrontOffice && $serviceRequest->status !== 'closed')
        <div id="technicianFeedbackCard" class="table-card p-4 mb-3" style="border: 1px solid rgba(201,162,74,0.32);">
            <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom flex-wrap gap-2" style="border-color:rgba(201,162,74,0.18) !important;">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-wrench-adjustable-circle text-gold" style="font-size:1.15rem;"></i>
                    <div>
                        <h2 style="font-family:'Playfair Display',serif; font-size:1.1rem; color:#F6F1E7; margin:0;">
                            Technician Feedback &amp; Field Report
                        </h2>
                        <div style="font-size:0.72rem; color:#8C94A4;">
                            Report diagnosis &amp; repair progress directly to Front Office
                        </div>
                    </div>
                </div>
                <span class="badge" style="background:rgba(201,162,74,0.12); border:1px solid rgba(201,162,74,0.3); color:#E6CA7A; font-size:0.68rem;">
                    <i class="bi bi-broadcast me-1"></i>Syncs to FO Timeline
                </span>
            </div>

            <form method="POST" action="{{ route('requests.feedback', $serviceRequest) }}">
                @csrf
                <div class="d-flex flex-wrap gap-1.5 mb-3">
                    <button type="button" class="btn btn-sm"
                            onclick="document.getElementById('techFeedbackInput').value='Arrived at Room {{ $serviceRequest->room?->room_number ?? '201' }} — inspecting fixture and diagnosing issue now.'; document.getElementById('techStatusSelect').value='in_progress';"
                            style="font-size:0.72rem; background:rgba(255,255,255,0.04); border:1px solid rgba(201,162,74,0.24); color:#E6CA7A; border-radius:999px;">
                        <i class="bi bi-geo-alt me-1"></i> Arrived &amp; Inspecting
                    </button>
                    <button type="button" class="btn btn-sm"
                            onclick="document.getElementById('techFeedbackInput').value='Repair in progress — servicing component and testing room system.'; document.getElementById('techStatusSelect').value='in_progress';"
                            style="font-size:0.72rem; background:rgba(255,255,255,0.04); border:1px solid rgba(201,162,74,0.24); color:#E6CA7A; border-radius:999px;">
                        <i class="bi bi-tools me-1"></i> Repair In Progress
                    </button>
                    <button type="button" class="btn btn-sm"
                            onclick="document.getElementById('techFeedbackInput').value='Repair completed & tested — fixture in Room {{ $serviceRequest->room?->room_number ?? '201' }} is now operating normally.'; document.getElementById('techStatusSelect').value='resolved';"
                            style="font-size:0.72rem; background:rgba(34,197,94,0.12); border:1px solid rgba(34,197,94,0.35); color:#86efac; border-radius:999px;">
                        <i class="bi bi-check2-circle me-1"></i> Repaired &amp; Resolved
                    </button>
                    <button type="button" class="btn btn-sm"
                            onclick="document.getElementById('techFeedbackInput').value='Requires replacement part — temporary fix applied and Front Office notified for follow-up.'; document.getElementById('techStatusSelect').value='in_progress';"
                            style="font-size:0.72rem; background:rgba(245,158,11,0.12); border:1px solid rgba(245,158,11,0.35); color:#fde68a; border-radius:999px;">
                        <i class="bi bi-exclamation-triangle me-1"></i> Part Required
                    </button>
                </div>

                <div class="row g-2 align-items-end">
                    <div class="col-md-8 col-12">
                        <label class="form-label" style="font-size:0.74rem; font-weight:700; color:#E6CA7A;">
                            Action Taken / Notes <span class="text-danger">*</span>
                        </label>
                        <textarea name="feedback" id="techFeedbackInput" class="form-control form-dark-control" rows="2" required minlength="3" maxlength="600"
                                  placeholder="Describe what was inspected, repaired, or needs Front Office attention..."></textarea>
                    </div>
                    <div class="col-md-4 col-12">
                        <label class="form-label" style="font-size:0.74rem; font-weight:700; color:#E6CA7A;">
                            Ticket Status
                        </label>
                        <select name="status" id="techStatusSelect" class="form-select form-dark-control">
                            <option value="in_progress" {{ $serviceRequest->status === 'in_progress' ? 'selected' : '' }}>In Progress (Working)</option>
                            <option value="resolved" {{ $serviceRequest->status === 'resolved' ? 'selected' : '' }}>Resolved (Completed)</option>
                            <option value="assigned" {{ $serviceRequest->status === 'assigned' ? 'selected' : '' }}>Acknowledged / Assigned</option>
                        </select>
                    </div>
                    <div class="col-12 d-flex justify-content-end mt-2">
                        <button type="submit" class="btn-gold px-4 py-2 d-inline-flex align-items-center gap-2" style="font-size:0.82rem; font-weight:700; border-radius:10px;">
                            <i class="bi bi-send-check-fill"></i> Submit Feedback to Front Office
                        </button>
                    </div>
                </div>
            </form>
        </div>
        @endif

        @if($isFrontOffice)
        {{-- 2. Adaptive Madi AI Triage Card (System-Accurate: Exact DB Categories, Exact 3 Queues, Zero Duplicate Text) --}}
        @if($serviceRequest->aiTriage)
        @php
            $aiTriage = $serviceRequest->aiTriage;
            $triagePrio = strtolower($aiTriage->suggested_priority ?? 'medium');
            $isTriagePending = $aiTriage->status === 'pending';

            $curCatName = $serviceRequest->category?->name ?? 'Uncategorized';
            $curPrioVal = strtolower($serviceRequest->priority ?? 'medium');
            $curDeptVal = $serviceRequest->department ?? 'Front Desk';

            $catDiffers  = strcasecmp($curCatName, (string) $aiTriage->suggested_category) !== 0;
            $prioDiffers = $curPrioVal !== $triagePrio;
            $deptDiffers = strcasecmp($curDeptVal, (string) $aiTriage->suggested_department) !== 0;
            $aiInSync    = !$catDiffers && !$prioDiffers && !$deptDiffers;

            $canonicalDepts = ['Front Desk', 'Maintenance', 'Housekeeping'];
            $allCategories  = $requestCategories ?? \App\Models\RequestCategory::where('is_active', true)->orderBy('type')->orderBy('name')->get();
            $maintCats      = $allCategories->where('type', 'maintenance');
            $svcCats        = $allCategories->where('type', 'service');
            $catExistsInList = $allCategories->contains(fn($c) => strcasecmp($c->name, (string) $aiTriage->suggested_category) === 0);
        @endphp
        <div class="ai-compact-bar p-3.5 mb-3" style="border-color:{{ $isTriagePending ? 'rgba(201,162,74,0.45)' : 'rgba(246,241,231,0.1)' }};">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2.5 min-w-0 flex-grow-1">
                    <span style="width:32px; height:32px; border-radius:9px; background:rgba(201,162,74,0.14); border:1px solid rgba(201,162,74,0.3); display:inline-flex; align-items:center; justify-content:center; flex-shrink:0;">
                        <i class="bi bi-stars text-gold"></i>
                    </span>
                    <div class="min-w-0 flex-grow-1">
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <span style="font-weight:700; font-size:0.86rem; color:#F6F1E7;">Madi AI Triage</span>
                            @if($aiTriage->status === 'accepted')
                                <span class="badge badge-status-resolved" style="font-size:0.65rem;">
                                    <i class="bi bi-check-circle-fill me-1"></i>Accepted by {{ $aiTriage->reviewedBy?->name ?? 'Staff' }}
                                </span>
                            @elseif($aiTriage->status === 'modified')
                                <span class="badge badge-status-dept" style="font-size:0.65rem;">
                                    <i class="bi bi-sliders me-1"></i>Overridden by {{ $aiTriage->reviewedBy?->name ?? 'Staff' }}
                                </span>
                            @else
                                <span class="badge badge-status-high" style="font-size:0.65rem;">
                                    <i class="bi bi-hourglass-split me-1"></i>Needs Review
                                </span>
                            @endif

                            @if(!$isTriagePending)
                                @if($aiInSync)
                                    <span style="font-size:0.68rem; color:#6ee7b7; font-weight:600;">
                                        <i class="bi bi-check2-all me-0.5"></i>Synced with ticket
                                    </span>
                                @else
                                    <span style="font-size:0.68rem; color:#fcd34d; font-weight:600;">
                                        <i class="bi bi-exclamation-circle me-0.5"></i>Ticket edited manually
                                    </span>
                                @endif
                            @endif
                        </div>

                        {{-- 1-line rationale preview ONLY while details panel is collapsed (eliminates duplicate text) --}}
                        @if(!$isTriagePending && $aiTriage->reason)
                        <div id="aiRationalePreview" class="text-truncate mt-0.5" style="font-size:0.74rem; color:#98A0AE;">
                            {{ $aiTriage->reason }}
                        </div>
                        @endif
                    </div>
                </div>

                <div class="d-flex align-items-center gap-2 shrink-0">
                    @if($isTriagePending)
                    <form method="POST" action="{{ route('requests.ai-review', $serviceRequest) }}" class="m-0">
                        @csrf
                        <input type="hidden" name="action" value="accept">
                        <input type="hidden" name="suggested_category" value="{{ $aiTriage->suggested_category }}">
                        <input type="hidden" name="suggested_priority" value="{{ ucfirst($triagePrio) }}">
                        <input type="hidden" name="suggested_department" value="{{ $aiTriage->suggested_department }}">
                        <button type="submit" class="btn-gold px-3 py-1.5 d-inline-flex align-items-center gap-1.5" style="font-size:0.76rem; font-weight:700; border-radius:9px;">
                            <i class="bi bi-check-circle-fill"></i> Accept AI Triage
                        </button>
                    </form>
                    @endif
                    <button type="button" id="btnToggleAiDetails" class="btn-ghost px-2.5 py-1.5 d-inline-flex align-items-center gap-1.5"
                            data-bs-toggle="collapse" data-bs-target="#aiTriageDetailsPanel"
                            aria-expanded="{{ $isTriagePending ? 'true' : 'false' }}"
                            onclick="const el=document.getElementById('aiTriageDetailsPanel'); const pv=document.getElementById('aiRationalePreview'); if(el && typeof bootstrap==='undefined'){ el.classList.toggle('show'); if(pv) pv.classList.toggle('d-none', el.classList.contains('show')); }"
                            style="font-size:0.74rem; border-radius:9px;">
                        <i class="bi bi-sliders"></i>
                        <span>{{ $isTriagePending ? 'Override' : 'Details / Override' }}</span>
                    </button>
                </div>
            </div>

            {{-- Compact Recommendation Pills + Live Rationale + Exact-DB Override Form --}}
            <div class="collapse {{ $isTriagePending ? 'show' : '' }} mt-3 pt-3 border-top" id="aiTriageDetailsPanel" style="border-color:rgba(246,241,231,0.08) !important;">
                <div class="d-flex flex-wrap align-items-center gap-2 mb-2.5">
                    <span class="ai-param-pill" style="{{ $catDiffers ? 'border-color:rgba(201,162,74,0.45);' : '' }}">
                        <span style="color:#7E8899;">Category:</span>
                        @if($catDiffers)
                            <span style="color:#7E8899; text-decoration:line-through; font-size:0.71rem;">{{ $curCatName }}</span>
                            <i class="bi bi-arrow-right text-gold" style="font-size:0.65rem;"></i>
                        @endif
                        <strong style="color:#F6F1E7;">{{ $aiTriage->suggested_category }}</strong>
                    </span>
                    <span class="ai-param-pill" style="{{ $prioDiffers ? 'border-color:rgba(201,162,74,0.45);' : '' }}">
                        <span style="color:#7E8899;">Priority:</span>
                        @if($prioDiffers)
                            <span style="color:#7E8899; text-decoration:line-through; font-size:0.71rem;">{{ ucfirst($curPrioVal) }}</span>
                            <i class="bi bi-arrow-right text-gold" style="font-size:0.65rem;"></i>
                        @endif
                        <strong style="color:{{ $triagePrio === 'urgent' ? '#fda4af' : ($triagePrio === 'high' ? '#fcd34d' : ($triagePrio === 'low' ? '#94a3b8' : '#93c5fd')) }};">{{ ucfirst($triagePrio) }}</strong>
                    </span>
                    <span class="ai-param-pill" style="{{ $deptDiffers ? 'border-color:rgba(201,162,74,0.45);' : '' }}">
                        <span style="color:#7E8899;">Queue:</span>
                        @if($deptDiffers)
                            <span style="color:#7E8899; text-decoration:line-through; font-size:0.71rem;">{{ $curDeptVal }}</span>
                            <i class="bi bi-arrow-right text-gold" style="font-size:0.65rem;"></i>
                        @endif
                        <strong style="color:#E6CA7A;">{{ $aiTriage->suggested_department }}</strong>
                    </span>
                    <span id="liveAiContextSyncBadge" class="ms-auto" style="font-size:0.68rem; color:#8C94A4;">
                        <i class="bi bi-arrow-repeat me-1 text-gold"></i>Auto-syncs with chat
                    </span>
                </div>

                @if($aiTriage->reason)
                <div id="liveAiContextBox" class="p-2.5 rounded-3 mb-3" style="background:rgba(8,13,24,0.72); border:1px solid rgba(201,162,74,0.22); border-left:3px solid #C9A24A; transition:box-shadow 0.4s ease, border-color 0.4s ease;">
                    <div id="liveAiContextText" style="font-size:0.8rem; line-height:1.55; color:#E7E2D7;">
                        "{{ $aiTriage->reason }}"
                    </div>
                </div>
                @endif

                {{-- Exact System Override Form (Active DB Categories + Exact 3 System Queues) --}}
                @if($serviceRequest->status !== 'closed')
                <form method="POST" action="{{ route('requests.ai-review', $serviceRequest) }}" id="modifyAI" class="p-3 rounded-3" style="background:rgba(8,13,24,0.82); border:1px solid rgba(246,241,231,0.09);">
                    @csrf
                    <input type="hidden" name="action" value="modify">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span style="font-size:0.68rem; letter-spacing:0.09em; text-transform:uppercase; color:#A8A397; font-weight:700;">
                            Adjust Triage &amp; Apply to Ticket
                        </span>
                        <span style="font-size:0.66rem; color:#7E8899;">Updates category, priority &amp; queue together</span>
                    </div>
                    <div class="row g-2 align-items-end">
                        <div class="col-md-4 col-12">
                            <label class="form-label mb-1" style="font-size:0.7rem; color:#A8A397; font-weight:600;">Category</label>
                            <select name="suggested_category" class="form-select form-select-sm form-dark-control">
                                @if(!$catExistsInList && !empty($aiTriage->suggested_category))
                                    <option value="{{ $aiTriage->suggested_category }}" selected>{{ $aiTriage->suggested_category }}</option>
                                @endif
                                @if($maintCats->isNotEmpty())
                                <optgroup label="Maintenance Categories">
                                    @foreach($maintCats as $cat)
                                    <option value="{{ $cat->name }}" {{ strcasecmp($aiTriage->suggested_category ?? '', $cat->name) === 0 ? 'selected' : '' }}>
                                        {{ $cat->name }}
                                    </option>
                                    @endforeach
                                </optgroup>
                                @endif
                                @if($svcCats->isNotEmpty())
                                <optgroup label="Service &amp; Concierge Categories">
                                    @foreach($svcCats as $cat)
                                    <option value="{{ $cat->name }}" {{ strcasecmp($aiTriage->suggested_category ?? '', $cat->name) === 0 ? 'selected' : '' }}>
                                        {{ $cat->name }}
                                    </option>
                                    @endforeach
                                </optgroup>
                                @endif
                            </select>
                        </div>
                        <div class="col-md-3 col-6">
                            <label class="form-label mb-1" style="font-size:0.7rem; color:#A8A397; font-weight:600;">Priority</label>
                            <select name="suggested_priority" class="form-select form-select-sm form-dark-control">
                                @foreach(['Low','Medium','High','Urgent'] as $p)
                                <option value="{{ $p }}" {{ strcasecmp($aiTriage->suggested_priority ?? '', $p) === 0 ? 'selected' : '' }}>{{ $p }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 col-6">
                            <label class="form-label mb-1" style="font-size:0.7rem; color:#A8A397; font-weight:600;">Department Queue</label>
                            <select name="suggested_department" class="form-select form-select-sm form-dark-control">
                                @foreach($canonicalDepts as $d)
                                <option value="{{ $d }}" {{ strcasecmp($aiTriage->suggested_department ?? '', $d) === 0 ? 'selected' : '' }}>{{ $d }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2 col-12">
                            <button type="submit" class="btn-gold w-100 py-1.5 d-inline-flex align-items-center justify-content-center gap-1" style="font-size:0.76rem; font-weight:700; border-radius:9px; height:36px;">
                                <i class="bi bi-check2"></i> Apply
                            </button>
                        </div>
                    </div>
                </form>
                @endif
            </div>
        </div>
        @endif

        {{-- 3. Staff Status & Assignment Console (Front Office & Admin Only — Zero Clutter) --}}
        <div class="table-card p-4 mb-3">
            <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom flex-wrap gap-2" style="border-color:rgba(246,241,231,0.08) !important;">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-sliders2-vertical text-gold"></i>
                    <h2 style="font-family:'Playfair Display',serif; font-size:1.08rem; color:#F6F1E7; margin:0;">
                        {{ $isEventInquiry ? 'Event Hold & Assignment' : 'Status & Assignment' }}
                    </h2>
                </div>
                <span style="font-size:0.7rem; color:#7E8899;">{{ $isEventInquiry ? 'Manual FO confirmation only' : 'Updates Guest Tracker live' }}</span>
            </div>

            @php
                $isClosed = $serviceRequest->status === 'closed';
                $isAdmin = auth()->user()->hasRole('admin');
            @endphp

            @if($isClosed && !$isAdmin)
            <div class="p-3 rounded-3" style="background:rgba(8,13,24,0.8); border:1px solid rgba(148,163,184,0.24); border-left:3px solid #94a3b8;">
                <div class="d-flex align-items-center gap-2 mb-1" style="font-weight:700; color:#E6CA7A; font-size:0.86rem;">
                    <i class="bi bi-lock-fill text-warning"></i> Ticket Closed &amp; Archived
                </div>
                <p class="mb-0" style="font-size:0.78rem; color:#98A0AE; line-height:1.45;">
                    Closed tickets remain locked per hotel audit policy. Only an Administrator can re-open a closed ticket.
                </p>
            </div>
            @else

            @if($isClosed && $isAdmin)
            <div class="alert alert-warning py-2 px-3 mb-3 d-flex align-items-center gap-2" style="font-size:0.78rem; border-radius:10px; background:rgba(245,158,11,0.12); border:1px solid rgba(245,158,11,0.35); color:#fef08a;">
                <i class="bi bi-shield-lock-fill text-warning"></i>
                <span><strong>Administrator Mode:</strong> Ticket is closed; you may re-open or adjust parameters.</span>
            </div>
            @endif

            <form id="requestStatusForm" method="POST" action="{{ route('requests.status', $serviceRequest) }}" onsubmit="return handleStatusFormSubmit(event)">
                @csrf
                <input type="hidden" id="currentDbStatus" value="{{ $serviceRequest->status }}">
                <div class="row g-2.5">
                    <div class="col-md-3 col-sm-6">
                        <label class="form-label mb-1" style="font-size:0.72rem; font-weight:700; color:#A8A397;">
                            {{ $isEventInquiry ? 'Event Stage' : 'Stage' }}
                        </label>
                        @php
                            $reqRanks = \App\Services\StatusProgressionService::REQUEST_RANKS;
                            $curReqRank = $reqRanks[$serviceRequest->status] ?? 0;
                            $statusOptionLabels = $isEventInquiry
                                ? [
                                    'pending'     => 'Inquiry Received',
                                    'assigned'    => 'Tentative Hold',
                                    'in_progress' => 'FO Appointment',
                                    'resolved'    => 'Booking Confirmed',
                                    'closed'      => 'Closed / Archived',
                                ]
                                : [
                                    'pending'     => 'Pending Triage',
                                    'assigned'    => 'Assigned',
                                    'in_progress' => 'In Progress',
                                    'resolved'    => 'Resolved',
                                    'closed'      => 'Closed',
                                ];
                        @endphp
                        <select name="status" id="workflowStatusSelect" class="form-select form-dark-control" onchange="previewStatusChangeWarning(this.value)">
                            @foreach($statusOptionLabels as $s => $lbl)
                            @php
                                $optRank = $reqRanks[$s] ?? 0;
                                $isBackOpt = (!$isClosed && $optRank < $curReqRank);
                            @endphp
                            <option value="{{ $s }}" {{ $serviceRequest->status === $s ? 'selected' : '' }} {{ $isBackOpt ? 'disabled' : '' }}>
                                {{ $lbl }}{{ $isBackOpt ? ' (Locked)' : '' }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3 col-sm-6">
                        <label class="form-label mb-1" style="font-size:0.72rem; font-weight:700; color:#A8A397;">Priority</label>
                        <select name="priority" class="form-select form-dark-control">
                            <option value="">Standard</option>
                            @foreach(['Low' => 'Low', 'Medium' => 'Medium', 'High' => 'High', 'Urgent' => 'Urgent'] as $p => $plbl)
                            <option value="{{ $p }}" {{ strcasecmp($serviceRequest->priority ?? '', $p) === 0 ? 'selected' : '' }}>{{ $plbl }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3 col-sm-6">
                        <label class="form-label mb-1" style="font-size:0.72rem; font-weight:700; color:#A8A397;">Department Queue</label>
                        @php
                            $sysDepts = ['Front Desk', 'Maintenance', 'Housekeeping'];
                            $currDept = $serviceRequest->department ?? 'Front Desk';
                            if (!in_array($currDept, $sysDepts, true)) {
                                $sysDepts[] = $currDept;
                            }
                        @endphp
                        <select name="department" class="form-select form-dark-control">
                            @foreach($sysDepts as $d)
                            <option value="{{ $d }}" {{ $currDept === $d ? 'selected' : '' }}>{{ $d }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3 col-sm-6">
                        <label class="form-label mb-1" style="font-size:0.72rem; font-weight:700; color:#A8A397;">Assign Staff</label>
                        <select name="assigned_to" class="form-select form-dark-control">
                            <option value="">— Unassigned —</option>
                            @php
                                $staffList = $assignableStaff ?? ($maintenanceStaff ?? collect([]));
                            @endphp
                            @foreach($staffList as $staffMember)
                            <option value="{{ $staffMember->id }}" {{ (int)($serviceRequest->assigned_to ?? 0) === (int)$staffMember->id ? 'selected' : '' }}>
                                {{ $staffMember->name }}{{ $staffMember->department ? ' (' . $staffMember->department . ')' : '' }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12">
                        <div class="d-flex flex-column flex-md-row gap-2 align-items-md-end mt-1">
                            <div class="flex-grow-1">
                                <label class="form-label mb-1" style="font-size:0.72rem; font-weight:700; color:#A8A397;">Update Note (Optional)</label>
                                <input type="text" name="notes" class="form-control form-dark-control" placeholder="e.g. Dispatched staff to Room {{ $serviceRequest->room?->room_number ?? '201' }} or confirmed appointment time...">
                            </div>
                            <button type="submit" id="btnSubmitStatus" class="btn-gold px-4 py-2 d-inline-flex align-items-center justify-content-center gap-1.5 text-nowrap" style="font-size:0.82rem; font-weight:700; border-radius:10px; height:38px;">
                                <i class="bi bi-check2-circle"></i> Save Updates
                            </button>
                        </div>
                        <div id="statusInlineNotice" class="mt-1.5" style="font-size:0.68rem; color:#7E8899;">
                            @if($isEventInquiry)
                                Manual FO control · Event holds never auto-advance — finalize in person at Lobby Front Desk.
                            @else
                                Forward-only workflow · Auto-stages every 30m up to In Progress (manual confirmation to Resolve).
                            @endif
                        </div>
                    </div>
                </div>
            </form>
            @endif
        </div>
        @endif
    </div>

    {{-- Right Column: Slim Room Actions + Scannable Operations Timeline --}}
    <div class="col-xl-4 col-12">
        {{-- Slim Room Quick-Actions Card (Zero Duplicate Dept/Personnel Boxes) --}}
        <div class="table-card p-3.5 mb-3">
            <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
                <div class="d-flex align-items-center gap-2.5 min-w-0">
                    <div style="width:42px; height:42px; border-radius:11px; background:rgba(201,162,74,0.14); border:1px solid rgba(201,162,74,0.3); display:flex; align-items:center; justify-content:center; font-weight:800; color:#E6CA7A; font-size:0.98rem; flex-shrink:0;">
                        {{ $serviceRequest->room?->room_number ?? '201' }}
                    </div>
                    <div class="min-w-0">
                        <div class="text-truncate" style="font-weight:700; color:#F6F1E7; font-size:0.92rem;">
                            Room {{ $serviceRequest->room?->room_number ?? '201' }}
                        </div>
                        <div style="font-size:0.72rem; color:#8C94A4;">
                            {{ $serviceRequest->room?->room_type ?? 'Standard' }} &middot; Floor {{ $serviceRequest->room?->floor ?? 1 }}
                        </div>
                    </div>
                </div>
                <span class="badge" style="background:rgba(34,197,94,0.12); border:1px solid rgba(34,197,94,0.28); color:#86efac; font-size:0.66rem;">
                    Active Stay
                </span>
            </div>

            <div class="d-grid gap-2">
                @if($isFrontOffice)
                <button type="button" onclick="toggleFloatingStaffChat(true);" class="btn-gold py-2 px-3 d-inline-flex align-items-center justify-content-center gap-2" style="font-size:0.78rem; font-weight:700; border-radius:10px;">
                    <i class="bi bi-chat-dots-fill"></i>
                    <span>Open Live Guest Chat</span>
                </button>
                @endif
                <div class="d-flex gap-1.5">
                    @if($isFrontOffice)
                    <a href="{{ route('guest.landing', $serviceRequest->room?->qr_token ?? 'demo') }}" target="_blank" class="btn-ghost flex-fill text-center py-1.5 px-2 text-decoration-none text-nowrap" style="font-size:0.72rem; border-radius:8px;">
                        <i class="bi bi-phone me-1"></i>Portal
                    </a>
                    @endif
                    <a href="{{ route('requests.index', ['room' => $serviceRequest->room?->room_number ?? '201']) }}" class="btn-ghost flex-fill text-center py-1.5 px-2 text-decoration-none text-nowrap" style="font-size:0.72rem; border-radius:8px;">
                        <i class="bi bi-clock-history me-1"></i>History
                    </a>
                    @if($serviceRequest->room && auth()->user()->hasAnyRole(['admin', 'owner', 'front_desk']))
                    <a href="{{ route('admin.rooms.folio', $serviceRequest->room) }}" class="btn-ghost flex-fill text-center py-1.5 px-2 text-decoration-none text-nowrap" style="font-size:0.72rem; border-radius:8px; color:#E6CA7A; border-color:rgba(201,162,74,0.32);">
                        <i class="bi bi-receipt-cutoff me-1"></i>Folio
                    </a>
                    @endif
                </div>
            </div>
        </div>

        {{-- Compact Scrollable Operations Timeline --}}
        <div class="table-card p-3.5">
            <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom" style="border-color:rgba(246,241,231,0.08) !important;">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-clock-history text-gold"></i>
                    <h2 style="font-family:'Playfair Display',serif; font-size:0.98rem; color:#F6F1E7; margin:0;">
                        Operations Timeline
                    </h2>
                </div>
                <span style="font-size:0.68rem; color:#7E8899;">Live Audit</span>
            </div>

            <div class="timeline-scroll">
                @foreach($serviceRequest->statusLogs->sortByDesc('created_at') as $log)
                @if($log->from_status === $log->to_status && empty($log->notes))
                    @continue
                @endif
                <div class="timeline-node">
                    <div class="timeline-dot {{ in_array($log->to_status, ['resolved', 'closed']) ? 'success' : '' }}"></div>
                    <div style="font-size:0.78rem; font-weight:700; color:#F6F1E7;">
                        {{ ($log->from_status && $log->from_status !== $log->to_status) ? ucwords(str_replace('_',' ',$log->from_status)) . ' → ' : '' }}
                        <span style="color:#E6CA7A;">{{ ucwords(str_replace('_',' ',$log->to_status)) }}</span>
                    </div>
                    @if($log->notes)
                    <div style="font-size:0.73rem; color:#CBD5E1; margin-top:0.22rem; background:rgba(255,255,255,0.03); padding:0.32rem 0.55rem; border-radius:6px; border-left:2px solid rgba(201,162,74,0.55);">
                        {{ $log->notes }}
                    </div>
                    @endif
                    <div style="font-size:0.66rem; color:#7E8899; margin-top:0.22rem;">
                        {{ $log->changedBy?->name ?? 'System' }} &bull; {{ $log->created_at->format('g:i A') }} ({{ $log->created_at->diffForHumans() }})
                    </div>
                </div>
                @endforeach

                @if($serviceRequest->aiTriage)
                <div class="timeline-node">
                    <div class="timeline-dot ai"></div>
                    <div style="font-size:0.78rem; font-weight:700; color:#E6CA7A;">
                        Madi AI Triage Completed
                    </div>
                    <div style="font-size:0.72rem; color:#98A0AE; margin-top:0.15rem;">
                        Suggested <strong style="color:#E7E2D7;">{{ ucfirst(strtolower($serviceRequest->aiTriage->suggested_priority ?? 'Medium')) }}</strong> &middot; <strong style="color:#E7E2D7;">{{ $serviceRequest->aiTriage->suggested_department }}</strong>
                    </div>
                    <div style="font-size:0.66rem; color:#7E8899; margin-top:0.18rem;">
                        Automated &bull; {{ $serviceRequest->aiTriage->created_at->format('g:i A') }}
                    </div>
                </div>
                @endif

                <div class="timeline-node">
                    <div class="timeline-dot {{ $prioLower === 'urgent' ? 'urgent' : '' }}"></div>
                    <div style="font-size:0.78rem; font-weight:700; color:#F6F1E7;">
                        Ticket Submitted by Guest
                    </div>
                    <div style="font-size:0.72rem; color:#98A0AE; margin-top:0.15rem;">
                        Room {{ $serviceRequest->room?->room_number ?? '201' }} QR Code &bull; {{ $serviceRequest->created_at->format('M d, g:i A') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Status Confirmation Warning Modal --}}
<div class="modal fade" id="statusConfirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background:#0F1B2D; border:1px solid rgba(201,168,76,0.4); border-radius:14px; box-shadow:0 15px 40px rgba(0,0,0,0.6);">
            <div class="modal-header pb-2" style="border-color:rgba(201,168,76,0.25);">
                <div class="d-flex align-items-center gap-2">
                    <i id="modalWarningIcon" class="bi bi-exclamation-triangle text-warning" style="font-size:1.15rem;"></i>
                    <h5 class="modal-title font-serif" style="color:#C9A24A; font-size:1.1rem; margin:0;" id="modalStatusTitle">
                        Confirm Status Change
                    </h5>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-3">
                <div class="p-3 rounded-3 mb-3" id="modalAlertBox" style="background:rgba(255,255,255,0.04); border:1px solid rgba(201,168,76,0.25);">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span style="font-size:0.75rem; color:#8899AA; text-transform:uppercase;">Workflow Transition</span>
                        <span id="modalTransitionBadge" class="badge" style="background:rgba(201,168,76,0.2); color:#E8D08D; font-size:0.75rem;">
                            {{ $serviceRequest->status }} &rarr; Target
                        </span>
                    </div>
                    <div id="modalWarningMessage" style="font-size:0.85rem; color:#F8FAFC; line-height:1.5;">
                        Warning text goes here.
                    </div>
                </div>
                <div style="font-size:0.75rem; color:#94a3b8; line-height:1.4;">
                    <i class="bi bi-info-circle me-1 text-warning"></i>
                    <span id="modalGuestImpactText">The guest tracking portal and hotel operations queue will reflect this change immediately.</span>
                </div>
            </div>
            <div class="modal-footer pt-2" style="border-color:rgba(201,168,76,0.2);">
                <button type="button" class="btn-ghost px-3 py-1.5" data-bs-dismiss="modal" style="font-size:0.82rem; border-radius:8px;">
                    Cancel
                </button>
                <button type="button" id="btnConfirmStatusChange" class="btn-gold px-4 py-1.5" style="font-size:0.82rem; border-radius:8px; font-weight:700;">
                    Confirm &amp; Apply Status
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Clear / Delete Conversation Confirmation Modal --}}
<div class="modal fade" id="deleteChatModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background:#0F1B2D; border:1px solid rgba(239,68,68,0.4); border-radius:14px; box-shadow:0 15px 40px rgba(0,0,0,0.6);">
            <div class="modal-header pb-2" style="border-color:rgba(239,68,68,0.25);">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-trash3 text-danger" style="font-size:1.1rem;"></i>
                    <h5 class="modal-title font-serif" style="color:#f87171; font-size:1.1rem; margin:0;">
                        Clear Ticket Conversation
                    </h5>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-3">
                <div class="p-3 rounded-3 mb-3" style="background:rgba(239,68,68,0.08); border:1px solid rgba(239,68,68,0.25);">
                    <div style="font-size:0.88rem; color:#F8FAFC; line-height:1.5; font-weight:600;">
                        Are you sure you want to permanently delete all messages for Room {{ $serviceRequest->room?->room_number ?? '201' }}?
                    </div>
                    <div style="font-size:0.75rem; color:#fca5a5; margin-top:0.35rem;">
                        This will wipe the chat history from both the staff console and the guest's mobile tracker.
                    </div>
                </div>
                <div style="font-size:0.75rem; color:#8899AA;">
                    <i class="bi bi-info-circle me-1 text-warning"></i>
                    Ticket details, priority level, and operations audit log will remain intact.
                </div>
            </div>
            <div class="modal-footer pt-2" style="border-color:rgba(255,255,255,0.08);">
                <button type="button" class="btn-ghost px-3 py-1.5" data-bs-dismiss="modal" style="font-size:0.82rem; border-radius:8px;">
                    Cancel
                </button>
                <button type="button" id="btnConfirmDeleteChat" onclick="executeDeleteConversation()" class="btn btn-danger px-4 py-1.5" style="font-size:0.82rem; border-radius:8px; font-weight:700;">
                    <i class="bi bi-trash3 me-1"></i> Delete Conversation
                </button>
            </div>
        </div>
    </div>
</div>

@if($isFrontOffice)
{{-- Floating Bottom-Right Live Guest Chat Widget (No Scrolling Required) --}}
@php
    $initialMessages = $serviceRequest->messages ?? collect([]);
    $initialMsgCount = $initialMessages->count();
    $hasCancelIntent = $serviceRequest->status !== 'closed'
        && str_starts_with((string) ($serviceRequest->aiTriage?->reason ?? ''), '[Cancellation Requested]');
@endphp
<style>
    .floating-chat-dock {
        position: fixed;
        right: 22px;
        bottom: 20px;
        z-index: 1040;
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: 10px;
    }
    .floating-chat-window {
        width: 388px;
        max-width: calc(100vw - 24px);
        height: min(520px, 76dvh);
        background: #0B121F;
        border: 1px solid rgba(201, 162, 74, 0.42);
        border-radius: 16px;
        box-shadow: 0 22px 58px rgba(0, 0, 0, 0.82), 0 0 0 1px rgba(255,255,255,0.04) inset;
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }
    .floating-chat-launcher {
        display: inline-flex;
        align-items: center;
        gap: 0.65rem;
        padding: 0.58rem 0.95rem 0.58rem 0.68rem;
        border-radius: 999px;
        background: linear-gradient(135deg, #131E33 0%, #0B121F 100%);
        border: 1px solid rgba(201, 162, 74, 0.55);
        color: #F6F1E7;
        box-shadow: 0 14px 34px rgba(0, 0, 0, 0.72), 0 0 18px rgba(201, 162, 74, 0.18);
        cursor: pointer;
        transition: border-color 0.2s ease, transform 0.15s ease, box-shadow 0.2s ease;
    }
    .floating-chat-launcher:hover {
        border-color: #E6CA7A;
        box-shadow: 0 16px 38px rgba(0, 0, 0, 0.8), 0 0 24px rgba(201, 162, 74, 0.3);
        transform: translateY(-1px);
    }
    .floating-chat-quick-strip {
        display: flex;
        align-items: center;
        gap: 0.35rem;
        overflow-x: auto;
        scrollbar-width: none;
        padding-bottom: 2px;
    }
    .floating-chat-quick-strip::-webkit-scrollbar { display: none; }
    .floating-quick-chip {
        font-size: 0.69rem;
        white-space: nowrap;
        flex-shrink: 0;
        background: rgba(255,255,255,0.04);
        border: 1px solid rgba(201,168,76,0.22);
        color: #D8C797;
        border-radius: 999px;
        padding: 0.18rem 0.55rem;
        cursor: pointer;
        transition: all 0.15s ease;
    }
    .floating-quick-chip:hover {
        background: rgba(201,168,76,0.16);
        border-color: rgba(201,168,76,0.5);
        color: #fff;
    }
    @media (max-width: 767.98px) {
        .floating-chat-dock { right: 12px; bottom: 72px; }
        .floating-chat-window { width: calc(100vw - 24px); height: min(470px, 68dvh); }
    }
</style>

<div class="floating-chat-dock" id="floatingChatDock">
    {{-- Expanded Floating Chat Window (preserves #staffChatCard ID for deep-links & tests) --}}
    <div id="staffChatCard" class="floating-chat-window {{ $hasCancelIntent ? '' : 'd-none' }}">
        {{-- Top Header Row: Title + Prominent Close Button (Never Overflows) --}}
        <div class="px-3 py-2.5 border-bottom d-flex align-items-center justify-content-between gap-2 flex-shrink-0" style="background:#10192B; border-color:rgba(246,241,231,0.08) !important;">
            <div class="d-flex align-items-center gap-2 min-w-0">
                <span style="width:32px; height:32px; min-width:32px; border-radius:9px; background:rgba(201,162,74,0.14); border:1px solid rgba(201,162,74,0.32); display:inline-flex; align-items:center; justify-content:center; color:#E6CA7A;">
                    <i class="bi bi-chat-dots-fill" style="font-size:0.88rem;"></i>
                </span>
                <div class="min-w-0">
                    <div class="text-truncate" style="font-weight:700; font-size:0.88rem; color:#F6F1E7; line-height:1.15;">
                        Live Guest Chat
                    </div>
                    <div class="text-truncate" style="font-size:0.7rem; color:#8C94A4;">
                        Room {{ $serviceRequest->room?->room_number ?? '201' }} &bull; {{ $serviceRequest->guest_name ?? 'Guest' }}
                    </div>
                </div>
            </div>

            <button type="button" onclick="toggleFloatingStaffChat(false);" class="btn-ghost p-0 d-inline-flex align-items-center justify-content-center flex-shrink-0" style="width:32px; height:32px; border-radius:9px; color:#E7E2D7; border-color:rgba(246,241,231,0.16);" title="Close / Minimize Chat" aria-label="Close Live Guest Chat">
                <i class="bi bi-x-lg" style="font-size:0.82rem;"></i>
            </button>
        </div>

        {{-- Clean Sub-Bar: Madi AI Auto-Responder Toggle + Clear Chat --}}
        <div class="px-3 py-2 border-bottom d-flex align-items-center justify-content-between gap-2 flex-shrink-0" style="background:#0B121F; border-color:rgba(201,162,74,0.16) !important;">
            @if($serviceRequest->status === 'closed')
                <span class="badge" style="background:rgba(148,163,184,0.12); border:1px solid rgba(148,163,184,0.25); color:#cbd5e1; font-size:0.65rem;">
                    <i class="bi bi-lock-fill"></i> Chat Closed &amp; Archived
                </span>
            @else
                <div class="d-inline-flex align-items-center gap-2" title="When ON, Madi AI automatically responds to guest messages">
                    <div class="form-check form-switch m-0 d-inline-flex align-items-center gap-2 p-0">
                        <input class="form-check-input m-0" type="checkbox" role="switch" id="botAutoReplyToggle" {{ $serviceRequest->auto_reply_bot ? 'checked' : '' }} onchange="toggleBotAutoReply(this)" style="cursor:pointer; width:30px; height:16px;">
                        <label class="form-check-label mb-0" for="botAutoReplyToggle" style="cursor:pointer;">
                            <span id="botStatusBadge" class="badge {{ $serviceRequest->auto_reply_bot ? 'badge-status-resolved' : 'badge-status-dept' }}" style="font-size:0.63rem; padding:0.18rem 0.5rem !important;">
                                <i class="bi bi-robot"></i> <span id="botStatusText">{{ $serviceRequest->auto_reply_bot ? 'Madi AI: ON' : 'Madi AI: OFF' }}</span>
                            </span>
                        </label>
                    </div>
                </div>
                <button type="button" onclick="openDeleteChatModal();" class="btn-ghost py-1 px-2 d-inline-flex align-items-center gap-1 text-nowrap flex-shrink-0" style="font-size:0.68rem; border-radius:7px; color:#fca5a5; border-color:rgba(239,68,68,0.28);" title="Delete Conversation">
                    <i class="bi bi-trash3"></i><span>Clear</span>
                </button>
            @endif
        </div>

        {{-- Smart AI Cancel-Intent Alert Banner (Human-in-the-Loop Confirmation) --}}
        <div id="guestCancelRequestAlert" class="{{ $hasCancelIntent ? '' : 'd-none' }} px-3 py-2 border-bottom d-flex align-items-center justify-content-between gap-2 flex-shrink-0" style="background:rgba(239,68,68,0.14); border-color:rgba(239,68,68,0.4) !important;">
            <div class="min-w-0">
                <div style="font-size:0.75rem; font-weight:700; color:#fecaca;">
                    <i class="bi bi-exclamation-octagon-fill text-danger me-1"></i>Guest requested cancellation
                </div>
            </div>
            <form method="POST" action="{{ route('requests.status', $serviceRequest) }}" class="m-0 flex-shrink-0">
                @csrf
                <input type="hidden" name="status" value="closed">
                <input type="hidden" name="notes" value="Ticket cancelled & closed per guest chat request by {{ auth()->user()->name }}.">
                <button type="submit" class="btn btn-sm btn-danger px-2.5 py-1 text-nowrap" style="font-size:0.68rem; font-weight:700; border-radius:8px;">
                    Confirm Cancel
                </button>
            </form>
        </div>

        {{-- Clean Scrollable Message Stream --}}
        <div id="staffChatThread" class="p-3 flex-grow-1" style="background:#070C15; overflow-y:auto; display:flex; flex-direction:column; gap:0.7rem;">
            @forelse($initialMessages as $msg)
                @if($msg->sender_type === 'staff')
                @php
                    $isAiBot = str_contains($msg->sender_name ?? '', 'AI') || str_contains($msg->sender_name ?? '', 'Auto-Reply');
                @endphp
                <div class="chat-msg-row d-flex flex-column align-items-end" data-staff-msg-id="{{ $msg->id }}">
                    <div style="font-size:0.65rem; color:#93c5fd; margin-bottom:2px; display:flex; align-items:center; gap:4px;">
                        @if($isAiBot)
                        <span class="badge" style="background:rgba(201,168,76,0.2); border:1px solid rgba(201,168,76,0.42); color:#E8D08D; font-size:0.58rem !important; padding:0.12rem 0.4rem !important;">
                            <i class="bi bi-robot"></i> {{ $msg->sender_name }}
                        </span>
                        @else
                        <span>{{ $msg->sender_name ?: 'Staff' }}</span>
                        @endif
                        &bull; {{ $msg->formatted_time ?: $msg->time_ago }}
                    </div>
                    <div style="{{ $isAiBot ? 'background:rgba(201,168,76,0.14); border:1px solid rgba(201,168,76,0.42);' : 'background:rgba(37,99,235,0.22); border:1px solid rgba(96,165,250,0.38);' }} color:#F8FAFC; border-radius:12px 12px 2px 12px; padding:0.5rem 0.78rem; font-size:0.8rem; line-height:1.45; max-width:86%; word-break:break-word;">
                        {{ $msg->message }}
                    </div>
                </div>
                @else
                <div class="chat-msg-row d-flex flex-column align-items-start" data-staff-msg-id="{{ $msg->id }}">
                    <div style="font-size:0.65rem; color:#E8D08D; margin-bottom:2px; display:flex; align-items:center; gap:4px;">
                        <span style="background:rgba(201,168,76,0.15); border:1px solid rgba(201,168,76,0.3); border-radius:4px; padding:1px 5px; font-size:0.6rem; color:#E8D08D;">{{ $msg->sender_name ?: ('Guest · Room ' . ($serviceRequest->room?->room_number ?? '201')) }}</span> &bull; {{ $msg->formatted_time ?: $msg->time_ago }}
                    </div>
                    <div style="background:rgba(255,255,255,0.06); border:1px solid rgba(246,241,231,0.12); color:#F8FAFC; border-radius:12px 12px 12px 2px; padding:0.5rem 0.78rem; font-size:0.8rem; line-height:1.45; max-width:86%; word-break:break-word;">
                        {{ $msg->message }}
                    </div>
                </div>
                @endif
            @empty
                <div id="emptyStaffChatPlaceholder" class="text-center py-4 my-auto" style="color:#7D8696; font-size:0.78rem;">
                    <i class="bi bi-chat-dots d-block mb-2" style="font-size:1.5rem; color:#C9A24A; opacity:0.65;"></i>
                    No messages yet.<br>
                    Tap <strong>Instant Madi Reply</strong> or type below to message the guest.
                </div>
            @endforelse
        </div>

        @if($serviceRequest->status !== 'closed')
        {{-- Compact Composer Footer --}}
        <div class="p-2.5 border-top flex-shrink-0" style="background:#0F1727; border-color:rgba(246,241,231,0.08) !important;">
            {{-- Clean AI Action Row --}}
            <div class="d-flex align-items-center justify-content-between gap-1.5 mb-2">
                <div class="d-flex align-items-center gap-1.5">
                    <button type="button" onclick="triggerAiAutoReply();" id="btnChatAiAutoSend" class="floating-quick-chip" style="background:rgba(201,162,74,0.2); border-color:#C9A24A; color:#F6F1E7; font-weight:600; padding:0.24rem 0.65rem;" title="Generate Madi AI reply and send directly">
                        <i class="bi bi-lightning-charge-fill text-gold" id="chatAiAutoSendIcon"></i> <span id="chatAiAutoSendText">Instant Madi Reply</span>
                    </button>
                    <button type="button" onclick="triggerAiDraft();" id="btnChatAiDraft" class="floating-quick-chip" style="padding:0.24rem 0.6rem;" title="Draft Madi AI reply into input box">
                        <i class="bi bi-pencil-square" id="chatAiDraftIcon"></i> <span id="chatAiDraftText">Draft</span>
                    </button>
                </div>
                <div class="d-flex align-items-center gap-1">
                    <button type="button" class="floating-quick-chip" onclick="setStaffDraft('Good day! Our team has been dispatched and will arrive at your room in 5 to 10 minutes.')" title="Quick snippet: Dispatched">
                        5-10m
                    </button>
                    <button type="button" class="floating-quick-chip" onclick="setStaffDraft('Your request has been completed and inspected. Please let us know if you need anything else!')" title="Quick snippet: Completed">
                        Done
                    </button>
                </div>
            </div>

            <form id="staffChatForm" onsubmit="handleSendStaffMessage(event);" class="d-flex align-items-end gap-1.5">
                <textarea id="staffMessageInput" class="form-control form-dark-control" rows="2" placeholder="Message guest..." maxlength="1000" style="resize:none; font-size:0.82rem; line-height:1.45; min-height:64px !important; max-height:140px; padding:0.55rem 0.75rem; overflow-y:auto;" oninput="autoResizeStaffInput(this)" onkeydown="if(event.key === 'Enter' && !event.shiftKey) { event.preventDefault(); handleSendStaffMessage(event); }"></textarea>
                <button type="submit" id="staffSendBtn" class="btn-gold d-inline-flex align-items-center justify-content-center flex-shrink-0" style="width:42px; height:42px; padding:0 !important; border-radius:10px;" title="Send message">
                    <i class="bi bi-send-fill" style="font-size:0.95rem;"></i>
                </button>
            </form>
        </div>
        @else
        <div class="px-3 py-2.5 text-center border-top flex-shrink-0" style="background:#0F1727; border-color:rgba(246,241,231,0.08) !important; font-size:0.74rem; color:#8C94A4;">
            <i class="bi bi-lock-fill text-gold me-1"></i>Ticket closed — chat history archived.
        </div>
        @endif
    </div>

    {{-- Floating Minimized Launcher Pill --}}
    <button type="button" id="staffChatLauncher" class="floating-chat-launcher {{ $hasCancelIntent ? 'd-none' : '' }}" onclick="toggleFloatingStaffChat(true);" aria-label="Open Live Guest Chat">
        <span style="width:32px; height:32px; border-radius:50%; background:var(--mp-metal, linear-gradient(135deg,#B89135,#E6CA7A)); color:#140F04; display:inline-flex; align-items:center; justify-content:center; flex-shrink:0;">
            <i class="bi bi-chat-dots-fill" style="font-size:0.92rem;"></i>
        </span>
        <span class="text-start">
            <span class="d-block" style="font-size:0.8rem; font-weight:700; line-height:1.15; color:#F6F1E7;">Live Guest Chat</span>
            <span class="d-block" style="font-size:0.67rem; color:#A8A397;">Room {{ $serviceRequest->room?->room_number ?? '201' }} &middot; <span id="floatingChatMsgCount">{{ $initialMsgCount }}</span> msg(s)</span>
        </span>
        <span id="floatingChatUnreadBadge" class="badge bg-danger d-none" style="font-size:0.62rem; padding:0.18rem 0.48rem !important; border-radius:999px !important;">0</span>
        <i class="bi bi-chevron-up" style="font-size:0.75rem; color:#E6CA7A;"></i>
    </button>
</div>
@endif
@endsection

@push('scripts')
<script>
// ── Staff Live Ticket Chat Synchronizer ──
const staffMessagesUrl = "{{ route('requests.messages', $serviceRequest, false) }}";
const staffMessageStoreUrl = "{{ route('requests.messages.store', $serviceRequest, false) }}";
const staffAiDraftUrl = "{{ route('requests.messages.ai-draft', $serviceRequest, false) }}";
const staffAiAutoReplyUrl = "{{ route('requests.messages.ai-auto-reply', $serviceRequest, false) }}";
const staffToggleBotUrl = "{{ route('requests.toggle-bot', $serviceRequest, false) }}";
const staffDeleteMessagesUrl = "{{ route('requests.messages.delete', $serviceRequest, false) }}";

const knownStaffMsgIds = new Set([
    @if(!empty($serviceRequest->messages))
        @foreach($serviceRequest->messages as $m)
            "{{ $m->id }}",
        @endforeach
    @endif
]);
let isSendingStaffMsg = false;
let isTicketChatPollInFlight = false;

function hasKnownStaffMsgId(id) {
    if (id === undefined || id === null) return false;
    return knownStaffMsgIds.has(String(id)) || knownStaffMsgIds.has(Number(id));
}

function addKnownStaffMsgId(id) {
    if (id === undefined || id === null) return;
    knownStaffMsgIds.add(String(id));
}

function scrollStaffChatToBottom() {
    const el = document.getElementById('staffChatThread');
    if (el) el.scrollTop = el.scrollHeight;
}

function autoResizeStaffInput(el) {
    if (!el) return;
    el.style.height = 'auto';
    el.style.height = Math.min(Math.max(el.scrollHeight, 64), 140) + 'px';
}

function setStaffDraft(txt) {
    const input = document.getElementById('staffMessageInput');
    if (input) {
        input.value = txt;
        autoResizeStaffInput(input);
        input.focus();
    }
}

// ⚡ 1-Click Instant AI Auto-Respond & Send Directly to Guest
function triggerAiAutoReply() {
    const btn = document.getElementById('btnChatAiAutoSend');
    const icon = document.getElementById('chatAiAutoSendIcon');
    const text = document.getElementById('chatAiAutoSendText');
    if (btn) btn.disabled = true;
    if (icon) icon.className = 'spinner-border spinner-border-sm text-gold';
    if (text) text.textContent = 'Generating & Sending...';

    fetch(staffAiAutoReplyUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({})
    })
    .then(r => r.json())
    .then(data => {
        if (btn) btn.disabled = false;
        if (icon) icon.className = 'bi bi-lightning-charge-fill text-gold';
        if (text) text.textContent = 'Instant Madi Reply';

        if (data.success && data.message) {
            addKnownStaffMsgId(data.message.id);
            appendMessageToStaffThread(data.message, true);
            if (typeof playStaffAlertChime === 'function') {
                playStaffAlertChime(false);
            }
        } else if (data.error) {
            alert(data.error);
        }
    })
    .catch(err => {
        if (btn) btn.disabled = false;
        if (icon) icon.className = 'bi bi-lightning-charge-fill text-gold';
        if (text) text.textContent = 'Instant Madi Reply';
        console.error('Instant AI Auto-reply error:', err);
    });
}

// 🤖 Toggle Live Bot Auto-Responder for this ticket
function toggleBotAutoReply(checkbox) {
    if (!checkbox) return;
    checkbox.disabled = true;
    fetch(staffToggleBotUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({})
    })
    .then(r => r.json())
    .then(data => {
        checkbox.disabled = false;
        if (data.success) {
            checkbox.checked = data.auto_reply_bot;
            if (typeof window.syncAccountBotUI === 'function') {
                window.syncAccountBotUI(data.auto_reply_bot);
            }
            const badge = document.getElementById('botStatusBadge');
            const txt = document.getElementById('botStatusText');
            if (badge && txt) {
                if (data.auto_reply_bot) {
                    badge.className = 'badge badge-status-resolved';
                    txt.textContent = 'Madi AI: ON';
                } else {
                    badge.className = 'badge badge-status-dept';
                    txt.textContent = 'Madi AI: OFF';
                }
            }
        }
    })
    .catch(err => {
        checkbox.disabled = false;
        console.error('Toggle bot error:', err);
    });
}

// 🗑️ Delete / Clear Conversation Modal Handlers
function openDeleteChatModal() {
    const el = document.getElementById('deleteChatModal');
    if (el) {
        const modal = bootstrap.Modal.getOrCreateInstance(el);
        modal.show();
    }
}

function executeDeleteConversation() {
    const btn = document.getElementById('btnConfirmDeleteChat');
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Deleting...';
    }

    fetch(staffDeleteMessagesUrl, {
        method: 'DELETE',
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(r => r.json())
    .then(data => {
        const el = document.getElementById('deleteChatModal');
        if (el) {
            const modal = bootstrap.Modal.getInstance(el);
            if (modal) modal.hide();
        }
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-trash3 me-1"></i> Delete Conversation';
        }

        if (data.success) {
            knownStaffMsgIds.clear();
            const thread = document.getElementById('staffChatThread');
            if (thread) {
                thread.innerHTML = `
                    <div id="emptyStaffChatPlaceholder" class="text-center py-4 my-auto" style="color:#8899AA; font-size:0.82rem;">
                        <div style="font-size:1.5rem; margin-bottom:0.3rem;">💬</div>
                        Conversation history deleted.<br>
                        Click <strong>⚡ Instant Madi Reply</strong> or type a message below to greet or update the guest.
                    </div>
                `;
            }
        }
    })
    .catch(err => {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-trash3 me-1"></i> Delete Conversation';
        }
        console.error('Delete conversation error:', err);
    });
}

function triggerAiDraft() {
    const btns = [document.getElementById('btnAiDraftReply'), document.getElementById('btnChatAiDraft')].filter(Boolean);
    const icons = [document.getElementById('aiDraftIcon'), document.getElementById('chatAiDraftIcon')].filter(Boolean);
    const texts = [document.getElementById('aiDraftText'), document.getElementById('chatAiDraftText')].filter(Boolean);
    const input = document.getElementById('staffMessageInput');

    if (!input) return;

    btns.forEach(b => b.disabled = true);
    icons.forEach(ic => ic.innerHTML = '<span class="spinner-border spinner-border-sm" style="width:12px; height:12px;" role="status"></span>');
    texts.forEach(t => t.textContent = 'Drafting AI Reply...');

    fetch(staffAiDraftUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({})
    })
    .then(r => r.json())
    .then(data => {
        btns.forEach(b => b.disabled = false);
        icons.forEach(ic => { ic.className = 'bi bi-pencil-square'; ic.innerHTML = ''; });
        texts.forEach(t => t.textContent = 'Draft');
        if (data.success && data.draft) {
            input.value = data.draft;
            input.focus();
            input.style.borderColor = '#C9A24A';
            input.style.boxShadow = '0 0 15px rgba(201,168,76, 0.45)';
            setTimeout(() => { 
                input.style.borderColor = ''; 
                input.style.boxShadow = '';
            }, 1800);
        }
    })
    .catch(err => {
        btns.forEach(b => b.disabled = false);
        icons.forEach(ic => { ic.className = 'bi bi-pencil-square'; ic.innerHTML = ''; });
        texts.forEach(t => t.textContent = 'Draft');
        console.error('AI Draft failed:', err);
    });
}

function appendMessageToStaffThread(msg, isSelf = false) {
    const thread = document.getElementById('staffChatThread');
    if (!thread) return;

    if (msg.id && thread.querySelector(`[data-staff-msg-id="${msg.id}"]`)) return;

    const placeholder = document.getElementById('emptyStaffChatPlaceholder');
    if (placeholder) placeholder.remove();

    const row = document.createElement('div');
    const isAi = (msg.sender_name && (msg.sender_name.includes('AI') || msg.sender_name.includes('Auto-Reply')));

    if (msg.sender_type === 'staff' || isSelf) {
        row.className = 'chat-msg-row d-flex flex-column align-items-end';
        row.setAttribute('data-staff-msg-id', msg.id || ('tmp_' + Date.now()));
        row.innerHTML = `
            <div class="staff-chat-meta" style="font-size:0.68rem; color:#93c5fd; margin-bottom:2px; display:flex; align-items:center; gap:4px;">
                ${isAi ? `<span class="badge" style="background:rgba(201,168,76,0.22); border:1px solid rgba(201,168,76,0.45); color:#E8D08D; font-size:0.62rem; padding:1px 6px;"><i class="bi bi-robot me-1"></i>${escapeStaffHtml(msg.sender_name)}</span>` : `<span>${escapeStaffHtml(msg.sender_name || 'Staff')}</span>`} &bull; ${escapeStaffHtml(msg.formatted_time || msg.time_ago || 'Just now')}
            </div>
            <div class="${isAi ? 'staff-chat-bubble-ai' : 'staff-chat-bubble-staff'}" style="${isAi ? 'background:rgba(201,168,76,0.14); border:1px solid rgba(201,168,76,0.45);' : 'background:rgba(37,99,235,0.22); border:1px solid rgba(96,165,250,0.4);'} color:#F8FAFC; border-radius:12px 12px 2px 12px; padding:0.6rem 0.95rem; font-size:0.85rem; line-height:1.45; max-width:82%; word-break:break-word;">
                ${escapeStaffHtml(msg.message)}
            </div>
        `;
    } else {
        row.className = 'chat-msg-row d-flex flex-column align-items-start';
        row.setAttribute('data-staff-msg-id', msg.id);
        row.innerHTML = `
            <div class="staff-chat-meta" style="font-size:0.68rem; color:#E8D08D; margin-bottom:2px; display:flex; align-items:center; gap:4px;">
                <span style="background:rgba(201,162,74,0.16); border:1px solid rgba(201,162,74,0.32); border-radius:4px; padding:1px 5px; font-size:0.62rem; color:#E8D08D;"><i class="bi bi-bell me-1"></i>${escapeStaffHtml(msg.sender_name || 'Guest')}</span> &bull; ${escapeStaffHtml(msg.formatted_time || msg.time_ago || 'Just now')}
            </div>
            <div class="staff-chat-bubble-guest" style="background:rgba(201,168,76,0.14); border:1px solid rgba(201,168,76,0.35); color:#F8FAFC; border-radius:12px 12px 12px 2px; padding:0.6rem 0.95rem; font-size:0.85rem; line-height:1.45; max-width:82%; word-break:break-word;">
                ${escapeStaffHtml(msg.message)}
            </div>
        `;
    }
    thread.appendChild(row);
    scrollStaffChatToBottom();
}

function escapeStaffHtml(text) {
    const div = document.createElement('div');
    div.textContent = text ?? '';
    return div.innerHTML;
}

function handleSendStaffMessage(e) {
    if (e) e.preventDefault();
    const input = document.getElementById('staffMessageInput');
    const btn = document.getElementById('staffSendBtn');
    if (!input || !input.value.trim()) return;

    const messageText = input.value.trim();
    input.value = '';
    input.style.height = '64px';
    btn.disabled = true;
    isSendingStaffMsg = true;

    const tempId = 'temp_staff_' + Date.now();
    appendMessageToStaffThread({
        id: tempId,
        sender_type: 'staff',
        sender_name: '{{ addslashes(auth()->user()->name) }}',
        message: messageText,
        formatted_time: 'Just now'
    }, true);

    fetch(staffMessageStoreUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ message: messageText })
    })
    .then(async r => {
        const data = await r.json().catch(() => null);
        btn.disabled = false;
        isSendingStaffMsg = false;
        if (r.ok && data && data.success && data.message) {
            addKnownStaffMsgId(data.message.id);
            const tempEl = document.querySelector(`[data-staff-msg-id="${tempId}"]`);
            if (tempEl) tempEl.setAttribute('data-staff-msg-id', data.message.id);

            if (data.bot_paused_by_human || data.auto_reply_bot === false) {
                const toggle = document.getElementById('botAutoReplyToggle');
                const badge = document.getElementById('botStatusBadge');
                const txt = document.getElementById('botStatusText');
                if (toggle) toggle.checked = false;
                if (badge) badge.className = 'badge badge-status-dept';
                if (txt) txt.textContent = data.bot_paused_by_human ? 'Madi AI: PAUSED (Human)' : 'Madi AI: OFF';
            }
        } else {
            const tempEl = document.querySelector(`[data-staff-msg-id="${tempId}"]`);
            if (tempEl) tempEl.remove();
            input.value = messageText;
            alert(data?.error || data?.message || 'Unable to send message right now.');
        }
    })
    .catch(err => {
        btn.disabled = false;
        isSendingStaffMsg = false;
        const tempEl = document.querySelector(`[data-staff-msg-id="${tempId}"]`);
        if (tempEl) tempEl.remove();
        input.value = messageText;
        console.error('Staff message send error:', err);
    });
}

function pollStaffTicketMessages() {
    if (document.hidden || isSendingStaffMsg || isTicketChatPollInFlight) return;
    isTicketChatPollInFlight = true;

    fetch(staffMessagesUrl, {
        cache: 'no-store',
        credentials: 'same-origin',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(r => r.json())
    .then(data => {
        isTicketChatPollInFlight = false;
        if (isSendingStaffMsg) return;
        if (data.success && Array.isArray(data.messages)) {
            // Handle clearing if conversation was wiped
            const thread = document.getElementById('staffChatThread');
            if (thread && data.messages.length === 0 && knownStaffMsgIds.size > 0) {
                thread.innerHTML = `
                    <div id="emptyStaffChatPlaceholder" class="text-center py-4 my-auto" style="color:#8899AA; font-size:0.82rem;">
                        <div style="font-size:1.5rem; margin-bottom:0.3rem;">💬</div>
                        Conversation history deleted.<br>
                        Click <strong>Instant AI Reply</strong> or type a message below to greet or update the guest.
                    </div>
                `;
                knownStaffMsgIds.clear();
                return;
            }

            // Guarantee strict ascending chronological order by message ID
            const orderedMessages = [...data.messages].sort((a, b) => Number(a.id || 0) - Number(b.id || 0));

            let hasNewGuestMsg = false;
            let newGuestMsgCount = 0;
            orderedMessages.forEach(msg => {
                if (!hasKnownStaffMsgId(msg.id)) {
                    addKnownStaffMsgId(msg.id);
                    appendMessageToStaffThread(msg);
                    if (msg.sender_type === 'guest') {
                        hasNewGuestMsg = true;
                        newGuestMsgCount++;
                    }
                }
            });
            const countEl = document.getElementById('floatingChatMsgCount');
            if (countEl) countEl.textContent = orderedMessages.length;

            if (hasNewGuestMsg) {
                if (typeof playStaffAlertChime === 'function') {
                    playStaffAlertChime(false);
                }
                const chatCard = document.getElementById('staffChatCard');
                const unreadBadge = document.getElementById('floatingChatUnreadBadge');
                if (chatCard && chatCard.classList.contains('d-none') && unreadBadge) {
                    const prev = parseInt(unreadBadge.textContent || '0', 10) || 0;
                    unreadBadge.textContent = (prev + newGuestMsgCount) + ' new';
                    unreadBadge.classList.remove('d-none');
                }
            }

            // Live-update AI Diagnostic Rationale & Live Chat Context box if Madison AI refined the summary
            if (data.ai_context) {
                const ctxEl = document.getElementById('liveAiContextText');
                const ctxBox = document.getElementById('liveAiContextBox');
                const ctxBadge = document.getElementById('liveAiContextSyncBadge');
                const formattedCtx = `"${data.ai_context}"`;
                if (ctxEl && ctxEl.textContent.trim() !== formattedCtx) {
                    ctxEl.textContent = formattedCtx;
                    if (ctxBox) {
                        ctxBox.style.boxShadow = '0 0 22px rgba(201,168,76,0.55)';
                        ctxBox.style.borderColor = '#E6CA7A';
                        setTimeout(() => {
                            ctxBox.style.boxShadow = '0 4px 15px rgba(0,0,0,0.25)';
                            ctxBox.style.borderColor = 'rgba(201,168,76,0.3)';
                        }, 2200);
                    }
                    if (ctxBadge) {
                        ctxBadge.innerHTML = '<i class="bi bi-stars me-1"></i>Updated Just Now';
                    }
                }
            }

            // Show/hide 1-click Guest Cancellation Confirmation alert banner live
            const cancelBanner = document.getElementById('guestCancelRequestAlert');
            if (cancelBanner && typeof data.cancel_requested === 'boolean') {
                cancelBanner.classList.toggle('d-none', !data.cancel_requested);
            }
        }
    })
    .catch(() => {
        isTicketChatPollInFlight = false;
    });
}

// Start polling ticket messages every 4 seconds + instant sync when switching back to tab
setInterval(pollStaffTicketMessages, 4000);
document.addEventListener('visibilitychange', () => {
    if (!document.hidden) pollStaffTicketMessages();
});
window.addEventListener('focus', pollStaffTicketMessages);
document.addEventListener('DOMContentLoaded', scrollStaffChatToBottom);

// ── Workflow Status Confirmation Warning Handler ──
let statusConfirmed = false;

function previewStatusChangeWarning(newStatus) {
    const curStatus = document.getElementById('currentDbStatus')?.value || '';
    const noticeEl = document.getElementById('statusInlineNotice');
    if (!noticeEl) return;

    if (newStatus === curStatus) {
        noticeEl.innerHTML = '';
        return;
    }

    if (newStatus === 'closed') {
        noticeEl.innerHTML = '<span class="text-danger"><i class="bi bi-exclamation-triangle-fill"></i> Closing will end guest live chat and lock this ticket.</span>';
    } else if (newStatus === 'resolved') {
        noticeEl.innerHTML = '<span class="text-success"><i class="bi bi-check-circle-fill"></i> Guest will be notified of resolution.</span>';
    } else if (curStatus === 'closed') {
        noticeEl.innerHTML = '<span class="text-warning"><i class="bi bi-unlock-fill"></i> Re-opening ticket to active queue.</span>';
    } else {
        noticeEl.innerHTML = '<span class="text-info"><i class="bi bi-arrow-repeat"></i> Dispatches updated state to guest.</span>';
    }
}

function handleStatusFormSubmit(e) {
    if (statusConfirmed) {
        return true;
    }

    const curStatus = document.getElementById('currentDbStatus')?.value || '';
    const newStatus = document.getElementById('workflowStatusSelect')?.value || '';

    // If status didn't change, allow direct submission without prompt
    if (curStatus === newStatus) {
        return true;
    }

    if (e) e.preventDefault();

    const titleEl = document.getElementById('modalStatusTitle');
    const iconEl = document.getElementById('modalWarningIcon');
    const badgeEl = document.getElementById('modalTransitionBadge');
    const msgEl = document.getElementById('modalWarningMessage');
    const impactEl = document.getElementById('modalGuestImpactText');
    const confirmBtn = document.getElementById('btnConfirmStatusChange');

    if (badgeEl) badgeEl.textContent = `${curStatus.replace('_', ' ').toUpperCase()} → ${newStatus.replace('_', ' ').toUpperCase()}`;

    if (newStatus === 'closed') {
        if (titleEl) titleEl.textContent = 'Warning: Closing Service Ticket';
        if (iconEl) iconEl.textContent = '🔒';
        if (msgEl) msgEl.innerHTML = '<strong>Caution:</strong> Closing this ticket finalizes guest concern resolution and <strong>immediately terminates the two-way live chat session</strong>. Regular staff cannot re-open a closed ticket. Are you sure you wish to close this ticket?';
        if (impactEl) impactEl.textContent = 'Guest will see "Ticket Closed" and chat will be archived.';
        if (confirmBtn) {
            confirmBtn.className = 'btn btn-danger px-4 py-1.5 font-weight-bold';
            confirmBtn.textContent = 'Yes, Close Ticket Now';
        }
    } else if (newStatus === 'resolved') {
        if (titleEl) titleEl.textContent = 'Confirm Ticket Resolution';
        if (iconEl) iconEl.textContent = '✅';
        if (msgEl) msgEl.innerHTML = 'Mark this request as <strong>Resolved</strong>? This signals hotel operations that technician or housekeeping work is complete.';
        if (impactEl) impactEl.textContent = 'Guest timeline advances to the 100% Resolved milestone.';
        if (confirmBtn) {
            confirmBtn.className = 'btn btn-success px-4 py-1.5 font-weight-bold';
            confirmBtn.textContent = 'Confirm Resolution';
        }
    } else if (curStatus === 'closed') {
        if (titleEl) titleEl.textContent = 'Admin Authorization: Re-open Ticket';
        if (iconEl) iconEl.textContent = '🔓';
        if (msgEl) msgEl.innerHTML = 'You are about to <strong>re-open a closed ticket</strong>. This will restore it to the active operations queue and re-enable two-way guest communication.';
        if (impactEl) impactEl.textContent = 'Ticket will re-appear in staff queues and guest live chat will be restored.';
        if (confirmBtn) {
            confirmBtn.className = 'btn-gold px-4 py-1.5 font-weight-bold';
            confirmBtn.textContent = 'Re-open Ticket';
        }
    } else {
        if (titleEl) titleEl.textContent = 'Confirm Status Update';
        if (iconEl) iconEl.textContent = '⚡';
        if (msgEl) msgEl.innerHTML = `Transition ticket status to <strong>${newStatus.replace('_', ' ')}</strong>?`;
        if (impactEl) impactEl.textContent = 'Live guest portal will reflect this new milestone instantly.';
        if (confirmBtn) {
            confirmBtn.className = 'btn-gold px-4 py-1.5 font-weight-bold';
            confirmBtn.textContent = 'Apply Status Update';
        }
    }

    const modalEl = document.getElementById('statusConfirmModal');
    let modalInstance = null;
    if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
        modalInstance = bootstrap.Modal.getOrCreateInstance(modalEl);
        modalInstance.show();
    } else if (modalEl) {
        modalEl.classList.add('show');
        modalEl.style.display = 'block';
    }

    if (confirmBtn) {
        confirmBtn.onclick = function() {
            statusConfirmed = true;
            if (modalInstance) {
                modalInstance.hide();
            } else if (modalEl) {
                modalEl.classList.remove('show');
                modalEl.style.display = 'none';
            }
            document.getElementById('requestStatusForm').submit();
        };
    }

    return false;
}

// ── Floating Bottom-Right Chat Dock Controller ──
function toggleFloatingStaffChat(open) {
    const card = document.getElementById('staffChatCard');
    const launcher = document.getElementById('staffChatLauncher');
    const unreadBadge = document.getElementById('floatingChatUnreadBadge');
    if (!card || !launcher) return;

    if (open) {
        card.classList.remove('d-none');
        launcher.classList.add('d-none');
        if (unreadBadge) {
            unreadBadge.textContent = '0';
            unreadBadge.classList.add('d-none');
        }
        const thread = document.getElementById('staffChatThread');
        if (thread) {
            requestAnimationFrame(() => { thread.scrollTop = thread.scrollHeight; });
        }
        const input = document.getElementById('staffMessageInput');
        if (input) setTimeout(() => input.focus(), 80);
    } else {
        card.classList.add('d-none');
        launcher.classList.remove('d-none');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    if (window.location.hash === '#staffChatCard') {
        toggleFloatingStaffChat(true);
    }
    const aiPanel = document.getElementById('aiTriageDetailsPanel');
    const aiPreview = document.getElementById('aiRationalePreview');
    if (aiPanel && aiPreview) {
        aiPanel.addEventListener('show.bs.collapse', () => aiPreview.classList.add('d-none'));
        aiPanel.addEventListener('hide.bs.collapse', () => aiPreview.classList.remove('d-none'));
    }
});
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        const card = document.getElementById('staffChatCard');
        if (card && !card.classList.contains('d-none')) {
            toggleFloatingStaffChat(false);
        }
    }
});
</script>
@endpush
