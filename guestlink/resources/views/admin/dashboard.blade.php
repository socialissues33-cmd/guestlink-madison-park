@extends('layouts.app')
@section('title', 'Admin Overview')
@section('page-title', 'Administration Panel')

@push('styles')
<style>
    /* ── Admin Overview Executive Styling (Dark Default) ── */
    .adm-kpi-link {
        display: block;
        text-decoration: none;
        color: inherit;
        height: 100%;
    }
    .adm-kpi-link .stat-card {
        transition: transform 0.16s ease, border-color 0.16s ease, box-shadow 0.16s ease;
    }
    .adm-kpi-link:hover .stat-card {
        transform: translateY(-2px);
        border-color: rgba(201, 162, 74, 0.42);
        box-shadow: 0 10px 24px rgba(0, 0, 0, 0.28);
    }
    .adm-kpi-link.is-alert .stat-card {
        border-color: rgba(248, 113, 113, 0.45);
    }
    .adm-kpi-sub {
        font-size: 0.72rem;
        color: #8E9BAE;
        margin-top: 0.35rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.35rem;
    }
    .adm-kpi-sub .bi-arrow-up-right {
        opacity: 0.45;
        transition: opacity 0.16s ease, transform 0.16s ease;
    }
    .adm-kpi-link:hover .adm-kpi-sub .bi-arrow-up-right {
        opacity: 1;
        color: #E6CA7A;
        transform: translate(1px, -1px);
    }

    .adm-card-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        margin-bottom: 1rem;
        padding-bottom: 0.7rem;
        border-bottom: 1px solid var(--mp-hairline);
    }
    .adm-card-title {
        font-family: var(--mp-font-display);
        font-size: 1.05rem;
        font-weight: 600;
        color: var(--mp-ivory);
        margin: 0;
    }
    .adm-eyebrow {
        font-size: 0.68rem;
        color: #8A96A8;
        text-transform: uppercase;
        letter-spacing: 0.12em;
        font-weight: 600;
    }

    /* Diagnostics Grid */
    .adm-diag-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 0.65rem;
        margin-bottom: 1.15rem;
    }
    @media (max-width: 767.98px) {
        .adm-diag-grid { grid-template-columns: 1fr; }
    }
    .adm-diag-tile {
        background: rgba(255, 255, 255, 0.025);
        border: 1px solid var(--mp-hairline);
        border-radius: 10px;
        padding: 0.75rem 0.85rem;
    }
    .adm-diag-label {
        font-size: 0.66rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.09em;
        color: #8A96A8;
        margin-bottom: 0.3rem;
        display: flex;
        align-items: center;
        gap: 0.35rem;
    }
    .adm-diag-val {
        font-size: 0.86rem;
        font-weight: 600;
        color: #F3EFE6;
        line-height: 1.25;
        word-break: break-word;
    }
    .adm-diag-meta {
        font-size: 0.71rem;
        color: #8E9BAE;
        margin-top: 0.25rem;
    }

    /* Hotel Control Rows */
    .adm-control-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        padding: 0.7rem 0.85rem;
        background: rgba(255, 255, 255, 0.022);
        border: 1px solid var(--mp-hairline);
        border-radius: 10px;
    }
    .adm-control-title {
        font-size: 0.84rem;
        font-weight: 600;
        color: #F3EFE6;
        margin: 0;
    }
    .adm-control-desc {
        font-size: 0.73rem;
        color: #8E9BAE;
        margin: 0.1rem 0 0;
    }
    .adm-switch {
        width: 2.55rem !important;
        height: 1.35rem !important;
        cursor: pointer;
        flex-shrink: 0;
        background-color: rgba(148, 163, 184, 0.28);
        border-color: rgba(148, 163, 184, 0.4);
    }
    .adm-switch:checked {
        background-color: #10B981 !important;
        border-color: #10B981 !important;
    }
    .adm-notice-input {
        background: rgba(11, 20, 34, 0.72) !important;
        border: 1px solid rgba(201, 162, 74, 0.24) !important;
        color: #F3EFE6 !important;
        border-radius: 10px !important;
        font-size: 0.82rem !important;
        padding: 0.55rem 0.85rem !important;
    }
    .adm-notice-input::placeholder {
        color: #7D899C !important;
    }

    /* Interactive Role Rows */
    .adm-role-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        padding: 0.62rem 0.85rem;
        background: rgba(255, 255, 255, 0.022);
        border: 1px solid var(--mp-hairline);
        border-radius: 10px;
        text-decoration: none;
        transition: border-color 0.16s ease, background 0.16s ease, transform 0.14s ease;
    }
    a.adm-role-row:hover {
        border-color: rgba(201, 162, 74, 0.42);
        background: rgba(201, 162, 74, 0.06);
        transform: translateX(2px);
    }
    .adm-role-name {
        font-size: 0.84rem;
        font-weight: 600;
        color: #F3EFE6;
        text-transform: capitalize;
        line-height: 1.2;
    }
    .adm-role-scope {
        font-size: 0.71rem;
        color: #8A96A8;
        margin-top: 0.1rem;
    }
    .adm-role-pill {
        background: rgba(201, 162, 74, 0.12);
        border: 1px solid rgba(201, 162, 74, 0.3);
        border-radius: 999px;
        padding: 0.16rem 0.65rem;
        font-size: 0.72rem;
        font-weight: 600;
        color: #E6CA7A;
        white-space: nowrap;
    }

    /* Audit Feed */
    .adm-status-chip {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        font-size: 0.7rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        padding: 0.18rem 0.55rem;
        border-radius: 6px;
        background: rgba(148, 163, 184, 0.14);
        color: #CBD5E1;
        border: 1px solid rgba(148, 163, 184, 0.22);
    }
    .adm-status-chip.is-done {
        background: rgba(16, 185, 129, 0.14);
        color: #6EE7B7;
        border-color: rgba(16, 185, 129, 0.3);
    }
    .adm-status-chip.is-warn {
        background: rgba(245, 158, 11, 0.14);
        color: #FCD34D;
        border-color: rgba(245, 158, 11, 0.3);
    }
    .adm-status-chip.is-info {
        background: rgba(56, 189, 248, 0.14);
        color: #7DD3FC;
        border-color: rgba(56, 189, 248, 0.3);
    }
    .adm-status-chip.is-danger {
        background: rgba(248, 113, 113, 0.14);
        color: #FCA5A5;
        border-color: rgba(248, 113, 113, 0.3);
    }

    .adm-health-pill {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        font-size: 0.72rem;
        font-weight: 600;
        padding: 0.24rem 0.65rem;
        border-radius: 999px;
        background: rgba(16, 185, 129, 0.14);
        color: #6EE7B7;
        border: 1px solid rgba(16, 185, 129, 0.32);
    }
    .adm-health-pill.is-warn {
        background: rgba(245, 158, 11, 0.14);
        color: #FCD34D;
        border-color: rgba(245, 158, 11, 0.32);
    }

    /* ── Crisp Light Mode Overrides ── */
    html[data-theme="light"] .adm-kpi-link:hover .stat-card {
        border-color: rgba(160, 116, 20, 0.45);
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
    }
    html[data-theme="light"] .adm-kpi-sub,
    html[data-theme="light"] .adm-eyebrow,
    html[data-theme="light"] .adm-diag-label,
    html[data-theme="light"] .adm-diag-meta,
    html[data-theme="light"] .adm-control-desc,
    html[data-theme="light"] .adm-role-scope {
        color: #475569;
    }
    html[data-theme="light"] .adm-kpi-link:hover .adm-kpi-sub .bi-arrow-up-right {
        color: #7A5408;
    }
    html[data-theme="light"] .adm-card-title,
    html[data-theme="light"] .adm-diag-val,
    html[data-theme="light"] .adm-control-title,
    html[data-theme="light"] .adm-role-name {
        color: #0F172A;
    }
    html[data-theme="light"] .adm-diag-tile,
    html[data-theme="light"] .adm-control-row,
    html[data-theme="light"] .adm-role-row {
        background: #F8FAFC;
        border-color: #E2E8F0;
    }
    html[data-theme="light"] a.adm-role-row:hover {
        background: #FFFBEB;
        border-color: rgba(160, 116, 20, 0.45);
    }
    html[data-theme="light"] .adm-role-pill {
        background: #FEF3C7;
        border-color: rgba(160, 116, 20, 0.35);
        color: #7A5408;
    }
    html[data-theme="light"] .adm-notice-input {
        background: #FFFFFF !important;
        border-color: #CBD5E1 !important;
        color: #0F172A !important;
    }
    html[data-theme="light"] .adm-notice-input::placeholder {
        color: #64748B !important;
    }
    html[data-theme="light"] .adm-health-pill {
        background: #ECFDF5;
        color: #047857;
        border-color: #A7F3D0;
    }
    html[data-theme="light"] .adm-health-pill.is-warn {
        background: #FFFBEB;
        color: #B45309;
        border-color: #FDE68A;
    }
    html[data-theme="light"] .adm-status-chip {
        background: #F1F5F9;
        color: #334155;
        border-color: #CBD5E1;
    }
    html[data-theme="light"] .adm-status-chip.is-done {
        background: #ECFDF5;
        color: #047857;
        border-color: #A7F3D0;
    }
    html[data-theme="light"] .adm-status-chip.is-warn {
        background: #FFFBEB;
        color: #B45309;
        border-color: #FDE68A;
    }
    html[data-theme="light"] .adm-status-chip.is-info {
        background: #EFF6FF;
        color: #1D4ED8;
        border-color: #BFDBFE;
    }
    html[data-theme="light"] .adm-status-chip.is-danger {
        background: #FEF2F2;
        color: #B91C1C;
        border-color: #FECACA;
    }
</style>
@endpush

@section('content')
@php
    $isAdmin = auth()->user()?->hasRole('admin');
    $kpiCards = [
        [
            'icon'  => 'bi-door-closed',
            'label' => 'Rooms & QR',
            'val'   => $stats['total_rooms'],
            'sub'   => $stats['active_rooms'] . ' active' . ($stats['inactive_rooms'] > 0 ? ' · ' . $stats['inactive_rooms'] . ' off' : ''),
            'href'  => route('admin.rooms'),
            'alert' => false,
        ],
        [
            'icon'  => 'bi-people',
            'label' => 'Staff Accounts',
            'val'   => $stats['total_staff'],
            'sub'   => $stats['active_staff'] . ' active' . ($stats['inactive_staff'] > 0 ? ' · ' . $stats['inactive_staff'] . ' off' : ''),
            'href'  => $isAdmin ? route('admin.users') : '#systemRolesCard',
            'alert' => false,
        ],
        [
            'icon'  => 'bi-ticket-perforated',
            'label' => 'Total Requests',
            'val'   => $stats['total_requests'],
            'sub'   => $stats['resolved_today'] . ' resolved today',
            'href'  => route('requests.index'),
            'alert' => false,
        ],
        [
            'icon'  => 'bi-hourglass-split',
            'label' => 'Pending Queue',
            'val'   => $stats['pending_requests'],
            'sub'   => $stats['urgent_open'] > 0 ? $stats['urgent_open'] . ' urgent open' : 'No urgent backlog',
            'href'  => route('requests.index', ['status' => 'pending']),
            'alert' => $stats['urgent_open'] > 0,
        ],
        [
            'icon'  => 'bi-cup-hot',
            'label' => 'Food Orders',
            'val'   => $stats['total_food_orders'],
            'sub'   => '₱' . number_format($stats['delivered_revenue'], 0) . ' delivered',
            'href'  => route('food.orders'),
            'alert' => false,
        ],
        [
            'icon'  => 'bi-journal-bookmark',
            'label' => 'Menu & Catalog',
            'val'   => $stats['total_food_items'],
            'sub'   => $stats['available_food_items'] . ' avail · ' . $stats['active_categories'] . ' cats',
            'href'  => route('food.menu'),
            'alert' => $stats['unavailable_food_items'] > 0,
        ],
    ];
@endphp

{{-- 1. Interactive KPI Overview Strip --}}
<div class="row g-3 mb-4">
    @foreach($kpiCards as $card)
    <div class="col-6 col-md-4 col-xl-2">
        <a href="{{ $card['href'] }}" class="adm-kpi-link {{ $card['alert'] ? 'is-alert' : '' }}">
            <div class="stat-card h-100 d-flex flex-column justify-content-between">
                <div>
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="stat-label">{{ $card['label'] }}</span>
                        <i class="bi {{ $card['icon'] }} stat-icon"></i>
                    </div>
                    <div class="stat-value mp-num">{{ $card['val'] }}</div>
                </div>
                <div class="adm-kpi-sub">
                    <span class="text-truncate">{{ $card['sub'] }}</span>
                    <i class="bi bi-arrow-up-right flex-shrink-0"></i>
                </div>
            </div>
        </a>
    </div>
    @endforeach
</div>

{{-- 2. Main Governance Row: System Health & Controls (Left) + Interactive RBAC Roles (Right) --}}
<div class="row g-3 mb-4">
    <div class="col-lg-7">
        <div class="table-card p-4 h-100 d-flex flex-column justify-content-between">
            <div>
                <div class="adm-card-head">
                    <div>
                        <h2 class="adm-card-title">System Health &amp; Hotel Controls</h2>
                    </div>
                    <span class="adm-health-pill {{ $systemHealth['gemini_configured'] ? '' : 'is-warn' }}">
                        <i class="bi {{ $systemHealth['gemini_configured'] ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill' }}"></i>
                        {{ $systemHealth['gemini_configured'] ? 'Systems Operational' : 'Gemini Key Missing' }}
                    </span>
                </div>

                {{-- Real-Time Diagnostics Strip --}}
                <div class="adm-diag-grid">
                    <div class="adm-diag-tile">
                        <div class="adm-diag-label">
                            <i class="bi bi-stars text-gold"></i> Gemini AI Triage
                        </div>
                        <div class="adm-diag-val">{{ $systemHealth['gemini_model'] }}</div>
                        <div class="adm-diag-meta mp-num">
                            @if($systemHealth['ai_acceptance_rate'] !== null)
                                {{ $systemHealth['ai_acceptance_rate'] }}% accepted ({{ $systemHealth['ai_accepted'] }}/{{ $systemHealth['ai_accepted'] + $systemHealth['ai_overridden'] }})
                            @else
                                {{ $systemHealth['ai_total'] }} triaged · {{ $systemHealth['ai_pending'] }} pending
                            @endif
                        </div>
                    </div>

                    <div class="adm-diag-tile">
                        <div class="adm-diag-label">
                            <i class="bi bi-database text-gold"></i> Database Engine
                        </div>
                        <div class="adm-diag-val">
                            {{ $systemHealth['db_driver'] }}
                            @if($systemHealth['db_journal'] !== 'N/A')
                                &middot; {{ $systemHealth['db_journal'] }}
                            @endif
                        </div>
                        <div class="adm-diag-meta mp-num">
                            @if($systemHealth['db_size_kb'] !== null)
                                {{ $systemHealth['db_size_kb'] }} KB · PHP {{ $systemHealth['php_version'] }}
                            @else
                                PHP {{ $systemHealth['php_version'] }} · Laravel {{ \Illuminate\Support\Str::before($systemHealth['laravel_version'], '.') }}
                            @endif
                        </div>
                    </div>

                    <div class="adm-diag-tile">
                        <div class="adm-diag-label">
                            <i class="bi bi-qr-code-scan text-gold"></i> Portal Readiness
                        </div>
                        <div class="adm-diag-val mp-num">{{ $stats['active_rooms'] }}/{{ $stats['total_rooms'] }} Rooms Live</div>
                        <div class="adm-diag-meta mp-num">
                            {{ $stats['available_food_items'] }}/{{ $stats['total_food_items'] }} dishes · {{ $stats['active_categories'] }} categories
                        </div>
                    </div>
                </div>

                {{-- Live Hotel-Wide Operational Controls --}}
                <form method="POST" action="{{ route('admin.settings.update') }}" class="d-flex flex-column gap-2.5">
                    @csrf
                    <div class="adm-control-row">
                        <div>
                            <p class="adm-control-title">Madi AI Auto-Reply Bot</p>
                            <p class="adm-control-desc">Automatically greet guests and assist on active service &amp; maintenance chats.</p>
                        </div>
                        <div class="form-check form-switch m-0">
                            <input type="hidden" name="bot_auto_reply_enabled" value="0">
                            <input class="form-check-input adm-switch" type="checkbox" role="switch"
                                   id="admBotToggle" name="bot_auto_reply_enabled" value="1"
                                   {{ $systemHealth['bot_auto_reply'] ? 'checked' : '' }}>
                        </div>
                    </div>

                    <div class="adm-control-row">
                        <div>
                            <p class="adm-control-title">Times Square In-Room Dining</p>
                            <p class="adm-control-desc">Toggle off after kitchen hours to pause new QR food orders while keeping menu readable.</p>
                        </div>
                        <div class="form-check form-switch m-0">
                            <input type="hidden" name="kitchen_ordering_open" value="0">
                            <input class="form-check-input adm-switch" type="checkbox" role="switch"
                                   id="admKitchenToggle" name="kitchen_ordering_open" value="1"
                                   {{ $systemHealth['kitchen_ordering_open'] ? 'checked' : '' }}>
                        </div>
                    </div>

                    <div class="mt-1">
                        <label for="admGuestNotice" class="adm-eyebrow d-block mb-1.5">
                            Guest Portal Announcement Banner <span style="opacity:0.7; text-transform:none; letter-spacing:0;">(optional)</span>
                        </label>
                        <input type="text"
                               id="admGuestNotice"
                               name="guest_notice"
                               maxlength="180"
                               value="{{ old('guest_notice', $systemHealth['guest_notice']) }}"
                               class="form-control adm-notice-input"
                               placeholder="e.g. Pool maintenance until 5:00 PM · Times Square Dinner starts at 6:00 PM (leave blank to hide)">
                    </div>

                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 pt-2 mt-1" style="border-top:1px solid var(--mp-hairline);">
                        <button type="submit" class="btn-gold px-4 py-2" style="border-radius:10px; font-size:0.82rem;">
                            <i class="bi bi-check2-circle me-1"></i> Save Hotel Controls
                        </button>

                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            @if($isAdmin)
                            <a href="{{ route('admin.backup.download') }}" class="btn-ghost px-3 py-2 text-decoration-none" style="font-size:0.78rem; border-radius:10px;" title="Download a point-in-time SQLite snapshot">
                                <i class="bi bi-download me-1"></i> DB Backup
                            </a>
                            @endif
                            <a href="{{ route('admin.reports.export') }}" class="btn-ghost px-3 py-2 text-decoration-none" style="font-size:0.78rem; border-radius:10px;">
                                <i class="bi bi-filetype-csv me-1"></i> Export CSV
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Interactive System Roles (RBAC) --}}
    <div class="col-lg-5" id="systemRolesCard">
        <div class="table-card p-4 h-100">
            <div class="adm-card-head">
                <div>
                    <h2 class="adm-card-title">System Roles &amp; Access</h2>
                </div>
                @if($isAdmin)
                <a href="{{ route('admin.users') }}" class="btn-ghost px-2.5 py-1 text-decoration-none" style="font-size:0.74rem; border-radius:8px;">
                    <i class="bi bi-person-plus me-1"></i> Manage Staff
                </a>
                @else
                <span class="adm-eyebrow">RBAC</span>
                @endif
            </div>

            <div class="d-flex flex-column gap-2">
                @foreach($roles as $role)
                @php
                    $scopeLabel = $roleScopes[$role->name] ?? 'Operational Staff';
                    $roleUrl = $isAdmin ? route('admin.users', ['search' => $role->name]) : null;
                @endphp
                @if($roleUrl)
                <a href="{{ $roleUrl }}" class="adm-role-row">
                    <div class="min-w-0">
                        <div class="adm-role-name">{{ str_replace('_', ' ', $role->name) }}</div>
                        <div class="adm-role-scope text-truncate">{{ $scopeLabel }}</div>
                    </div>
                    <div class="d-flex align-items-center gap-2 flex-shrink-0">
                        <span class="adm-role-pill mp-num">{{ $role->users_count }} {{ \Illuminate\Support\Str::plural('user', $role->users_count) }}</span>
                        <i class="bi bi-chevron-right opacity-50" style="font-size:0.72rem;"></i>
                    </div>
                </a>
                @else
                <div class="adm-role-row">
                    <div class="min-w-0">
                        <div class="adm-role-name">{{ str_replace('_', ' ', $role->name) }}</div>
                        <div class="adm-role-scope text-truncate">{{ $scopeLabel }}</div>
                    </div>
                    <span class="adm-role-pill mp-num">{{ $role->users_count }} {{ \Illuminate\Support\Str::plural('user', $role->users_count) }}</span>
                </div>
                @endif
                @endforeach
            </div>
        </div>
    </div>
</div>

{{-- 3. Live Staff & Operations Audit Feed --}}
<div class="table-card">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 px-4 py-3" style="border-bottom:1px solid var(--mp-hairline);">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-activity text-gold"></i>
            <h2 class="adm-card-title">Recent Staff &amp; System Activity</h2>
        </div>
        <a href="{{ route('admin.reports') }}" class="btn-ghost px-3 py-1.5 text-decoration-none" style="font-size:0.76rem; border-radius:8px;">
            Full Audit Log <i class="bi bi-arrow-right ms-1"></i>
        </a>
    </div>

    <div class="table-responsive">
        <table class="data-table mb-0">
            <thead>
                <tr>
                    <th>Reference</th>
                    <th>Room</th>
                    <th>Status Change</th>
                    <th>Actor</th>
                    <th>Time</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($recentActivity as $act)
                @php
                    $to = strtolower((string) $act['to']);
                    $chipClass = match(true) {
                        in_array($to, ['resolved', 'closed', 'delivered', 'ready']) => 'is-done',
                        in_array($to, ['pending', 'preparing', 'assigned']) => 'is-warn',
                        in_array($to, ['cancelled']) => 'is-danger',
                        default => 'is-info',
                    };
                @endphp
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi {{ $act['icon'] }} text-gold"></i>
                            <span class="mp-num fw-semibold" style="font-size:0.8rem;">{{ $act['ref'] }}</span>
                        </div>
                    </td>
                    <td>
                        @if($act['room'])
                            <span class="room-badge-formal">Room {{ $act['room'] }}</span>
                        @else
                            <span style="opacity:0.55;">—</span>
                        @endif
                    </td>
                    <td>
                        <div class="d-flex align-items-center gap-1.5 flex-wrap">
                            @if($act['from'])
                                <span class="adm-status-chip">{{ str_replace('_', ' ', $act['from']) }}</span>
                                <i class="bi bi-arrow-right opacity-50" style="font-size:0.7rem;"></i>
                            @endif
                            <span class="adm-status-chip {{ $chipClass }}">{{ str_replace('_', ' ', $act['to']) }}</span>
                        </div>
                    </td>
                    <td style="font-size:0.8rem; font-weight:500;">{{ $act['actor'] }}</td>
                    <td class="mp-num" style="font-size:0.76rem; opacity:0.78;" title="{{ $act['created_at']?->format('M d, Y h:i A') }}">
                        {{ $act['created_at']?->diffForHumans() }}
                    </td>
                    <td class="text-end">
                        @if($act['url'])
                        <a href="{{ $act['url'] }}" class="btn-ghost px-2.5 py-1 text-decoration-none" style="font-size:0.74rem; border-radius:8px;">
                            Inspect &rarr;
                        </a>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center py-4" style="opacity:0.7;">
                        No operational activity recorded yet. Status updates from requests and kitchen orders will appear here live.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
