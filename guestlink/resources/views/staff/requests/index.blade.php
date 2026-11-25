@extends('layouts.app')
@section('title', 'Requests')
@section('page-title', 'Service & Maintenance Requests')

@push('styles')
<style>
    .clickable-request-row {
        cursor: pointer;
        transition: all 0.15s ease-in-out;
    }
    .clickable-request-row:hover {
        background: rgba(201, 168, 76, 0.09) !important;
    }
    .search-control-wrap {
        position: relative;
    }
    .search-control-wrap i {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: #8899AA;
        pointer-events: none;
        font-size: 0.85rem;
    }
    .search-control-wrap input {
        padding-left: 34px !important;
        background: rgba(11, 20, 34, 0.75) !important;
        border: 1px solid rgba(201, 168, 76, 0.25) !important;
        color: #fff !important;
        border-radius: 10px;
        font-size: 0.82rem;
    }
    .search-control-wrap input:focus {
        border-color: var(--gold) !important;
        box-shadow: 0 0 10px rgba(201, 168, 76, 0.3) !important;
    }

    /* Pulsing Rose & Amber Indicators for Urgent & High Tickets */
    @keyframes pulseRoseGlow {
        0%, 100% { box-shadow: 0 0 0 0 rgba(244, 63, 94, 0.7); }
        50% { box-shadow: 0 0 0 7px rgba(244, 63, 94, 0); }
    }
    @keyframes pulseAmberGlow {
        0%, 100% { box-shadow: 0 0 0 0 rgba(245, 158, 11, 0.7); }
        50% { box-shadow: 0 0 0 7px rgba(245, 158, 11, 0); }
    }
    .pulse-dot-rose {
        animation: pulseRoseGlow 1.8s infinite;
    }
    .pulse-dot-amber {
        animation: pulseAmberGlow 1.8s infinite;
    }
    .priority-item-urgent {
        background: rgba(244, 63, 94, 0.06);
        border: 1px solid rgba(244, 63, 94, 0.35);
        border-radius: 12px;
        padding: 0.85rem 1rem;
        transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
        cursor: pointer;
    }
    .priority-item-urgent:hover {
        background: rgba(244, 63, 94, 0.12);
        border-color: #f43f5e;
        transform: translateX(4px);
        box-shadow: 0 8px 24px rgba(244, 63, 94, 0.15);
    }
    .priority-item-high {
        background: rgba(245, 158, 11, 0.05);
        border: 1px solid rgba(245, 158, 11, 0.35);
        border-radius: 12px;
        padding: 0.85rem 1rem;
        transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
        cursor: pointer;
    }
    .priority-item-high:hover {
        background: rgba(245, 158, 11, 0.12);
        border-color: #f59e0b;
        transform: translateX(4px);
        box-shadow: 0 8px 24px rgba(245, 158, 11, 0.15);
    }
</style>
@endpush

@section('content')
{{-- Quick Filter Navigation & Monthly Reset Cycle Header --}}
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div class="filter-segmented d-inline-flex p-1 flex-wrap">
        @php
            $currStatus = request('status', '');
            $currPriority = request('priority', '');
            $currAssigned = request('assigned', '');
            $currType = request('type', '');
        @endphp
        <a href="{{ route('requests.index', array_merge(request()->except(['status', 'priority', 'assigned', 'type', 'page']), [])) }}"
           class="filter-segment-btn {{ $currStatus === '' && !$currPriority && !$currAssigned && $currType === '' ? 'active' : '' }}">
            <i class="bi bi-collection-fill me-1"></i> All Requests ({{ $period === 'current_month' ? $currentMonthCount : $requests->total() }})
        </a>
        <a href="{{ route('requests.index', array_merge(request()->except(['status', 'priority', 'assigned', 'type', 'page']), ['type' => 'event'])) }}"
           class="filter-segment-btn {{ $currType === 'event' ? 'active' : '' }}">
            <i class="bi bi-calendar2-event me-1 text-gold"></i> Events &amp; Appts
        </a>
        <a href="{{ route('requests.index', array_merge(request()->except(['status', 'priority', 'assigned', 'type', 'page']), ['assigned' => 'me'])) }}"
           class="filter-segment-btn {{ $currAssigned === 'me' ? 'active' : '' }}">
            <i class="bi bi-person-check-fill me-1 text-gold"></i> My Assigned
        </a>
        <a href="{{ route('requests.index', array_merge(request()->except(['status', 'priority', 'assigned', 'type', 'page']), ['status' => 'pending'])) }}"
           class="filter-segment-btn {{ $currStatus === 'pending' ? 'active' : '' }}">
            <i class="bi bi-hourglass-split me-1 text-warning"></i> Pending Triage
        </a>
        <a href="{{ route('requests.index', array_merge(request()->except(['status', 'priority', 'assigned', 'type', 'page']), ['priority' => 'Urgent'])) }}"
           class="filter-segment-btn {{ $currPriority === 'Urgent' ? 'active' : '' }}">
            <i class="bi bi-exclamation-circle text-danger me-1"></i> Urgent Only
        </a>
        <a href="{{ route('requests.index', array_merge(request()->except(['status', 'priority', 'assigned', 'type', 'page']), ['status' => 'in_progress'])) }}"
           class="filter-segment-btn {{ $currStatus === 'in_progress' ? 'active' : '' }}">
            <i class="bi bi-gear-wide-connected me-1 text-info"></i> In Progress
        </a>
        <a href="{{ route('requests.index', array_merge(request()->except(['status', 'priority', 'assigned', 'type', 'page']), ['status' => 'resolved'])) }}"
           class="filter-segment-btn {{ $currStatus === 'resolved' ? 'active' : '' }}">
            <i class="bi bi-check-circle-fill me-1 text-success"></i> Resolved
        </a>
    </div>

    {{-- Unified Rolling 24h / Monthly Cycle Selector --}}
    @php
        $periodLabel = match ($period) {
            '24h'           => 'Last 24h',
            'current_month' => now()->format('M Y'),
            'last_month'    => now()->subMonth()->format('M Y') . ' (Prev)',
            default         => 'All-Time Archive',
        };
    @endphp
    <div class="dropdown">
        <button class="d-inline-flex align-items-center gap-2 dropdown-toggle"
                type="button"
                data-bs-toggle="dropdown"
                aria-expanded="false"
                title="Rolling 24h cycle — click to switch period"
                style="height:38px; padding:0 0.85rem; border-radius:10px; background:#0C1322; border:1px solid rgba(201,162,74,0.28); color:#F6F1E7; font-size:0.78rem; font-weight:600; transition:border-color 0.18s ease, background 0.18s ease;">
            <i class="bi bi-clock-history" style="color:var(--mp-gold); font-size:0.82rem;"></i>
            <span style="color:#8C94A4; font-weight:500;">Cycle</span>
            <span style="color:#E6CA7A;">{{ $periodLabel }}</span>
            @if($period === '24h')
            <span class="d-none d-sm-inline-block" style="width:6px; height:6px; border-radius:50%; background:#34d399; box-shadow:0 0 6px rgba(52,211,153,0.65);" title="Active rolling 24-hour window"></span>
            @endif
        </button>
        <ul class="dropdown-menu dropdown-menu-dark dropdown-menu-end shadow-lg"
            style="background:#0B111E; border:1px solid rgba(201,162,74,0.32); border-radius:12px; padding:0.4rem; min-width:245px; margin-top:8px; font-size:0.78rem; box-shadow:0 18px 44px rgba(0,0,0,0.78);">
            <li class="px-2.5 py-1.5 mb-1" style="font-size:0.66rem; letter-spacing:0.1em; text-transform:uppercase; color:#7E8899; font-weight:700;">
                Operations Cycle
            </li>
            <li>
                <a class="dropdown-item rounded-2 py-1.5 px-2.5 d-flex align-items-center justify-content-between {{ $period === '24h' ? 'active' : '' }}"
                   href="{{ route('requests.index', array_merge(request()->except(['period', 'page']), ['period' => '24h'])) }}">
                    <span><i class="bi bi-clock-history me-2 text-gold"></i>Last 24 Hours (Default)</span>
                    @if($period === '24h')<i class="bi bi-check2 text-gold"></i>@endif
                </a>
            </li>
            <li>
                <a class="dropdown-item rounded-2 py-1.5 px-2.5 d-flex align-items-center justify-content-between {{ $period === 'current_month' ? 'active' : '' }}"
                   href="{{ route('requests.index', array_merge(request()->except(['period', 'page']), ['period' => 'current_month'])) }}">
                    <span><i class="bi bi-calendar-check me-2 text-info"></i>{{ now()->format('F Y') }} (Month)</span>
                    @if($period === 'current_month')<i class="bi bi-check2 text-gold"></i>@endif
                </a>
            </li>
            <li>
                <a class="dropdown-item rounded-2 py-1.5 px-2.5 d-flex align-items-center justify-content-between {{ $period === 'last_month' ? 'active' : '' }}"
                   href="{{ route('requests.index', array_merge(request()->except(['period', 'page']), ['period' => 'last_month'])) }}">
                    <span><i class="bi bi-calendar-minus me-2 text-secondary"></i>{{ now()->subMonth()->format('F Y') }} (Prev)</span>
                    @if($period === 'last_month')<i class="bi bi-check2 text-gold"></i>@endif
                </a>
            </li>
            <li><hr class="dropdown-divider my-1.5" style="border-color:rgba(246,241,231,0.08);"></li>
            <li>
                <a class="dropdown-item rounded-2 py-1.5 px-2.5 d-flex align-items-center justify-content-between {{ $period === 'all' ? 'active' : '' }}"
                   href="{{ route('requests.index', array_merge(request()->except(['period', 'page']), ['period' => 'all'])) }}">
                    <span><i class="bi bi-archive me-2 text-secondary"></i>All-Time Archive ({{ $allTimeCount }})</span>
                    @if($period === 'all')<i class="bi bi-check2 text-gold"></i>@endif
                </a>
            </li>
        </ul>
    </div>
</div>

{{-- Search & Filters Bar --}}
<div class="table-card mb-3 p-3">
    <form method="GET" action="{{ route('requests.index') }}" id="requestsFilterForm">
        @if(request('period'))
        <input type="hidden" name="period" value="{{ request('period') }}">
        @endif
        <div class="row g-2 align-items-end">
            <div class="col-12 col-md-4 col-xl-3">
                <label class="form-label">Live Search</label>
                <div class="search-control-wrap">
                    <i class="bi bi-search"></i>
                    <input type="text"
                           id="requestsSearchInput"
                           name="search"
                           class="form-control"
                           placeholder="Ref #, room, keywords..."
                           value="{{ request('search') }}"
                           onkeyup="liveFilterRequests(this.value)"
                           autocomplete="off">
                </div>
            </div>
            <div class="col-6 col-md-4 col-xl-2">
                <label class="form-label">Department</label>
                <select id="requestsDeptSelect" name="department" class="form-select" onchange="liveFilterRequests()">
                    <option value="">All Departments</option>
                    @foreach(['Front Desk', 'Maintenance', 'Housekeeping'] as $deptOpt)
                    <option value="{{ $deptOpt }}" {{ strcasecmp((string) request('department'), $deptOpt) === 0 ? 'selected' : '' }}>{{ $deptOpt }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-4 col-xl-1">
                <label class="form-label">Type</label>
                <select id="requestsTypeSelect" name="type" class="form-select" onchange="liveFilterRequests()">
                    <option value="">All</option>
                    <option value="event" {{ request('type') === 'event' ? 'selected' : '' }}>Event / Appt</option>
                    <option value="service" {{ request('type') === 'service' ? 'selected' : '' }}>Service</option>
                    <option value="maintenance" {{ request('type') === 'maintenance' ? 'selected' : '' }}>Maint.</option>
                </select>
            </div>
            <div class="col-6 col-md-3 col-xl-2">
                <label class="form-label">Status</label>
                <select id="requestsStatusSelect" name="status" class="form-select" onchange="liveFilterRequests()">
                    <option value="">All Statuses</option>
                    @foreach(['pending','assigned','in_progress','resolved','closed'] as $s)
                    <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucwords(str_replace('_',' ',$s)) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-4 col-xl-2">
                <label class="form-label">Priority</label>
                <select id="requestsPrioritySelect" name="priority" class="form-select" onchange="liveFilterRequests()">
                    <option value="">All Priorities</option>
                    @foreach(['Low','Medium','High','Urgent'] as $p)
                    <option value="{{ $p }}" {{ strcasecmp((string) request('priority'), $p) === 0 ? 'selected' : '' }}>{{ $p }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-8 col-xl-2 d-flex gap-2">
                <button type="submit" class="btn-gold flex-fill d-inline-flex align-items-center justify-content-center gap-1 text-nowrap" style="height:40px;">
                    <i class="bi bi-funnel"></i><span>Apply</span>
                </button>
                <a href="{{ route('requests.index', ['period' => $period]) }}" class="btn-ghost px-3 d-inline-flex align-items-center justify-content-center text-decoration-none text-nowrap" style="height:40px;">
                    Reset
                </a>
            </div>
        </div>
    </form>
</div>

{{-- Priority Attention Deck (Executive Attention Queue) --}}
@php
    $prioritySpotlight = $requests->filter(function($r) {
        return in_array(strtolower($r->priority ?? ''), ['urgent', 'high']) && !in_array($r->status, ['resolved', 'closed']);
    });
@endphp
@if($prioritySpotlight->count() > 0 && (!request('status') || request('status') === 'pending' || request('priority')))
<div class="deck-formal mb-3 p-3">
    <div class="d-flex align-items-center justify-content-between mb-2 pb-2 border-bottom flex-wrap gap-2" style="border-color:rgba(201,168,76,0.15) !important;">
        <div class="d-flex align-items-center gap-2">
            <span class="deck-formal-title">
                <i class="bi bi-shield-exclamation text-danger me-1"></i> Priority Attention Deck
            </span>
            <span class="badge badge-status-urgent">{{ $prioritySpotlight->count() }} Urgent Attention</span>
        </div>
        <div style="font-size:0.72rem; color:var(--muted);">
            Prioritized for rapid staff intervention
        </div>
    </div>
    <div class="row g-2">
        @foreach($prioritySpotlight as $pReq)
        @php
            $isUrg = strtolower($pReq->priority ?? '') === 'urgent';
        @endphp
        <div class="col-md-6 col-12">
            <div class="deck-item-formal d-flex align-items-center justify-content-between gap-3" onclick="window.location='{{ route('requests.show', $pReq) }}'" style="cursor:pointer;">
                <div class="d-flex align-items-center gap-3 flex-grow-1" style="min-width:0;">
                    <div class="deck-item-icon">
                        <i class="bi {{ $pReq->type === 'maintenance' ? 'bi-tools' : 'bi-bell' }}"></i>
                    </div>
                    <div style="min-width:0;" class="flex-grow-1">
                        <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                            <span class="room-badge-formal">
                                Room {{ $pReq->room?->room_number ?? '201' }}
                            </span>
                            @if($isUrg)
                            <span class="badge badge-status-urgent">Urgent</span>
                            @else
                            <span class="badge badge-status-high">High</span>
                            @endif
                            <span class="badge badge-status-dept">
                                {{ $pReq->department ?? ($pReq->category?->name ?? 'Front Desk') }}
                            </span>
                            <span class="badge bg-{{ $pReq->status_badge }}" style="font-size:0.65rem;">
                                {{ str_replace('_', ' ', $pReq->status) }}
                            </span>
                            @if($pReq->is_sla_overdue)
                            <span class="badge bg-danger" style="font-size:0.62rem; letter-spacing:0.04em;" title="Pending beyond SLA target">
                                <i class="bi bi-alarm-fill me-1"></i>SLA OVERDUE
                            </span>
                            @endif
                        </div>
                        <div style="font-size:0.83rem; color:#F7F2E7; font-weight:600; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                            {{ $pReq->description }}
                        </div>
                        <div style="font-size:0.7rem; color:var(--muted); margin-top:2px;" class="d-flex align-items-center gap-2 flex-wrap">
                            <span><i class="bi bi-clock me-1"></i>Logged {{ $pReq->created_at->diffForHumans() }}</span>
                            @if($pReq->assignedTo)
                            &bull; <span>Assigned to {{ $pReq->assignedTo->name }}</span>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="flex-shrink-0">
                    <a href="{{ route('requests.show', $pReq) }}" class="action-link-formal">
                        Inspect &rarr;
                    </a>
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endif

{{-- Table --}}
<div class="table-card">
    <div class="table-responsive">
        <table class="data-table align-middle" id="requestsTable">
            <thead>
                <tr>
                    <th>Reference</th>
                    <th>Room</th>
                    <th>Type</th>
                    <th>Guest &amp; Request</th>
                    <th>Status</th>
                    <th>Priority</th>
                    <th>AI Triage</th>
                    <th>Department</th>
                    <th>Time</th>
                    <th style="text-align:right;">Action</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $sortedPageRequests = $requests->getCollection()->sortBy(function($r) {
                        $p = strtolower($r->priority ?? '');
                        $st = strtolower($r->status ?? '');
                        $isDone = in_array($st, ['resolved', 'closed']) ? 20 : 0;
                        if ($p === 'urgent') return 1 + $isDone;
                        if ($p === 'high') return 2 + $isDone;
                        if ($p === 'medium') return 3 + $isDone;
                        if ($p === 'low') return 4 + $isDone;
                        return 5 + $isDone;
                    });
                @endphp
                @forelse($sortedPageRequests as $req)
                @php
                    $prioLower = strtolower($req->priority ?? '');
                    $isUrgentRow = $prioLower === 'urgent';
                    $isHighRow = $prioLower === 'high';
                    $isEventRow = $req->isEventInquiry();
                    $eventRowSummary = null;
                    if ($isEventRow) {
                        $venueMatch = preg_match('/Venue:\s*([^\n•]+)/i', $req->description, $vm) ? trim($vm[1]) : null;
                        $evtMatch = preg_match('/Event Type:\s*([^\n•]+)/i', $req->description, $em) ? trim($em[1]) : null;
                        if ($venueMatch || $evtMatch) {
                            $eventRowSummary = trim(($venueMatch ?: 'Event Inquiry') . ($evtMatch ? ' · ' . $evtMatch : ''));
                        }
                    }
                    $rowHighlightStyle = $isUrgentRow
                        ? 'background:rgba(244,63,94,0.04);'
                        : ($isHighRow ? 'background:rgba(245,158,11,0.03);' : '');
                @endphp
                <tr class="clickable-request-row"
                    data-department="{{ strtolower($req->department ?? 'engineering') }}"
                    data-type="{{ strtolower($req->type ?? '') }}"
                    data-is-event="{{ $isEventRow ? '1' : '0' }}"
                    data-status="{{ strtolower($req->status ?? '') }}"
                    data-priority="{{ strtolower($req->priority ?? '') }}"
                    onclick="window.location='{{ route('requests.show', $req) }}'"
                    title="Click row to inspect Request #{{ $req->reference_number }}"
                    style="{{ $rowHighlightStyle }}">
                    <td>
                        <a href="{{ route('requests.show', $req) }}" style="text-decoration:none;" onclick="event.stopPropagation();">
                            <code style="color:var(--mp-gold); font-size:0.78rem; font-weight:600;">{{ $req->reference_number }}</code>
                        </a>
                    </td>
                    <td style="white-space:nowrap;">
                        <span class="room-badge-formal">
                            Room {{ $req->room?->room_number ?? '201' }}
                        </span>
                    </td>
                    <td style="white-space:nowrap;">
                        @if($isEventRow)
                        <span class="small" style="color:#E6CA7A; white-space:nowrap; font-weight:500;">
                            <i class="bi bi-calendar2-event me-1 opacity-75"></i> Event
                        </span>
                        @else
                        <span class="small text-secondary" style="white-space:nowrap;">
                            <i class="bi {{ $req->type === 'maintenance' ? 'bi-tools' : 'bi-bell' }} me-1 opacity-75"></i>
                            {{ ucfirst($req->type) }}
                        </span>
                        @endif
                    </td>
                    <td style="max-width:225px;">
                        <div style="font-size:0.8rem; color:#fff; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                            @if(!empty($req->photo_path))
                            <i class="bi bi-camera me-1" style="color:var(--mp-gold);" title="Photo attached"></i>
                            @endif
                            {{ $eventRowSummary ?: $req->description }}
                        </div>
                        @if($req->guest_name)
                        <div class="text-secondary small text-truncate">By: {{ $req->guest_name }}</div>
                        @endif
                    </td>
                    <td>
                        <span class="badge bg-{{ $req->status_badge }}">
                            {{ $req->display_status }}
                        </span>
                    </td>
                    <td>
                        @if($isUrgentRow)
                        <span class="badge badge-status-urgent">Urgent</span>
                        @elseif($isHighRow)
                        <span class="badge badge-status-high">High</span>
                        @elseif($req->priority)
                        <span class="badge badge-status-dept">{{ $req->priority }}</span>
                        @else
                        <span class="text-secondary opacity-50">—</span>
                        @endif
                    </td>
                    <td>
                        @if($req->aiTriage)
                        <span class="badge badge-status-dept" title="AI Assessed: {{ $req->aiTriage->suggested_priority }} priority">
                            <i class="bi bi-stars me-1" style="color:var(--mp-gold);"></i>{{ ucfirst($req->aiTriage->status) }}
                        </span>
                        @else
                        <span class="text-secondary opacity-50">—</span>
                        @endif
                    </td>
                    <td>
                        <span class="badge badge-status-dept">
                            {{ $req->department ?? 'Engineering' }}
                        </span>
                        @if($req->assignedTo)
                        <div class="text-secondary small mt-1" style="font-size:0.7rem; white-space:nowrap;">
                            <i class="bi bi-person me-1"></i>{{ $req->assignedTo->name }}
                        </div>
                        @endif
                    </td>
                    <td class="text-secondary small" style="white-space:nowrap;">
                        <i class="bi bi-clock me-1 opacity-50"></i>{{ $req->created_at->diffForHumans() }}
                    </td>
                    <td style="text-align:right; white-space:nowrap;" onclick="event.stopPropagation();">
                        <div class="d-inline-flex align-items-center gap-2">
                            <a href="{{ route('requests.show', $req) }}#staffChatCard"
                               class="badge text-decoration-none d-inline-flex align-items-center gap-1"
                               style="background:{{ ($req->unread_guest_messages_count ?? 0) > 0 ? 'rgba(201,168,76,0.25)' : 'rgba(255,255,255,0.06)' }}; border:1px solid {{ ($req->unread_guest_messages_count ?? 0) > 0 ? 'rgba(201,168,76,0.6)' : 'rgba(255,255,255,0.15)' }}; color:{{ ($req->unread_guest_messages_count ?? 0) > 0 ? '#E8D08D' : '#CBD5E1' }}; font-size:0.72rem; padding:0.35rem 0.65rem; border-radius:14px;"
                               title="Open Live Guest & AI Chat ({{ $req->messages_count ?? 0 }} messages)">
                                <i class="bi bi-chat-dots"></i>
                                <span>Chat ({{ $req->messages_count ?? 0 }})</span>
                            </a>
                            <a href="{{ route('requests.show', $req) }}" class="action-link-formal">
                                Inspect &rarr;
                            </a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr id="reqEmptyRow">
                    <td colspan="10" style="text-align:center; color:#8899AA; padding:3.5rem;">
                        <div style="font-size:1.75rem; margin-bottom:0.5rem; opacity:0.55;"><i class="bi bi-inbox"></i></div>
                        No requests found matching criteria.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($requests->hasPages())
    <div class="p-3 border-top" style="border-color:rgba(255,255,255,0.08) !important;">
        {{ $requests->links() }}
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
    function liveFilterRequests(keyword) {
        const searchEl = document.getElementById('requestsSearchInput');
        const deptEl = document.getElementById('requestsDeptSelect');
        const typeEl = document.getElementById('requestsTypeSelect');
        const statusEl = document.getElementById('requestsStatusSelect');
        const prioEl = document.getElementById('requestsPrioritySelect');

        const term = (typeof keyword === 'string' ? keyword : (searchEl ? searchEl.value : '')).toLowerCase().trim();
        const dept = (deptEl ? deptEl.value : '').toLowerCase().trim();
        const type = (typeEl ? typeEl.value : '').toLowerCase().trim();
        const status = (statusEl ? statusEl.value : '').toLowerCase().trim();
        const prio = (prioEl ? prioEl.value : '').toLowerCase().trim();

        const table = document.getElementById('requestsTable');
        if (!table) return;
        const tbody = table.querySelector('tbody');
        const rows = table.querySelectorAll('tbody tr.clickable-request-row');
        let visibleCount = 0;

        rows.forEach(row => {
            const text = row.innerText.toLowerCase();
            const rDept = (row.getAttribute('data-department') || '').toLowerCase();
            const rType = (row.getAttribute('data-type') || '').toLowerCase();
            const rIsEvent = row.getAttribute('data-is-event') === '1';
            const rStatus = (row.getAttribute('data-status') || '').toLowerCase();
            const rPrio = (row.getAttribute('data-priority') || '').toLowerCase();

            const matchTerm = !term || text.includes(term);
            const matchDept = !dept || rDept === dept || (dept === 'maintenance' && rDept === 'engineering');
            const matchType = !type || (type === 'event' ? rIsEvent : rType === type);
            const matchStatus = !status || rStatus === status;
            const matchPrio = !prio || rPrio === prio;

            if (matchTerm && matchDept && matchType && matchStatus && matchPrio) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        let dynamicEmpty = document.getElementById('reqLiveNoMatchRow');
        if (visibleCount === 0 && rows.length > 0) {
            if (!dynamicEmpty && tbody) {
                dynamicEmpty = document.createElement('tr');
                dynamicEmpty.id = 'reqLiveNoMatchRow';
                dynamicEmpty.innerHTML = `
                    <td colspan="10" style="text-align:center; color:#8899AA; padding:2.75rem 1rem;">
                        <i class="bi bi-funnel d-block mb-2" style="font-size:1.6rem; opacity:0.55; color:#C9A24A;"></i>
                        <div style="font-size:0.88rem; color:#F6F1E7; font-weight:600;">No requests match your selected filters.</div>
                        <div style="font-size:0.75rem; color:#8899AA; margin-top:0.25rem;">Try choosing a different department, status, or priority, or click Apply to query all pages.</div>
                        <button type="button" onclick="resetLiveRequestFilters()" class="btn-ghost px-3 py-1 mt-3" style="font-size:0.75rem;">
                            Clear Filters
                        </button>
                    </td>
                `;
                tbody.appendChild(dynamicEmpty);
            } else if (dynamicEmpty) {
                dynamicEmpty.style.display = '';
            }
        } else if (dynamicEmpty) {
            dynamicEmpty.style.display = 'none';
        }
    }

    function resetLiveRequestFilters() {
        ['requestsSearchInput', 'requestsDeptSelect', 'requestsTypeSelect', 'requestsStatusSelect', 'requestsPrioritySelect'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.value = '';
        });
        liveFilterRequests();
    }
</script>
@endpush
