@extends('layouts.app')
@section('title', 'Order #' . $foodOrder->reference_number)
@section('page-title', 'Kitchen Order Ticket #' . $foodOrder->reference_number)

@section('content')
<div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2 no-print">
    <a href="{{ route('food.orders') }}" class="btn-ghost px-3 py-1 text-decoration-none d-inline-flex align-items-center gap-1" style="font-size:0.8rem;">
        <i class="bi bi-arrow-left"></i> Back to Orders
    </a>
    <div class="d-flex align-items-center gap-2">
        <button type="button" onclick="window.print()" class="btn-ghost px-3 py-1 d-inline-flex align-items-center gap-1" style="font-size:0.8rem;">
            <i class="bi bi-printer"></i> Print Kitchen / Delivery Slip
        </button>
        <span class="badge bg-{{ $foodOrder->status_badge }} px-3 py-2" style="font-size:0.82rem;">
            {{ ucfirst($foodOrder->status) }}
        </span>
    </div>
</div>

{{-- Visual 5-Step Order Progress Stepper --}}
@php
    $isDineIn = $foodOrder->isDineIn();
    $isPayDirect = $foodOrder->isPayOnArrival();
    $steps = [
        'pending'   => ['label' => 'Order Placed', 'icon' => '1'],
        'confirmed' => ['label' => 'Confirmed',    'icon' => '2'],
        'preparing' => ['label' => 'Cooking',      'icon' => '3'],
        'ready'     => ['label' => 'Ready',        'icon' => '4'],
        'delivered' => ['label' => $isDineIn ? 'Served' : 'Delivered', 'icon' => '5'],
    ];
    $statusOrder = array_keys($steps);
    $currentIndex = array_search($foodOrder->status, $statusOrder);
    if ($currentIndex === false && $foodOrder->status === 'cancelled') {
        $currentIndex = -1;
    }
    $progressPercent = $currentIndex >= 0 ? ($currentIndex / (count($steps) - 1)) * 100 : 0;
@endphp

@if($foodOrder->status !== 'cancelled')
<div class="table-card p-4 mb-4">
    <div class="stepper-track">
        <div class="stepper-line"></div>
        <div class="stepper-progress" style="width: calc({{ $progressPercent }}% * 0.88);"></div>
        @foreach($steps as $key => $s)
            @php
                $idx = array_search($key, $statusOrder);
                $isCompleted = $idx < $currentIndex;
                $isActive = $idx === $currentIndex;
            @endphp
            <div class="step-item {{ $isActive ? 'active' : ($isCompleted ? 'completed' : '') }}">
                <div class="step-circle">
                    @if($isCompleted)
                        <i class="bi bi-check"></i>
                    @else
                        {{ $s['icon'] }}
                    @endif
                </div>
                <div class="step-label">{{ $s['label'] }}</div>
            </div>
        @endforeach
    </div>
</div>
@else
<div class="alert alert-danger-custom mb-4">
    <i class="bi bi-x-circle me-2"></i> This order was cancelled.
</div>
@endif

<div class="row g-3">
    {{-- Left Column: Kitchen Order Ticket --}}
    <div class="col-lg-8">
        {{-- KDS Receipt Ticket --}}
        <div class="table-card p-4 mb-3" style="border:1px solid rgba(201,168,76,0.35); position:relative;">
            <div class="d-flex justify-content-between align-items-start border-bottom pb-3 mb-3 flex-wrap gap-2" style="border-color:rgba(201,168,76,0.2) !important;">
                <div class="d-flex align-items-center gap-3">
                    <div style="width:52px; height:52px; border-radius:12px; background:rgba(201,168,76,0.15); border:1px solid rgba(201,168,76,0.3); display:flex; align-items:center; justify-content:center; font-size:1.6rem; color:#C9A84C;">
                        <i class="bi bi-cup-hot"></i>
                    </div>
                    <div>
                        <div style="font-family:'Playfair Display',serif; font-size:1.4rem; font-weight:700; color:#fff;">
                            Times Square Dining Ticket
                        </div>
                        <code style="color:#C9A84C; font-size:0.85rem; font-weight:700;">{{ $foodOrder->reference_number }}</code>
                    </div>
                </div>
                <div class="text-end">
                    <div style="font-size:0.7rem; color:#8899AA; text-transform:uppercase; letter-spacing:1px;">
                        {{ $isDineIn ? 'Pre-Order · Dine-In' : 'Room Delivery' }}
                    </div>
                    <div style="font-size:1.35rem; font-weight:800; color:#E8D08D;">
                        {{ $isDineIn ? 'TIMES SQUARE' : 'ROOM ' . ($foodOrder->room?->room_number ?? '201') }}
                    </div>
                    <div style="font-size:0.72rem; color:#8899AA;">
                        @if($isDineIn)
                            Ground Floor Restaurant &bull; Guest from Room {{ $foodOrder->room?->room_number ?? '201' }}
                        @else
                            Floor {{ $foodOrder->room?->floor ?? 2 }} &bull; {{ $foodOrder->room?->room_type ?? 'Standard' }}
                        @endif
                    </div>
                </div>
            </div>

            {{-- Guest Info & Timestamp --}}
            <div class="row g-2 mb-3">
                <div class="col-sm-6">
                    <div style="font-size:0.72rem; color:#8899AA;">Guest Name:</div>
                    <div style="font-weight:700; font-size:0.95rem; color:#fff;">{{ $foodOrder->guest_name ?? 'In-House Guest' }}</div>
                </div>
                <div class="col-sm-6 text-sm-end">
                    <div style="font-size:0.72rem; color:#8899AA;">Order Received:</div>
                    <div style="font-weight:600; font-size:0.88rem; color:#E2E8F0;">
                        {{ $foodOrder->created_at->format('M d, Y \a\t g:i A') }} ({{ $foodOrder->created_at->diffForHumans() }})
                    </div>
                </div>
            </div>

            {{-- Fulfillment & Payment Instruction Strip --}}
            <div class="row g-2 mb-3">
                <div class="col-sm-6">
                    <div class="p-2.5 rounded-3 h-100" style="background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.09);">
                        <div style="font-size:0.66rem; color:#8899AA; text-transform:uppercase; letter-spacing:0.8px;">Fulfillment Mode</div>
                        <div style="font-size:0.86rem; font-weight:700; color:#F6F1E7; margin-top:2px;">
                            <i class="bi {{ $isDineIn ? 'bi-shop' : 'bi-door-open' }} me-1" style="color:#E6CA7A;"></i>
                            {{ $foodOrder->dining_mode_label }}
                        </div>
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="p-2.5 rounded-3 h-100" style="background:{{ $isPayDirect ? 'rgba(16,185,129,0.1)' : 'rgba(201,168,76,0.09)' }}; border:1px solid {{ $isPayDirect ? 'rgba(16,185,129,0.35)' : 'rgba(201,168,76,0.3)' }};">
                        <div style="font-size:0.66rem; color:{{ $isPayDirect ? '#6ee7b7' : '#E6CA7A' }}; text-transform:uppercase; letter-spacing:0.8px;">Payment Instruction</div>
                        <div style="font-size:0.86rem; font-weight:700; color:#fff; margin-top:2px;">
                            <i class="bi {{ $isPayDirect ? 'bi-cash-coin' : 'bi-receipt-cutoff' }} me-1" style="color:{{ $isPayDirect ? '#6ee7b7' : '#E6CA7A' }};"></i>
                            {{ $foodOrder->payment_method_label }}
                        </div>
                    </div>
                </div>
            </div>

            {{-- Special Instructions Callout --}}
            @if($foodOrder->notes)
            <div class="p-3 mb-3 rounded-3" style="background:rgba(245,158,11,0.12); border:1px solid rgba(245,158,11,0.35); color:#fde68a;">
                <div class="d-flex align-items-center gap-2 mb-1" style="font-size:0.75rem; font-weight:700; text-transform:uppercase; letter-spacing:1px; color:#fbbf24;">
                    <i class="bi bi-exclamation-circle-fill"></i> Kitchen Special Instructions
                </div>
                <div style="font-size:0.9rem; font-weight:500;">"{{ $foodOrder->notes }}"</div>
            </div>
            @endif

            {{-- Itemized Receipt Table --}}
            <div style="background:rgba(11,20,34,0.7); border:1px solid rgba(255,255,255,0.08); border-radius:12px; padding:1.25rem; margin-bottom:1.5rem;">
                <div style="font-size:0.72rem; color:#C9A84C; font-weight:700; text-transform:uppercase; letter-spacing:1px; margin-bottom:0.75rem;">
                    Prepared Menu Items
                </div>
                @foreach($foodOrder->orderItems as $item)
                <div class="d-flex justify-content-between align-items-center py-2 border-bottom" style="border-color:rgba(255,255,255,0.06) !important;">
                    <div class="d-flex align-items-center gap-3">
                        <span style="width:28px; height:28px; border-radius:50%; background:rgba(201,168,76,0.2); border:1px solid rgba(201,168,76,0.4); display:flex; align-items:center; justify-content:center; font-weight:700; font-size:0.85rem; color:#E8D08D;">
                            {{ $item->quantity }}×
                        </span>
                        <div>
                            <div style="font-size:0.92rem; font-weight:600; color:#fff;">{{ $item->foodItem?->name ?? 'Dish' }}</div>
                            <div style="font-size:0.75rem; color:#8899AA;">₱{{ number_format($item->unit_price, 2) }} each</div>
                        </div>
                    </div>
                    <div style="color:#E8D08D; font-weight:700; font-size:0.95rem;">
                        ₱{{ number_format($item->subtotal, 2) }}
                    </div>
                </div>
                @endforeach

                <div class="d-flex justify-content-between align-items-center pt-3 mt-1">
                    <span style="font-size:1.05rem; font-weight:700; color:#fff;">Total Amount</span>
                    <span style="color:#C9A84C; font-size:1.35rem; font-weight:800;">₱{{ number_format($foodOrder->total_amount, 2) }}</span>
                </div>
            </div>

            {{-- Guest Dining Rating & Feedback (if rated) --}}
            @if(!empty($foodOrder->guest_rating))
            <div class="p-3 mb-3 rounded-3" style="background:rgba(201,168,76,0.12); border:1px solid rgba(201,168,76,0.35);">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <span style="color:#E8D08D; font-size:0.75rem; font-weight:700; text-transform:uppercase; letter-spacing:1px;">
                        <i class="bi bi-star-fill text-warning me-1"></i>Guest Dining Rating
                    </span>
                    <span style="color:#FBBF24; font-size:0.9rem; font-weight:700;">
                        @for($i = 1; $i <= 5; $i++)
                            <i class="bi {{ $i <= $foodOrder->guest_rating ? 'bi-star-fill' : 'bi-star' }}"></i>
                        @endfor
                        <span class="ms-1 text-light" style="font-size:0.8rem;">({{ $foodOrder->guest_rating }} / 5)</span>
                    </span>
                </div>
                @if(!empty($foodOrder->guest_feedback))
                <div class="mt-1.5" style="font-size:0.86rem; color:#E2E8F0; font-style:italic;">
                    "{{ $foodOrder->guest_feedback }}"
                </div>
                @endif
            </div>
            @endif

            {{-- One-Click Next Step Action Button & Forward-Only Lock --}}
            @php
                $isTerminalOrder = in_array($foodOrder->status, ['delivered', 'cancelled'], true);
                $stageRanks = \App\Services\StatusProgressionService::FOOD_ORDER_RANKS;
                $currentRank = $stageRanks[$foodOrder->status] ?? 0;
                $nextMap = [
                    'pending'   => ['status' => 'confirmed', 'label' => 'Accept & Confirm Order',    'icon' => 'bi-check2-circle', 'btn' => 'btn-gold'],
                    'confirmed' => ['status' => 'preparing', 'label' => 'Start Preparing in Kitchen', 'icon' => 'bi-fire',          'btn' => 'btn-gold'],
                    'preparing' => ['status' => 'ready',     'label' => $isDineIn ? 'Mark Ready for Times Square Table' : 'Mark Ready for Room Delivery', 'icon' => 'bi-bell', 'btn' => 'btn-gold'],
                    'ready'     => ['status' => 'delivered', 'label' => $isDineIn ? 'Confirm Served at Times Square (Complete)' : 'Confirm Room Delivery (Complete)', 'icon' => 'bi-box2-heart', 'btn' => 'btn-gold'],
                ];
                $nextAction = $nextMap[$foodOrder->status] ?? null;
                $minsInStage = $foodOrder->updated_at ? (int) $foodOrder->updated_at->diffInMinutes(now()) : 0;
                $minsUntilAuto = max(0, \App\Services\StatusProgressionService::STANDARD_STAGE_MINUTES - $minsInStage);
            @endphp

            @if($isTerminalOrder)
            <div class="p-3.5 rounded-3 mb-2" style="background:{{ $foodOrder->status === 'delivered' ? 'rgba(34,197,94,0.12)' : 'rgba(239,68,68,0.12)' }}; border:1px solid {{ $foodOrder->status === 'delivered' ? 'rgba(34,197,94,0.4)' : 'rgba(239,68,68,0.4)' }};">
                <div class="d-flex align-items-center gap-2 mb-1" style="font-weight:700; color:{{ $foodOrder->status === 'delivered' ? '#86efac' : '#fca5a5' }}; font-size:0.9rem;">
                    <i class="bi {{ $foodOrder->status === 'delivered' ? 'bi-patch-check-fill' : 'bi-lock-fill' }}"></i>
                    {{ $foodOrder->status === 'delivered' ? 'Order Completed & Permanently Locked' : 'Order Cancelled & Locked' }}
                </div>
                <div style="font-size:0.78rem; color:#cbd5e1;">
                    {{ $foodOrder->status === 'delivered'
                        ? 'Guest has received this order. Completed orders are permanently locked and cannot be reopened or moved backwards.'
                        : 'This order was cancelled and is permanently locked.' }}
                </div>
            </div>
            @else
                {{-- 30-Minute Standard Auto-Progression Notice --}}
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 px-3 py-2 mb-3 rounded-3 no-print" style="background:rgba(201,168,76,0.08); border:1px solid rgba(201,168,76,0.25); font-size:0.74rem; color:#E8D08D;">
                    <span>
                        <i class="bi bi-stopwatch me-1"></i>
                        @if($foodOrder->status === 'ready')
                            <strong>Final Stage:</strong> Ready for Room Delivery &mdash; <strong>manual touch required</strong> by Staff or Guest to Complete.
                        @else
                            <strong>Standard 30-Min Auto-Stage:</strong> Auto-advances to next stage in ~{{ $minsUntilAuto }} min (stops at Ready; manual touch required to Complete).
                        @endif
                    </span>
                </div>

                @if($nextAction)
                <form method="POST" action="{{ route('food.orders.status', $foodOrder) }}" class="mb-3 no-print">
                    @csrf
                    <input type="hidden" name="status" value="{{ $nextAction['status'] }}">
                    <div class="mb-2">
                        <input type="text" name="notes" class="form-control form-control-sm"
                               placeholder="Optional kitchen / delivery note for guest tracker..."
                               style="background:rgba(11,20,34,0.85); border:1px solid rgba(201,168,76,0.3); color:#fff; border-radius:8px; font-size:0.8rem;">
                    </div>
                    <button type="submit" class="{{ $nextAction['btn'] }} w-100 py-3 d-flex align-items-center justify-content-center gap-2" style="font-size:1rem; font-weight:700;">
                        <i class="bi {{ $nextAction['icon'] }} fs-5"></i>
                        <span>Next Stage: {{ $nextAction['label'] }}</span>
                    </button>
                </form>
                @endif

                {{-- Forward-Only Manual Status Progression Bar (Backward moves disabled) --}}
                <div class="pt-2 border-top no-print" style="border-color:rgba(255,255,255,0.08) !important;">
                    <div style="font-size:0.72rem; color:#8899AA; margin-bottom:0.5rem;">
                        Forward-Only Stage Controls <span style="opacity:0.7;">(cannot revert past completed stages)</span>:
                    </div>
                    <div class="d-flex gap-2 flex-wrap">
                        @foreach(['pending','confirmed','preparing','ready','delivered','cancelled'] as $s)
                        @php
                            $sRank = $stageRanks[$s] ?? 0;
                            $isCurrent = $foodOrder->status === $s;
                            $isBackward = $s !== 'cancelled' && $sRank < $currentRank;
                        @endphp
                        <form method="POST" action="{{ route('food.orders.status', $foodOrder) }}">
                            @csrf
                            <input type="hidden" name="status" value="{{ $s }}">
                            <button type="submit"
                                    class="{{ $isCurrent ? 'btn-gold' : 'btn-ghost' }} px-3 py-1"
                                    style="font-size:0.75rem; border-radius:20px; {{ $isBackward ? 'opacity:0.35; cursor:not-allowed;' : '' }}"
                                    {{ ($isCurrent || $isBackward) ? 'disabled' : '' }}
                                    title="{{ $isBackward ? 'Cannot move backwards past completed stage' : ucfirst($s) }}">
                                @if($isBackward)<i class="bi bi-lock-fill me-1" style="font-size:0.65rem;"></i>@endif
                                {{ ucfirst($s) }}
                            </button>
                        </form>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- Right Column: Status Timeline --}}
    <div class="col-lg-4 no-print">
        <div class="table-card p-4">
            <h2 style="font-family:'Playfair Display',serif; font-size:1.1rem; color:#fff; margin-bottom:1.25rem;">
                <i class="bi bi-clock-history me-2" style="color:var(--gold);"></i>Order Timeline
            </h2>
            @forelse($foodOrder->statusLogs->sortByDesc('created_at') as $log)
            <div style="border-left:2px solid rgba(201,168,76,0.4); padding-left:1.25rem; margin-bottom:1.25rem; position:relative;">
                <div style="position:absolute; left:-6px; top:2px; width:10px; height:10px; border-radius:50%; background:var(--gold); box-shadow:0 0 8px rgba(201,168,76,0.6);"></div>
                <div style="font-size:0.85rem; font-weight:700; color:#fff;">
                    {{ $log->from_status ? ucfirst($log->from_status) . ' → ' : '' }}
                    <span style="color:#E8D08D;">{{ ucfirst($log->to_status) }}</span>
                </div>
                @if(!empty($log->notes))
                <div style="font-size:0.78rem; color:#CBD5E1; font-style:italic; margin-top:0.2rem;">
                    "{{ $log->notes }}"
                </div>
                @endif
                <div style="font-size:0.72rem; color:#8899AA; margin-top:0.25rem;">
                    <i class="bi bi-person me-1"></i>{{ $log->changedBy?->name ?? 'System' }} &bull; {{ $log->created_at->format('M d, g:i A') }}
                </div>
            </div>
            @empty
            <div style="color:#8899AA; font-size:0.85rem; text-align:center; padding:2rem 1rem;">
                No status changes recorded yet.
            </div>
            @endforelse
        </div>
    </div>
</div>
@endsection

