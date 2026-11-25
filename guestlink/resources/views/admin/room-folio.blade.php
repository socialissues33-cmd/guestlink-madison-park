@extends('layouts.app')

@section('title', 'Room ' . $room->room_number . ' Folio')
@section('page-title', 'Room Folio — Room ' . $room->room_number)

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4 no-print">
    <div>
        <h5 class="fw-bold mb-1" style="font-family:var(--mp-font-display); color:#F6F1E7;">
            <i class="bi bi-receipt-cutoff me-2 text-gold"></i>Guest Room Folio
        </h5>
        <p class="small mb-0" style="color:#98A0AE;">
            Room {{ $room->room_number }} &middot; {{ $room->room_type }} (Floor {{ $room->floor }})
            @if($room->checked_in_at)
                &middot; Stay session since <strong style="color:#E7E2D7;">{{ $room->checked_in_at->format('M d, Y g:i A') }}</strong>
            @else
                &middot; All delivered in-room dining charges
            @endif
        </p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('admin.rooms') }}"
           onclick="if (window.history.length > 1 && document.referrer && document.referrer.indexOf(window.location.origin) === 0 && document.referrer !== window.location.href) { event.preventDefault(); window.history.back(); }"
           class="btn-ghost px-3 py-1.5 text-decoration-none d-inline-flex align-items-center gap-1">
            <i class="bi bi-arrow-left"></i> Back
        </a>
        <a href="{{ route('admin.rooms') }}" class="btn-ghost px-3 py-1.5 text-decoration-none d-inline-flex align-items-center gap-1">
            <i class="bi bi-door-open"></i> Rooms
        </a>
        <button type="button" onclick="window.print()" class="btn-gold px-3 py-1.5 d-inline-flex align-items-center gap-1">
            <i class="bi bi-printer"></i> Print Folio
        </button>
    </div>
</div>

<div class="table-card folio-sheet" style="max-width: 840px; margin: 0 auto; overflow: hidden;">
    <div class="py-4 px-4" style="background: #090E18; color: #F7F3EC; border-bottom: 1px solid rgba(201,162,74,0.28);">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div>
                <div style="font-size: 0.7rem; letter-spacing: 0.16em; text-transform: uppercase; color: #C9A24A; font-weight: 700;">
                    Madison Park Hotel &middot; Official Statement
                </div>
                <h4 class="mb-1 mt-1 fw-bold" style="font-family:var(--mp-font-display); color: #F7F3EC;">Room {{ $room->room_number }} Folio</h4>
                <div style="font-size: 0.82rem; color: rgba(247,243,236,0.72);">
                    {{ $room->room_type }} &middot; Floor {{ $room->floor }} &middot; Status: {{ $room->is_active ? 'Occupied / Active' : 'Vacant / Inactive' }}
                </div>
            </div>
            <div class="text-end" style="font-size: 0.8rem; color: rgba(247,243,236,0.78);">
                <div><strong>Issued:</strong> {{ now()->format('M d, Y g:i A') }}</div>
                @if($room->checked_in_at)
                    <div><strong>Check-In:</strong> {{ $room->checked_in_at->format('M d, Y g:i A') }}</div>
                @endif
                <div><strong>Service Requests:</strong> {{ $stayRequestsCount }} logged</div>
            </div>
        </div>
    </div>

    <div class="p-4">
        <h6 class="text-uppercase fw-bold small mb-3" style="letter-spacing: 0.1em; color: #C9A24A;">
            Delivered In-Room Dining Charges
        </h6>

        @if($deliveredOrders->isEmpty())
            <div class="text-center py-5" style="color:#8C94A4;">
                <i class="bi bi-receipt fs-1 d-block mb-2 opacity-25"></i>
                <p class="mb-1 fw-semibold" style="color:#E7E2D7;">No delivered dining charges for this stay session.</p>
                <small>Only orders marked <strong>Delivered</strong> appear on the guest folio.</small>
            </div>
        @else
            <div class="table-responsive">
                <table class="data-table align-middle mb-4">
                    <thead>
                        <tr>
                            <th>Date / Ref</th>
                            <th>Items &amp; Billing</th>
                            <th class="text-end">Amount (PHP)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($deliveredOrders as $ord)
                        @php
                            $ordPayDirect = method_exists($ord, 'isPayOnArrival') && $ord->isPayOnArrival();
                            $ordDineIn = method_exists($ord, 'isDineIn') && $ord->isDineIn();
                        @endphp
                        <tr>
                            <td style="white-space: nowrap;">
                                <code style="color:#E6CA7A; font-size:0.82rem; font-weight:600;">{{ $ord->reference_number }}</code>
                                <div style="font-size: 0.72rem; color:#8C94A4;">{{ $ord->created_at->format('M d, g:i A') }}</div>
                            </td>
                            <td>
                                <ul class="list-unstyled mb-1 small">
                                    @foreach($ord->orderItems as $oi)
                                        <li style="color:#E7E2D7;">
                                            {{ $oi->quantity }}× {{ $oi->foodItem?->name ?? 'Menu Item' }}
                                            <span style="color:#8C94A4;">(@ ₱{{ number_format((float)$oi->unit_price, 2) }})</span>
                                        </li>
                                    @endforeach
                                </ul>
                                <div class="d-flex flex-wrap gap-2" style="font-size:0.7rem;">
                                    <span style="color:#98A0AE;">
                                        <i class="bi {{ $ordDineIn ? 'bi-shop' : 'bi-door-open' }} me-1"></i>{{ $ordDineIn ? 'Times Square Dine-In' : 'Room Delivery' }}
                                    </span>
                                    @if($ordPayDirect)
                                        <span style="color:#6ee7b7; font-weight:600;">
                                            <i class="bi bi-check-circle me-1"></i>Settled ({{ $ordDineIn ? 'Cashier' : 'On Delivery' }})
                                        </span>
                                    @else
                                        <span style="color:#E6CA7A; font-weight:600;">
                                            <i class="bi bi-receipt-cutoff me-1"></i>Charged to Room
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td class="text-end fw-semibold" style="white-space:nowrap; color:{{ $ordPayDirect ? '#8C94A4' : '#F6F1E7' }};">
                                ₱{{ number_format((float)$ord->total_amount, 2) }}
                                @if($ordPayDirect)
                                    <div style="font-size:0.68rem; color:#6ee7b7; font-weight:500;">Paid</div>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <div class="pt-3 mt-2 ms-auto" style="max-width: 360px; border-top: 1px solid rgba(246,241,231,0.1);">
            <div class="d-flex justify-content-between mb-2 small">
                <span style="color:#98A0AE;">Room-Charged Dining Subtotal</span>
                <span class="fw-semibold" style="color:#E7E2D7;">₱{{ number_format($subtotal, 2) }}</span>
            </div>
            <div class="d-flex justify-content-between mb-2 small">
                <span style="color:#98A0AE;">Service Charge (10%)</span>
                <span class="fw-semibold" style="color:#E7E2D7;">₱{{ number_format($serviceCharge, 2) }}</span>
            </div>
            <div class="d-flex justify-content-between pt-2.5 mt-2" style="font-size: 1.08rem; border-top: 1px solid rgba(201,162,74,0.3);">
                <span class="fw-bold" style="color:#F6F1E7;">Balance Due at Check-Out</span>
                <span class="fw-bold" style="color: #E6CA7A;">₱{{ number_format($grandTotal, 2) }}</span>
            </div>
            @if(($paidDirectlyTotal ?? 0) > 0)
            <div class="d-flex justify-content-between mt-2 pt-2 small" style="border-top:1px dashed rgba(246,241,231,0.1); color:#6ee7b7;">
                <span>Already Settled (Delivery / Cashier)</span>
                <span class="fw-semibold">₱{{ number_format($paidDirectlyTotal, 2) }}</span>
            </div>
            @endif
        </div>
    </div>

    <div class="px-4 py-3 d-flex justify-content-between align-items-center small" style="background:rgba(8,12,20,0.8); border-top:1px solid rgba(246,241,231,0.08); color:#8C94A4;">
        <span>Madison Park Hotel &middot; GuestLink Digital Concierge</span>
        <span>Thank you for staying with us.</span>
    </div>
</div>

<style>
@media print {
    body, .main-content, .page-content { background: #fff !important; color: #111 !important; }
    .no-print, .sidebar, .topbar, nav { display: none !important; }
    .main-content, main { margin: 0 !important; padding: 0 !important; width: 100% !important; }
    .folio-sheet { background: #fff !important; color: #111 !important; box-shadow: none !important; border: 1px solid #ddd !important; }
    .folio-sheet * { color: #111 !important; border-color: #ddd !important; background: transparent !important; }
}
</style>
@endsection
