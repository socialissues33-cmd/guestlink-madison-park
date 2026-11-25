@extends('layouts.app')
@section('title', 'Reports')
@section('page-title', 'Reports & Monitoring')

@section('content')
{{-- Date Filter & Export Controls --}}
<div class="table-card p-3 mb-4">
    <form method="GET" action="{{ route('admin.reports') }}" class="row g-2 align-items-end">
        <div class="col-12 col-sm-6 col-md-3">
            <label class="form-label">From Date</label>
            <input type="date" name="date_from" class="form-control" value="{{ $dateFrom }}">
        </div>
        <div class="col-12 col-sm-6 col-md-3">
            <label class="form-label">To Date</label>
            <input type="date" name="date_to" class="form-control" value="{{ $dateTo }}">
        </div>
        <div class="col-6 col-md-2">
            <button type="submit" class="btn-gold px-3 py-2 w-100">Apply</button>
        </div>
        <div class="col-6 col-md-2">
            <a href="{{ route('admin.reports') }}" class="btn-ghost py-2 px-3 text-center d-block text-decoration-none">Reset</a>
        </div>
        <div class="col-12 col-md-2 d-flex gap-2">
            <a href="{{ route('admin.reports.export', ['date_from' => $dateFrom, 'date_to' => $dateTo]) }}"
               class="btn-gold py-2 px-3 text-center flex-fill text-decoration-none"
               title="Download CSV Report">
                <i class="bi bi-file-earmark-spreadsheet me-1"></i> CSV
            </a>
            <button type="button" onclick="window.print()" class="btn-ghost py-2 px-3 text-center" title="Print Report">
                <i class="bi bi-printer"></i>
            </button>
        </div>
    </form>
</div>

<div class="row g-3">
    {{-- Request Stats --}}
    <div class="col-md-4">
        <div class="table-card p-4 h-100 d-flex flex-column justify-content-between">
            <div>
                <h2 class="mb-3 pb-2" style="font-family:var(--mp-font-display); font-size:1.02rem; font-weight:600; color:var(--mp-ivory); border-bottom:1px solid var(--mp-hairline);">
                    <i class="bi bi-ticket-perforated text-gold me-2"></i>Service &amp; Maintenance Requests
                </h2>
                @foreach([
                    'pending'     => 'bi-hourglass-split',
                    'assigned'    => 'bi-person-check',
                    'in_progress' => 'bi-lightning-charge',
                    'resolved'    => 'bi-check2-circle',
                    'closed'      => 'bi-archive',
                ] as $status => $icon)
                <div class="d-flex align-items-center justify-content-between mb-2 px-3 py-2" style="background:rgba(255,255,255,0.025); border:1px solid var(--mp-hairline); border-radius:10px;">
                    <span style="font-size:0.83rem; color:#E7E2D7;"><i class="bi {{ $icon }} text-gold me-2"></i>{{ ucwords(str_replace('_',' ',$status)) }}</span>
                    <span class="mp-num" style="background:rgba(201,162,74,0.12); border:1px solid rgba(201,162,74,0.28); border-radius:999px; padding:0.12rem 0.65rem; font-size:0.78rem; font-weight:600; color:#E6CA7A;">
                        {{ $requestStats[$status] ?? 0 }}
                    </span>
                </div>
                @endforeach
            </div>
            <div class="mt-3 pt-3" style="border-top:1px solid var(--mp-hairline);">
                <div class="d-flex justify-content-between">
                    <span style="font-size:0.8rem; color:#8C94A4;">By Type &mdash; Service</span>
                    <span class="mp-num" style="color:#E6CA7A; font-weight:600; font-size:0.84rem;">{{ $byType['service'] ?? 0 }}</span>
                </div>
                <div class="d-flex justify-content-between mt-1.5">
                    <span style="font-size:0.8rem; color:#8C94A4;">By Type &mdash; Maintenance</span>
                    <span class="mp-num" style="color:#E6CA7A; font-weight:600; font-size:0.84rem;">{{ $byType['maintenance'] ?? 0 }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- AI-Assistant Triage & Department Performance --}}
    <div class="col-md-4">
        <div class="table-card p-4 h-100 d-flex flex-column justify-content-between">
            <div>
                <h2 class="mb-3 pb-2" style="font-family:var(--mp-font-display); font-size:1.02rem; font-weight:600; color:var(--mp-ivory); border-bottom:1px solid var(--mp-hairline);">
                    <i class="bi bi-stars text-gold me-2"></i>AI-Assistant Triage &amp; Routing
                </h2>
                <div class="d-flex align-items-center justify-content-between mb-2 px-3 py-2.5" style="background:rgba(201,162,74,0.09); border:1px solid rgba(201,162,74,0.3); border-radius:10px;">
                    <span style="font-size:0.83rem; font-weight:600; color:#E6CA7A;">Total AI-Triaged Tickets</span>
                    <span class="mp-num" style="font-family:var(--mp-font-display); font-size:1.05rem; font-weight:600; color:#E6CA7A;">
                        {{ $aiTriageStats['total'] ?? 0 }}
                    </span>
                </div>
                <div class="d-flex align-items-center justify-content-between mb-2 px-3 py-2" style="background:rgba(255,255,255,0.025); border:1px solid var(--mp-hairline); border-radius:10px;">
                    <span style="font-size:0.82rem; color:#E7E2D7;"><i class="bi bi-check2-circle text-success me-2"></i>Recommendation Accepted</span>
                    <span class="mp-num" style="color:#86EFAC; font-weight:600; font-size:0.84rem;">{{ $aiTriageStats['accepted'] ?? 0 }}</span>
                </div>
                <div class="d-flex align-items-center justify-content-between mb-2 px-3 py-2" style="background:rgba(255,255,255,0.025); border:1px solid var(--mp-hairline); border-radius:10px;">
                    <span style="font-size:0.82rem; color:#E7E2D7;"><i class="bi bi-pencil text-gold me-2"></i>Staff Adjusted</span>
                    <span class="mp-num" style="color:#FDE68A; font-weight:600; font-size:0.84rem;">{{ $aiTriageStats['modified'] ?? 0 }}</span>
                </div>
                <div class="d-flex align-items-center justify-content-between mb-2 px-3 py-2" style="background:rgba(255,255,255,0.025); border:1px solid var(--mp-hairline); border-radius:10px;">
                    <span style="font-size:0.82rem; color:#E7E2D7;"><i class="bi bi-hourglass-split text-secondary me-2"></i>Pending Verification</span>
                    <span class="mp-num" style="color:#98A0AE; font-weight:600; font-size:0.84rem;">{{ $aiTriageStats['pending'] ?? 0 }}</span>
                </div>
            </div>

            <div class="mt-3 pt-3" style="border-top:1px solid var(--mp-hairline);">
                <div style="font-size:0.68rem; text-transform:uppercase; letter-spacing:0.14em; color:#7D8696; font-weight:600; margin-bottom:0.5rem;">By Department</div>
                @foreach(['Front Desk', 'Maintenance', 'Housekeeping'] as $dept)
                <div class="d-flex justify-content-between mt-1.5">
                    <span style="font-size:0.82rem; color:#C8C2B5;">{{ $dept }}</span>
                    <span class="mp-num" style="color:#E6CA7A; font-weight:600; font-size:0.84rem;">{{ $byDepartment[$dept] ?? 0 }}</span>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Food Order Stats --}}
    <div class="col-md-4">
        <div class="table-card p-4 h-100 d-flex flex-column justify-content-between">
            <div>
                <h2 class="mb-3 pb-2" style="font-family:var(--mp-font-display); font-size:1.02rem; font-weight:600; color:var(--mp-ivory); border-bottom:1px solid var(--mp-hairline);">
                    <i class="bi bi-cup-hot text-gold me-2"></i>Food Orders &amp; Revenue
                </h2>
                @foreach([
                    'pending'   => 'bi-hourglass-split',
                    'confirmed' => 'bi-patch-check',
                    'preparing' => 'bi-fire',
                    'ready'     => 'bi-bell',
                    'delivered' => 'bi-check2-all',
                    'cancelled' => 'bi-x-circle',
                ] as $status => $icon)
                <div class="d-flex align-items-center justify-content-between mb-2 px-3 py-2" style="background:rgba(255,255,255,0.025); border:1px solid var(--mp-hairline); border-radius:10px;">
                    <span style="font-size:0.83rem; color:#E7E2D7;"><i class="bi {{ $icon }} text-gold me-2"></i>{{ ucfirst($status) }}</span>
                    <span class="mp-num" style="background:rgba(201,162,74,0.12); border:1px solid rgba(201,162,74,0.28); border-radius:999px; padding:0.12rem 0.65rem; font-size:0.78rem; font-weight:600; color:#E6CA7A;">
                        {{ $orderStats[$status] ?? 0 }}
                    </span>
                </div>
                @endforeach
            </div>
            <div class="mt-3 pt-3" style="border-top:1px solid var(--mp-hairline);">
                <div class="d-flex justify-content-between align-items-baseline">
                    <span style="font-size:0.84rem; font-weight:600; color:#C8C2B5;">Delivered Revenue</span>
                    <span class="mp-num" style="font-family:var(--mp-font-display); color:#86EFAC; font-size:1.25rem; font-weight:600;">₱{{ number_format($totalRevenue, 2) }}</span>
                </div>
                <div class="d-flex justify-content-between align-items-center mt-2 pt-2" style="border-top:1px dashed var(--mp-hairline);">
                    <span style="font-size:0.8rem; color:#C8C2B5;">
                        <i class="bi bi-star-fill text-gold me-1"></i>Guest Satisfaction
                    </span>
                    <span class="mp-num" style="font-size:0.84rem; font-weight:600; color:#E6CA7A;">
                        @if(!empty($satisfactionStats['count']))
                            {{ number_format($satisfactionStats['average'], 1) }} / 5.0
                            <span style="color:#8C94A4; font-weight:400; font-size:0.75rem;">({{ $satisfactionStats['count'] }})</span>
                        @else
                            <span style="color:#8C94A4; font-weight:400; font-size:0.75rem;">No ratings yet</span>
                        @endif
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Recent Requests Audit Log --}}
@if(isset($recentRequests) && $recentRequests->count() > 0)
<div class="table-card mt-4">
    <div class="px-4 py-3 d-flex justify-content-between align-items-center" style="border-bottom:1px solid var(--mp-hairline);">
        <h2 class="m-0" style="font-family:var(--mp-font-display); font-size:1.02rem; font-weight:600; color:var(--mp-ivory);">
            Operational Audit Log
        </h2>
        <span style="font-size:0.74rem; color:#8C94A4;">Latest {{ $recentRequests->count() }} request(s)</span>
    </div>
    <div class="table-responsive">
        <table class="data-table align-middle">
            <thead>
                <tr>
                    <th>Reference</th>
                    <th>Room</th>
                    <th>Type</th>
                    <th>Priority</th>
                    <th>Department</th>
                    <th>Assigned Personnel</th>
                    <th>AI Triage</th>
                    <th>Status</th>
                    <th>Created</th>
                </tr>
            </thead>
            <tbody>
                @foreach($recentRequests as $req)
                <tr>
                    <td><code style="color:#E6CA7A; font-size:0.78rem;">{{ $req->reference_number }}</code></td>
                    <td>Room {{ $req->room?->room_number ?? 'N/A' }}</td>
                    <td>{{ ucfirst($req->type) }}</td>
                    <td><span class="badge bg-{{ $req->priority_badge }}">{{ $req->priority ?? 'Medium' }}</span></td>
                    <td>{{ $req->department ?? 'Front Desk' }}</td>
                    <td>{{ $req->assignedTo?->name ?? 'Unassigned' }}</td>
                    <td>{{ $req->aiTriage ? ucfirst($req->aiTriage->status) : '—' }}</td>
                    <td><span class="badge bg-{{ $req->status_badge }}">{{ ucwords(str_replace('_', ' ', $req->status)) }}</span></td>
                    <td style="font-size:0.75rem; color:#8C94A4;">{{ $req->created_at?->format('M d, Y g:i A') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

<div class="table-card p-3 mt-3">
    <div style="color:#8C94A4; font-size:0.8rem; text-align:center;">
        <i class="bi bi-calendar3 text-gold me-2"></i>
        Reporting window: <strong style="color:#E6CA7A;">{{ date('M d, Y', strtotime($dateFrom)) }}</strong>
        to <strong style="color:#E6CA7A;">{{ date('M d, Y', strtotime($dateTo)) }}</strong>
    </div>
</div>
@endsection
