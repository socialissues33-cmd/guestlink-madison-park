@extends('layouts.app')
@section('title', 'Dashboard')
@section('page-title', 'Operations Dashboard')

@push('styles')
<style>
    .stat-card-clickable {
        display: block;
        text-decoration: none !important;
        cursor: pointer;
        height: 100%;
    }
    .stat-card-clickable .stat-card {
        transition: border-color 150ms cubic-bezier(0.22, 1, 0.36, 1), background-color 150ms cubic-bezier(0.22, 1, 0.36, 1), transform 140ms cubic-bezier(0.22, 1, 0.36, 1);
        position: relative;
    }
    .stat-card-clickable:hover .stat-card {
        transform: translateY(-1px);
        border-color: rgba(201, 162, 74, 0.45);
    }
    .stat-card-clickable.active-filter .stat-card {
        border-color: #C9A24A !important;
        background: rgba(201, 162, 74, 0.08) !important;
        box-shadow: inset 0 2px 0 #C9A24A !important;
    }

    /* Urgent Emergency Panel (quiet semantic hairline, zero glowing shadows) */
    .urgent-banner {
        background: #121722;
        border: 1px solid rgba(239, 68, 68, 0.35);
        border-radius: 14px;
        overflow: hidden;
    }
    .urgent-item-row {
        background: rgba(255, 255, 255, 0.025);
        border: 1px solid rgba(239, 68, 68, 0.22);
        border-radius: 10px;
        padding: 0.85rem 1rem;
        transition: background-color 150ms cubic-bezier(0.22, 1, 0.36, 1), border-color 150ms cubic-bezier(0.22, 1, 0.36, 1);
        cursor: pointer;
    }
    .urgent-item-row:hover {
        background: rgba(239, 68, 68, 0.08);
        border-color: rgba(239, 68, 68, 0.45);
    }

    .priority-item-urgent {
        background: rgba(244, 63, 94, 0.05);
        border: 1px solid rgba(244, 63, 94, 0.28);
        border-radius: 10px;
        padding: 0.85rem 1rem;
        transition: background-color 150ms cubic-bezier(0.22, 1, 0.36, 1), border-color 150ms cubic-bezier(0.22, 1, 0.36, 1);
        cursor: pointer;
    }
    .priority-item-urgent:hover {
        background: rgba(244, 63, 94, 0.1);
        border-color: rgba(244, 63, 94, 0.5);
    }
    .priority-item-high {
        background: rgba(201, 162, 74, 0.05);
        border: 1px solid rgba(201, 162, 74, 0.28);
        border-radius: 10px;
        padding: 0.85rem 1rem;
        transition: background-color 150ms cubic-bezier(0.22, 1, 0.36, 1), border-color 150ms cubic-bezier(0.22, 1, 0.36, 1);
        cursor: pointer;
    }
    .priority-item-high:hover {
        background: rgba(201, 162, 74, 0.1);
        border-color: rgba(201, 162, 74, 0.5);
    }

    /* Live Stream Filter Bar */
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
        border: 1px solid rgba(201, 162, 74, 0.22) !important;
        color: #fff !important;
        border-radius: 10px;
        font-size: 0.82rem;
    }
    .search-control-wrap input:focus {
        border-color: var(--gold) !important;
        box-shadow: 0 0 0 3px rgba(201, 162, 74, 0.14) !important;
    }
</style>
@endpush

@section('content')
{{-- Quick Action Toolbar --}}
<div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
    <div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <h2 style="font-family:'Playfair Display',serif; font-size:1.35rem; color:#fff; margin:0;">
                Madison Park Operations
            </h2>
            <span class="badge" style="background:rgba(201,168,76,0.12); border:1px solid rgba(201,168,76,0.3); color:#E8D08D; font-size:0.72rem; padding:0.25rem 0.65rem; border-radius:12px;">
                <i class="bi bi-clock-history me-1"></i> 24h Operations
            </span>
        </div>
        <span style="font-size:0.75rem; color:var(--muted);">Rolling 24-hour operational window &bull; Full historical archive in Requests</span>
    </div>
    <div class="filter-segmented d-inline-flex p-1 flex-wrap">
        <a href="{{ route('dashboard', ['filter' => 'all']) }}" class="filter-segment-btn {{ $filter === 'all' && !request('search') && !request('type') && !request('status') && !request('priority') ? 'active' : '' }}">
            <i class="bi bi-grid-fill me-1"></i> All 24h (<span id="btnTotal24hCount">{{ $stats['total_requests_24h'] }}</span>)
        </a>
        <a href="{{ route('dashboard', ['filter' => 'urgent']) }}" class="filter-segment-btn {{ $filter === 'urgent' ? 'active' : '' }}">
            <i class="bi bi-exclamation-circle text-danger me-1"></i> Urgent (<span id="btnUrgentCount">{{ $stats['urgent_requests'] }}</span>)
        </a>
        <a href="{{ route('food.orders', ['status' => 'pending']) }}" class="filter-segment-btn">
            <i class="bi bi-cup-hot text-gold me-1"></i> Kitchen (<span id="btnKitchenQueueCount">{{ $stats['pending_orders'] }}</span>)
        </a>
    </div>
</div>

{{-- KPI Stats Grid (All 6 cards are clickable and update live) --}}
<div class="row g-3 mb-4">
    {{-- Card 1: Pending --}}
    <div class="col-6 col-sm-4 col-lg-4 col-xl-2">
        <a href="{{ route('dashboard', ['filter' => 'pending']) }}" class="stat-card-clickable {{ $filter === 'pending' ? 'active-filter' : '' }}">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start">
                    <span class="stat-label">Pending</span>
                    <i class="bi bi-hourglass-split stat-icon"></i>
                </div>
                <div class="stat-value" id="kpiPendingCount">{{ $stats['pending_requests'] }}</div>
                <div style="font-size:0.7rem; color:var(--muted);">Awaiting action</div>
            </div>
        </a>
    </div>

    {{-- Card 2: In Progress --}}
    <div class="col-6 col-sm-4 col-lg-4 col-xl-2">
        <a href="{{ route('dashboard', ['filter' => 'in_progress']) }}" class="stat-card-clickable {{ $filter === 'in_progress' ? 'active-filter' : '' }}">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start">
                    <span class="stat-label">In Progress</span>
                    <i class="bi bi-arrow-repeat stat-icon"></i>
                </div>
                <div class="stat-value" id="kpiInProgressCount">{{ $stats['in_progress_requests'] }}</div>
                <div style="font-size:0.7rem; color:var(--muted);">Being handled</div>
            </div>
        </a>
    </div>

    {{-- Card 3: Resolved Today --}}
    <div class="col-6 col-sm-4 col-lg-4 col-xl-2">
        <a href="{{ route('dashboard', ['filter' => 'resolved']) }}" class="stat-card-clickable {{ $filter === 'resolved' ? 'active-filter' : '' }}">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start">
                    <span class="stat-label">Resolved Today</span>
                    <i class="bi bi-check2-circle stat-icon"></i>
                </div>
                <div class="stat-value" id="kpiResolvedTodayCount">{{ $stats['resolved_today'] }}</div>
                <div style="font-size:0.7rem; color:var(--muted);">Completed</div>
            </div>
        </a>
    </div>

    {{-- Card 4: Active Orders --}}
    <div class="col-6 col-sm-4 col-lg-4 col-xl-2">
        <a href="{{ route('food.orders', ['status' => 'pending']) }}" class="stat-card-clickable" title="View Times Square Kitchen Queue">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start">
                    <span class="stat-label">Active Orders</span>
                    <i class="bi bi-cup-hot stat-icon"></i>
                </div>
                <div class="stat-value" id="kpiActiveOrdersCount">{{ $stats['pending_orders'] }}</div>
                <div style="font-size:0.7rem; color:var(--gold);">Kitchen queue &rarr;</div>
            </div>
        </a>
    </div>

    {{-- Card 5: Urgent --}}
    <div class="col-6 col-sm-4 col-lg-4 col-xl-2">
        <a href="{{ route('dashboard', ['filter' => 'urgent']) }}" class="stat-card-clickable {{ $filter === 'urgent' ? 'active-filter' : '' }}">
            <div class="stat-card" style="border-color: rgba(244,63,94,0.32);">
                <div class="d-flex justify-content-between align-items-start">
                    <span class="stat-label" style="color:#fda4af;">Urgent</span>
                    <i class="bi bi-exclamation-octagon" style="color:#fda4af; font-size:1.05rem;"></i>
                </div>
                <div class="stat-value" style="color:#fda4af;" id="kpiUrgentCount">{{ $stats['urgent_requests'] }}</div>
                <div style="font-size:0.7rem; color:#fda4af; opacity:0.85;">Immediate action</div>
            </div>
        </a>
    </div>

    {{-- Card 6: AI Triaged --}}
    <div class="col-6 col-sm-4 col-lg-4 col-xl-2">
        <a href="{{ route('dashboard', ['filter' => 'ai']) }}" class="stat-card-clickable {{ $filter === 'ai' ? 'active-filter' : '' }}">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start">
                    <span class="stat-label">AI Triaged</span>
                    <i class="bi bi-stars stat-icon"></i>
                </div>
                <div class="stat-value" id="kpiAiTriagedCount">{{ $stats['ai_triaged'] }}</div>
                <div style="font-size:0.7rem; color:var(--muted);">Gemini AI routed</div>
            </div>
        </a>
    </div>
</div>

{{-- 🚨 Dedicated Urgent & High Priority Attention Queue (High Visibility) --}}
@php
    $highPriorityTickets = $recentRequests->filter(function($r) {
        return strtolower($r->priority ?? '') === 'high' && !in_array($r->status, ['resolved', 'closed']);
    });
    $attentionTickets = $urgentRequests->concat($highPriorityTickets)->unique('id');
@endphp
@if($attentionTickets->count() > 0)
<div class="deck-formal mb-4 p-3" id="dashboardUrgentBanner">
    <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom flex-wrap gap-2" style="border-color:rgba(201,168,76,0.15) !important;">
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <span class="deck-formal-title">
                <i class="bi bi-shield-exclamation text-danger me-1"></i> Priority Attention Deck
            </span>
            <span class="badge badge-status-urgent ms-1" id="dashboardUrgentQueueCount">
                {{ $attentionTickets->count() }} Immediate Attention
            </span>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span style="font-size:0.72rem; color:var(--muted);">Prioritized for rapid staff intervention</span>
            <a href="{{ route('requests.index', ['priority' => 'Urgent']) }}" class="btn-ghost px-2 py-1 text-decoration-none" style="font-size:0.75rem;">
                All Urgent &rarr;
            </a>
            <a href="{{ route('requests.index', ['priority' => 'High']) }}" class="btn-ghost px-2 py-1 text-decoration-none" style="font-size:0.75rem;">
                All High &rarr;
            </a>
        </div>
    </div>

    <div class="row g-2" id="dashboardUrgentList">
        @foreach($attentionTickets as $urg)
        @php
            $isUrgent = strtolower($urg->priority ?? '') === 'urgent';
        @endphp
        <div class="col-md-6 col-12">
            <div class="deck-item-formal d-flex align-items-center justify-content-between gap-3" onclick="window.location='{{ route('requests.show', $urg) }}'">
                <div class="d-flex align-items-center gap-3 flex-grow-1" style="min-width:0;">
                    <div class="deck-item-icon">
                        <i class="bi {{ $urg->type === 'maintenance' ? 'bi-tools' : 'bi-bell' }}"></i>
                    </div>
                    <div style="min-width:0;" class="flex-grow-1">
                        <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                            <span class="room-badge-formal">
                                Room {{ $urg->room?->room_number ?? '201' }}
                            </span>
                            @if($isUrgent)
                            <span class="badge badge-status-urgent">Urgent</span>
                            @else
                            <span class="badge badge-status-high">High</span>
                            @endif
                            <span class="badge badge-status-dept">
                                {{ $urg->department ?? ($urg->category?->name ?? 'Front Desk') }}
                            </span>
                            <span class="badge bg-{{ $urg->status_badge }}" style="font-size:0.65rem;">
                                {{ str_replace('_', ' ', $urg->status) }}
                            </span>
                            @if($urg->is_sla_overdue)
                            <span class="badge bg-danger" style="font-size:0.62rem; letter-spacing:0.04em;" title="Pending beyond SLA target">
                                <i class="bi bi-alarm-fill me-1"></i>SLA OVERDUE
                            </span>
                            @endif
                            @if($urg->aiTriage)
                            <span style="font-size:0.7rem; color:var(--gold);" title="AI Triaged"><i class="bi bi-stars"></i></span>
                            @endif
                        </div>
                        <div style="font-size:0.83rem; color:#F7F2E7; font-weight:600; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                            {{ $urg->description }}
                        </div>
                        <div style="font-size:0.7rem; color:var(--muted); margin-top:2px;" class="d-flex align-items-center gap-2 flex-wrap">
                            <span><i class="bi bi-clock me-1"></i>Logged {{ $urg->created_at->diffForHumans() }}</span>
                            @if($urg->assignedTo)
                            &bull; <span>Assigned to {{ $urg->assignedTo->name }}</span>
                            @else
                            &bull; <strong style="color:var(--gold);">Unassigned</strong>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="flex-shrink-0">
                    <a href="{{ route('requests.show', $urg) }}" class="action-link-formal">
                        Inspect &rarr;
                    </a>
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endif

@php
    $dashUser = auth()->user();
    $canSeeKitchenOnDash = !$dashUser || $dashUser->hasAnyRole(['admin', 'owner', 'front_desk', 'restaurant']);
@endphp
<div class="row g-3">
    {{-- Recent Requests Table with Filter Controls --}}
    <div class="{{ $canSeeKitchenOnDash ? 'col-xl-8' : 'col-12' }} col-12">
        <div class="table-card">
            <div class="d-flex align-items-center justify-content-between p-3 border-bottom flex-wrap gap-2" style="border-color:rgba(201,168,76,0.18) !important;">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-activity text-gold" style="font-size:1.1rem;"></i>
                    <div>
                        <span style="font-weight:700; font-size:0.95rem; color:#fff;">
                            @php
                                $filterTitles = [
                                    'all'         => 'Live Service & Maintenance Stream',
                                    'urgent'      => 'Urgent Priority Queue',
                                    'pending'     => 'Pending Triage Queue',
                                    'in_progress' => 'Currently In Progress',
                                    'resolved'    => 'Resolved Today',
                                    'ai'          => 'AI Triaged Requests',
                                ];
                            @endphp
                            {{ $filterTitles[$filter] ?? 'Live Requests Stream' }}
                        </span>
                        <span class="badge ms-1" style="background:rgba(201,168,76,0.12); color:#E8D08D; font-size:0.68rem; border:1px solid rgba(201,168,76,0.3);">
                            24h Operations
                        </span>
                        <span class="badge badge-status-resolved ms-1">
                            <i class="bi bi-broadcast me-1"></i> Live Stream
                        </span>
                        @if($filter !== 'all')
                        <span class="badge badge-status-dept ms-1">Filtered</span>
                        @endif
                    </div>
                </div>
                <div class="d-flex gap-2 align-items-center flex-wrap">
                    <button type="button" id="dashboardManualRefreshBtn" class="btn-ghost px-2 py-1 text-decoration-none d-inline-flex align-items-center gap-1" onclick="manualRefreshStream(this)" style="font-size:0.75rem; border-radius:8px;" title="Instantly sync live stream">
                        <i class="bi bi-arrow-clockwise"></i>
                        <span>Sync Now</span>
                    </button>
                    @if($filter !== 'all' || request('search') || request('type') || request('status') || request('priority'))
                    <a href="{{ route('dashboard', ['filter' => 'all']) }}" class="btn-ghost px-2 py-1 text-decoration-none d-inline-flex align-items-center gap-1" style="font-size:0.72rem;">
                        <i class="bi bi-x-lg"></i><span>Clear Filters</span>
                    </a>
                    @endif
                    <a href="{{ route('requests.index') }}" class="btn-ghost px-2 py-1 text-decoration-none" style="font-size:0.75rem;">
                        Monthly Archive (Full Table) &rarr;
                    </a>
                </div>
            </div>

            {{-- Live Stream Search & Filters Bar --}}
            <div class="p-3 border-bottom" style="background:rgba(15,27,45,0.7); border-color:rgba(201,168,76,0.18) !important;">
                <form method="GET" action="{{ route('dashboard') }}" id="dashboardStreamFilterForm">
                    @if(request('filter') && request('filter') !== 'all')
                    <input type="hidden" name="filter" value="{{ request('filter') }}">
                    @endif
                    <div class="row g-2 align-items-end">
                        <div class="col-md-3 col-12">
                            <label class="form-label" style="font-size:0.75rem; color:#8899AA; text-transform:uppercase; font-weight:600; letter-spacing:0.5px;">Live Search</label>
                            <div class="search-control-wrap">
                                <i class="bi bi-search"></i>
                                <input type="text"
                                       id="dashboardStreamSearch"
                                       name="search"
                                       class="form-control"
                                       placeholder="Ref #, room, keywords..."
                                       value="{{ request('search') }}"
                                       oninput="liveFilterDashboardStream()"
                                       autocomplete="off">
                            </div>
                        </div>
                        <div class="col-md-2 col-6">
                            <label class="form-label" style="font-size:0.72rem; color:#8899AA; font-weight:600;">Type</label>
                            <select id="dashboardStreamType" name="type" class="form-select" onchange="liveFilterDashboardStream()" style="height:40px; font-size:0.82rem;">
                                <option value="">All Types</option>
                                <option value="service" {{ request('type') === 'service' ? 'selected' : '' }}>Service</option>
                                <option value="maintenance" {{ request('type') === 'maintenance' ? 'selected' : '' }}>Maintenance</option>
                            </select>
                        </div>
                        <div class="col-md-2 col-6">
                            <label class="form-label" style="font-size:0.72rem; color:#8899AA; font-weight:600;">Status</label>
                            <select id="dashboardStreamStatus" name="status" class="form-select" onchange="liveFilterDashboardStream()" style="height:40px; font-size:0.82rem;">
                                <option value="">All Statuses</option>
                                @foreach(['pending','assigned','in_progress','resolved','closed'] as $s)
                                <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucwords(str_replace('_',' ',$s)) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2 col-6">
                            <label class="form-label" style="font-size:0.72rem; color:#8899AA; font-weight:600;">Priority</label>
                            <select id="dashboardStreamPriority" name="priority" class="form-select" onchange="liveFilterDashboardStream()" style="height:40px; font-size:0.82rem;">
                                <option value="">All Priorities</option>
                                @foreach(['Low','Medium','High','Urgent'] as $p)
                                <option value="{{ $p }}" {{ request('priority') === $p ? 'selected' : '' }}>{{ $p }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 col-12 d-flex gap-2">
                            <button type="submit" class="btn-gold flex-fill d-inline-flex align-items-center justify-content-center gap-1 text-nowrap" style="height:40px; font-size:0.84rem; border-radius:10px;">
                                <i class="bi bi-funnel"></i><span>Apply</span>
                            </button>
                            <a href="{{ route('dashboard', ['filter' => 'all']) }}" class="btn-ghost px-3 d-inline-flex align-items-center justify-content-center text-decoration-none text-nowrap" style="height:40px; font-size:0.84rem; border-radius:10px;">
                                Reset
                            </a>
                        </div>
                    </div>
                </form>
            </div>

            <div class="table-responsive">
                <table class="data-table" id="dashboardStreamTable">
                    <thead>
                        <tr>
                            <th>Reference</th>
                            <th>Room</th>
                            <th>Type</th>
                            <th>Description</th>
                            <th>Status</th>
                            <th>Priority</th>
                            <th>Time</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $sortedRecentRequests = $recentRequests->sortBy(function($r) {
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
                        @forelse($sortedRecentRequests as $req)
                        @php
                            $rowPrio = strtolower($req->priority ?? '');
                            $isUrgentRow = $rowPrio === 'urgent';
                            $isHighRow = $rowPrio === 'high';
                            $isEventDashRow = $req->isEventInquiry();
                            $dashRowStyle = $isUrgentRow
                                ? 'background:rgba(244,63,94,0.04);'
                                : ($isHighRow ? 'background:rgba(245,158,11,0.03);' : '');
                        @endphp
                        <tr class="dashboard-stream-row"
                            data-ref="{{ $req->reference_number }}"
                            data-search="{{ strtolower($req->reference_number . ' ' . ($req->room->room_number ?? '') . ' ' . $req->description . ' ' . ($req->guest_name ?? '') . ' ' . $req->type . ' ' . $req->status . ' ' . ($req->priority ?? '')) }}"
                            data-type="{{ strtolower($req->type) }}"
                            data-status="{{ strtolower($req->status) }}"
                            data-priority="{{ strtolower($req->priority ?? '') }}"
                            style="cursor:pointer; transition:all 0.15s ease; {{ $dashRowStyle }}"
                            onclick="window.location='{{ route('requests.show', $req) }}'">
                            <td style="white-space:nowrap;">
                                <div class="d-inline-flex align-items-center gap-1 text-nowrap">
                                    <code style="color:var(--gold); font-size:0.78rem; font-weight:600;">{{ $req->reference_number }}</code>
                                    @if($req->aiTriage)
                                        <span style="font-size:0.72rem; color:var(--gold);" title="AI Triaged"><i class="bi bi-stars"></i></span>
                                    @endif
                                </div>
                            </td>
                            <td style="white-space:nowrap;">
                                <span class="room-badge-formal">
                                    Room {{ $req->room?->room_number ?? '201' }}
                                </span>
                            </td>
                            <td style="white-space:nowrap;">
                                <div class="d-flex flex-column align-items-start gap-1">
                                    <div class="d-inline-flex align-items-center gap-1.5 text-nowrap">
                                        <i class="bi {{ $isEventDashRow ? 'bi-calendar2-event' : ($req->type === 'maintenance' ? 'bi-tools' : 'bi-bell') }} text-gold" style="font-size:0.8rem;"></i>
                                        <span style="font-size:0.8rem; font-weight:600; color:#F8FAFC;">{{ $isEventDashRow ? 'Event' : ucfirst($req->type) }}</span>
                                    </div>
                                    <span class="badge badge-status-dept" style="font-size:0.65rem;">
                                        {{ $req->department ?? ($req->type === 'maintenance' ? 'Maintenance' : 'Front Desk') }}
                                    </span>
                                </div>
                            </td>
                            <td style="max-width:180px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; font-size:0.8rem; color:#E2E8F0;">
                                {{ $req->description }}
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
                                <span class="badge bg-{{ $req->priority_badge }}">{{ $req->priority }}</span>
                                @else<span style="color:var(--muted);">—</span>@endif
                            </td>
                            <td style="color:var(--muted); font-size:0.75rem; white-space:nowrap;">
                                <i class="bi bi-clock me-1"></i>{{ $req->created_at->diffForHumans() }}
                            </td>
                            <td>
                                <a href="{{ route('requests.show', $req) }}" class="action-link-formal">
                                    Inspect &rarr;
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr id="emptyDashboardStreamPlaceholder">
                            <td colspan="8" style="text-align:center; color:#8899AA; padding:3rem 1.5rem;">
                                <i class="bi bi-inbox" style="font-size:1.8rem; display:block; margin-bottom:0.5rem; opacity:0.6;"></i>
                                <div>No requests match current filter (<strong>{{ ucfirst($filter) }}</strong>) in the 24-hour operations window.</div>
                                <a href="{{ route('dashboard', ['filter' => 'all']) }}" class="btn-gold px-3 py-1 mt-2 d-inline-block text-decoration-none" style="font-size:0.75rem;">
                                    Show All 24h Requests
                                </a>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Recent Food Orders Column (Only shown to Front Office, Admin, Owner & Restaurant) --}}
    @if($canSeeKitchenOnDash)
    <div class="col-xl-4 col-12">
        <div class="table-card h-100">
            <div class="d-flex align-items-center justify-content-between gap-2 p-3 border-bottom" style="border-color:rgba(201,168,76,0.18) !important;">
                <div class="d-flex align-items-center gap-2 min-w-0">
                    <i class="bi bi-cup-hot text-gold flex-shrink-0" style="font-size:1.1rem;"></i>
                    <span class="text-truncate" style="font-weight:700; font-size:0.95rem; color:#fff;">Times Square Queue</span>
                </div>
                <a href="{{ route('food.orders') }}" class="btn-ghost px-2.5 py-1 text-decoration-none text-nowrap flex-shrink-0" style="font-size:0.75rem;">
                    All Orders &rarr;
                </a>
            </div>
            <div class="p-2" id="dashboardRecentOrdersList">
                @forelse($recentOrders as $order)
                <div class="p-3 mb-2 rounded-3 border transition-all dashboard-order-card" data-ref="{{ $order->reference_number }}" style="background:rgba(255,255,255,0.02); border-color:rgba(201,168,76,0.14) !important; cursor:pointer;" onclick="window.location='{{ route('food.orders.show', $order) }}'">
                    <div class="d-flex justify-content-between align-items-center gap-2 mb-1">
                        <div class="d-flex align-items-center gap-1.5 min-w-0">
                            <span class="room-badge-formal">Room {{ $order->room?->room_number ?? '201' }}</span>
                            <span class="text-truncate" style="color:var(--muted); font-size:0.72rem;">({{ $order->guest_name ?? 'Guest' }})</span>
                        </div>
                        <span class="badge bg-{{ $order->status_badge }} order-status-badge flex-shrink-0">{{ ucfirst($order->status) }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center gap-2 mt-2">
                        <code class="text-truncate" style="font-size:0.72rem; color:var(--gold);">{{ $order->reference_number }}</code>
                        <span class="text-nowrap flex-shrink-0" style="font-size:0.82rem; font-weight:700; color:#F7F2E7;">₱{{ number_format($order->total_amount, 2) }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-2 pt-1" style="border-top:1px dashed rgba(255,255,255,0.06); font-size:0.7rem; color:var(--muted);">
                        <span>{{ $order->orderItems->count() }} item(s)</span>
                        <span>{{ $order->created_at->diffForHumans() }}</span>
                    </div>
                </div>
                @empty
                <div id="emptyDashboardOrdersPlaceholder" style="text-align:center; color:var(--muted); padding:3rem 1rem; font-size:0.85rem;">
                    No orders currently in the kitchen queue.
                </div>
                @endforelse
            </div>
        </div>
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
    function liveFilterDashboardStream() {
        const searchInput = document.getElementById('dashboardStreamSearch');
        const typeSelect = document.getElementById('dashboardStreamType');
        const statusSelect = document.getElementById('dashboardStreamStatus');
        const prioritySelect = document.getElementById('dashboardStreamPriority');

        const term = (searchInput ? searchInput.value : '').toLowerCase().trim();
        const type = (typeSelect ? typeSelect.value : '').toLowerCase().trim();
        const status = (statusSelect ? statusSelect.value : '').toLowerCase().trim();
        const priority = (prioritySelect ? prioritySelect.value : '').toLowerCase().trim();

        const table = document.getElementById('dashboardStreamTable');
        if (!table) return;
        const rows = table.querySelectorAll('tbody tr.dashboard-stream-row');
        let visibleCount = 0;

        rows.forEach(row => {
            const rowSearch = (row.getAttribute('data-search') || '').toLowerCase();
            const rowType = (row.getAttribute('data-type') || '').toLowerCase();
            const rowStatus = (row.getAttribute('data-status') || '').toLowerCase();
            const rowPriority = (row.getAttribute('data-priority') || '').toLowerCase();

            const matchesSearch = !term || rowSearch.includes(term);
            const matchesType = !type || rowType === type;
            const matchesStatus = !status || rowStatus === status;
            const matchesPriority = !priority || rowPriority === priority;

            if (matchesSearch && matchesType && matchesStatus && matchesPriority) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        // Dynamic empty state indicator
        let noMatchRow = document.getElementById('streamNoMatchRow');
        const tbody = table.querySelector('tbody');
        if (visibleCount === 0 && rows.length > 0) {
            if (!noMatchRow && tbody) {
                noMatchRow = document.createElement('tr');
                noMatchRow.id = 'streamNoMatchRow';
                noMatchRow.innerHTML = `
                    <td colspan="8" style="text-align:center; color:#8899AA; padding:2.5rem 1rem;">
                        <div style="font-size:1.6rem; margin-bottom:0.4rem; opacity:0.55;"><i class="bi bi-search"></i></div>
                        <div style="font-size:0.9rem; color:#fff; font-weight:600;">No 24-hour requests match your live filters.</div>
                        <div style="font-size:0.75rem; color:#8899AA; margin-top:0.25rem;">Try adjusting search keywords, type, status, or priority.</div>
                        <button type="button" onclick="resetLiveDashboardStream()" class="btn-gold px-3 py-1 mt-3" style="font-size:0.75rem; border-radius:8px;">
                            Reset Live Filters
                        </button>
                    </td>
                `;
                tbody.appendChild(noMatchRow);
            } else if (noMatchRow) {
                noMatchRow.style.display = '';
            }
        } else if (noMatchRow) {
            noMatchRow.style.display = 'none';
        }
    }

    function resetLiveDashboardStream() {
        const searchInput = document.getElementById('dashboardStreamSearch');
        const typeSelect = document.getElementById('dashboardStreamType');
        const statusSelect = document.getElementById('dashboardStreamStatus');
        const prioritySelect = document.getElementById('dashboardStreamPriority');
        if (searchInput) searchInput.value = '';
        if (typeSelect) typeSelect.value = '';
        if (statusSelect) statusSelect.value = '';
        if (prioritySelect) prioritySelect.value = '';
        liveFilterDashboardStream();
    }
</script>
<style>
.spin-icon {
    animation: spinRefresh 0.8s linear infinite !important;
    display: inline-block;
}
@keyframes spinRefresh {
    100% { transform: rotate(360deg); }
}
</style>
@endpush
