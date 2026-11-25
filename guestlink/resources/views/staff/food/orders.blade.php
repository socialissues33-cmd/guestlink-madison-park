@extends('layouts.app')
@section('title', 'Food Orders')
@section('page-title', 'Food Orders Queue')

@push('styles')
<style>
    .clickable-order-row {
        cursor: pointer;
        transition: all 0.15s ease-in-out;
    }
    .clickable-order-row:hover {
        background: rgba(201, 168, 76, 0.09) !important;
        transform: scale(1.001);
    }
    .search-control-wrap {
        position: relative;
        min-width: min(280px, 100%);
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
        padding-right: 32px !important;
        background: rgba(11, 20, 34, 0.75) !important;
        border: 1px solid rgba(201, 168, 76, 0.25) !important;
        color: #fff !important;
        border-radius: 20px;
        font-size: 0.82rem;
    }
    .search-control-wrap input:focus {
        border-color: var(--gold) !important;
        box-shadow: 0 0 10px rgba(201, 168, 76, 0.3) !important;
    }
</style>
@endpush

@section('content')
{{-- Filter Tabs & Search Bar --}}
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
    @php
        $currStatus = request('status', '');
        $statusTabs = [
            ''          => ['label' => 'All Orders', 'icon' => 'bi-receipt'],
            'pending'   => ['label' => 'Pending',    'icon' => 'bi-hourglass-split'],
            'confirmed' => ['label' => 'Confirmed',  'icon' => 'bi-check2-circle'],
            'preparing' => ['label' => 'In Kitchen', 'icon' => 'bi-fire'],
            'ready'     => ['label' => 'Ready',      'icon' => 'bi-bell'],
            'delivered' => ['label' => 'Delivered',  'icon' => 'bi-box2-heart'],
            'cancelled' => ['label' => 'Cancelled',  'icon' => 'bi-slash-circle'],
        ];
    @endphp
    <div class="filter-segmented d-inline-flex p-1 flex-wrap">
        @foreach($statusTabs as $val => $tab)
        <a href="{{ route('food.orders', array_merge(request()->except(['status', 'page']), $val ? ['status'=>$val] : [])) }}"
           class="filter-segment-btn {{ $currStatus === $val || (!$currStatus && $val === '') ? 'active' : '' }}">
            <i class="bi {{ $tab['icon'] }} me-1"></i> {{ $tab['label'] }}
        </a>
        @endforeach
    </div>

    {{-- Instant Live Search Bar --}}
    <div class="d-flex align-items-center gap-2 flex-grow-1 flex-md-grow-0">
        <form method="GET" action="{{ route('food.orders') }}" class="m-0 d-flex align-items-center gap-2 w-100">
            @if($currStatus)
            <input type="hidden" name="status" value="{{ $currStatus }}">
            @endif
            <div class="search-control-wrap flex-grow-1">
                <i class="bi bi-search"></i>
                <input type="text"
                       id="foodOrderSearchInput"
                       name="search"
                       class="form-control"
                       placeholder="Search Ref, Room, Guest, Dish..."
                       value="{{ request('search') }}"
                       onkeyup="liveFilterOrders(this.value)"
                       autocomplete="off">
            </div>
            @if(request('search'))
            <a href="{{ route('food.orders', $currStatus ? ['status' => $currStatus] : []) }}" class="btn-ghost px-2 py-1 text-decoration-none" style="font-size:0.75rem; border-radius:15px;" title="Clear Search">
                ✕ Clear
            </a>
            @endif
        </form>
    </div>
</div>

<div class="table-card">
    <div class="table-responsive">
        <table class="data-table align-middle" id="foodOrdersTable">
            <thead>
                <tr>
                    <th style="font-size:0.75rem; text-transform:uppercase; letter-spacing:1px;">Ticket Ref</th>
                    <th style="font-size:0.75rem; text-transform:uppercase; letter-spacing:1px;">Room</th>
                    <th class="d-none d-xxl-table-cell" style="font-size:0.75rem; text-transform:uppercase; letter-spacing:1px;">Guest</th>
                    <th style="font-size:0.75rem; text-transform:uppercase; letter-spacing:1px;">Order Summary</th>
                    <th style="font-size:0.75rem; text-transform:uppercase; letter-spacing:1px;">Total</th>
                    <th style="font-size:0.75rem; text-transform:uppercase; letter-spacing:1px;">Status</th>
                    <th style="font-size:0.75rem; text-transform:uppercase; letter-spacing:1px;">Elapsed</th>
                    <th style="text-align:right; font-size:0.75rem; text-transform:uppercase; letter-spacing:1px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $order)
                <tr class="clickable-order-row"
                    data-ref="{{ $order->reference_number }}"
                    onclick="window.location='{{ route('food.orders.show', $order) }}'"
                    title="Click row to view Order #{{ $order->reference_number }}">
                    <td style="white-space:nowrap;">
                        <a href="{{ route('food.orders.show', $order) }}" style="text-decoration:none;" onclick="event.stopPropagation();">
                            <code style="color:var(--gold); font-size:0.76rem; font-weight:700;">{{ $order->reference_number }}</code>
                        </a>
                        <div class="d-xxl-none" style="font-size:0.68rem; color:#8899AA; max-width:118px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; margin-top:2px;">
                            {{ $order->guest_name ?? 'In-House Guest' }}
                        </div>
                    </td>
                    <td style="white-space:nowrap;">
                        <span class="room-badge-formal">
                            Room {{ $order->room?->room_number ?? '201' }}
                        </span>
                        <div style="font-size:0.68rem; margin-top:3px;">
                            @if($order->isDineIn())
                                <span style="color:#E6CA7A; font-weight:600;"><i class="bi bi-shop me-1"></i>Times Sq. Dine-In</span>
                            @else
                                <span style="color:var(--muted);"><i class="bi bi-door-open me-1"></i>Room Delivery</span>
                            @endif
                        </div>
                    </td>
                    <td class="d-none d-xxl-table-cell" style="font-size:0.8rem; color:#fff; max-width:110px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                        {{ $order->guest_name ?? 'In-House Guest' }}
                    </td>
                    <td style="max-width:168px;">
                        <div style="font-size:0.8rem; color:#ddd; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                            @foreach($order->orderItems->take(2) as $it)
                                {{ $it->quantity }}x {{ $it->foodItem?->name ?? 'Dish' }}{{ !$loop->last ? ',' : '' }}
                            @endforeach
                            @if($order->orderItems->count() > 2)
                                <span style="color:var(--muted); font-size:0.72rem;">+{{ $order->orderItems->count() - 2 }} more</span>
                            @endif
                        </div>
                        @if($order->notes)
                        <div style="font-size:0.72rem; color:var(--gold-light); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                            <i class="bi bi-chat-quote me-1"></i>"{{ $order->notes }}"
                        </div>
                        @endif
                    </td>
                    <td style="white-space:nowrap;">
                        <span style="color:#F7F2E7; font-weight:700; font-size:0.88rem;">
                            ₱{{ number_format($order->total_amount, 2) }}
                        </span>
                        <div style="font-size:0.66rem; margin-top:2px;">
                            @if($order->isPayOnArrival())
                                <span style="color:#6ee7b7; font-weight:600;">
                                    <i class="bi bi-cash-coin me-1"></i>{{ $order->isDineIn() ? 'Pay at Cashier' : 'Collect Cash/GCash' }}
                                </span>
                            @else
                                <span style="color:#98A0AE;">
                                    <i class="bi bi-receipt-cutoff me-1"></i>Room Folio (FO)
                                </span>
                            @endif
                        </div>
                    </td>
                    <td>
                        <span class="badge bg-{{ $order->status_badge }} order-status-badge">
                            {{ ucfirst($order->status) }}
                        </span>
                    </td>
                    <td>
                        @php
                            $diffMins = (int) round($order->created_at->diffInMinutes());
                            if ($diffMins < 60) {
                                $timeStr = "{$diffMins}m ago";
                            } elseif ($diffMins < 1440) {
                                $hours = floor($diffMins / 60);
                                $rem = $diffMins % 60;
                                $timeStr = $rem > 0 ? "{$hours}h {$rem}m ago" : "{$hours}h ago";
                            } else {
                                $days = floor($diffMins / 1440);
                                $timeStr = "{$days}d ago";
                            }
                            $color = $diffMins > 25 ? '#fda4af' : ($diffMins > 12 ? '#fde68a' : 'var(--muted)');
                        @endphp
                        <span style="color:{{ $color }}; font-size:0.75rem; font-weight:500;" title="{{ $order->created_at->format('M d, Y h:i A') }}">
                            <i class="bi bi-clock me-1"></i>{{ $timeStr }}
                        </span>
                    </td>
                    <td style="text-align:right; white-space:nowrap;" onclick="event.stopPropagation();">
                        <div class="d-inline-flex gap-2 align-items-center">
                            <a href="{{ route('food.orders.show', $order) }}" class="action-link-formal">
                                Ticket &rarr;
                            </a>
                            @if(!in_array($order->status, ['delivered','cancelled']))
                            <form method="POST" action="{{ route('food.orders.status', $order) }}" class="m-0">
                                @csrf
                                @php
                                    $next = ['pending'=>'confirmed','confirmed'=>'preparing','preparing'=>'ready','ready'=>'delivered'];
                                    $nextIcons = [
                                        'confirmed' => 'bi-check2',
                                        'preparing' => 'bi-fire',
                                        'ready'     => 'bi-bell',
                                        'delivered' => 'bi-check-all'
                                    ];
                                    $nextStatus = $next[$order->status] ?? null;
                                @endphp
                                @if($nextStatus)
                                <input type="hidden" name="status" value="{{ $nextStatus }}">
                                <button type="submit" class="btn-gold px-2 py-1 d-inline-flex align-items-center gap-1" style="font-size:0.72rem; white-space:nowrap;">
                                    <i class="bi {{ $nextIcons[$nextStatus] ?? 'bi-arrow-right' }}"></i>
                                    <span>{{ ucfirst($nextStatus) }}</span>
                                </button>
                                @endif
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr id="emptyRow">
                    <td colspan="8" style="text-align:center; color:#8899AA; padding:3.5rem;">
                        <div style="font-size:1.75rem; margin-bottom:0.5rem; opacity:0.55;"><i class="bi bi-cup-hot"></i></div>
                        No food orders match your criteria.
                    </td>
                </tr>
                @endforelse
                <tr id="orderNoMatchRow" style="display:none;">
                    <td colspan="8" style="text-align:center; color:#8899AA; padding:2.5rem;">No matching food orders found.</td>
                </tr>
            </tbody>
        </table>
    </div>
    @if($orders->hasPages())
    <div class="p-3 border-top" style="border-color:rgba(255,255,255,0.08) !important;">
        {{ $orders->links() }}
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
    function liveFilterOrders(keyword) {
        const term = (keyword || '').toLowerCase().trim();
        const table = document.getElementById('foodOrdersTable');
        if (!table) return;
        const rows = table.querySelectorAll('tbody tr.clickable-order-row');
        let visibleCount = 0;

        rows.forEach(row => {
            const text = row.innerText.toLowerCase();
            if (!term || text.includes(term)) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        const emptyRow = document.getElementById('orderNoMatchRow') || document.getElementById('emptyRow');
        if (emptyRow) {
            emptyRow.style.display = (visibleCount === 0 && rows.length > 0) ? '' : 'none';
        }
    }
</script>
@endpush
