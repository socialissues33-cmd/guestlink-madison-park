{{-- File: guestlink/resources/views/guest/track.blade.php --}}
@extends('layouts.guest')
@section('title', 'Track & Chat')

@push('styles')
<style>
    .step-node.completed {
        background: #1B1912;
        border-color: var(--mp-gold);
        color: var(--mp-gold-bright);
    }
    .step-node.current {
        background: var(--mp-metal);
        border-color: var(--mp-gold-bright);
        color: #140F04;
        box-shadow: 0 0 0 4px rgba(201, 162, 74, 0.16);
    }
    .step-node.upcoming {
        background: #0B101C;
        border-color: rgba(246, 241, 231, 0.16);
        color: #647082;
    }
    .track-tab-bar {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 0.25rem;
        padding: 0.25rem;
        border-radius: 14px;
        background: #0C1220;
        border: 1px solid var(--mp-hairline-md);
        overflow: hidden;
    }
    .track-tab-btn {
        width: 100%;
        min-width: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.28rem;
        padding: 0.52rem 0.25rem;
        border-radius: 10px;
        font-size: 11px;
        font-weight: 600;
        color: #8C94A4;
        background: transparent;
        border: 1px solid transparent;
        white-space: nowrap;
        overflow: hidden;
        cursor: pointer;
        transition: all 0.18s ease;
    }
    .track-tab-btn:hover {
        color: #F6F1E7;
    }
    .track-tab-btn.is-active {
        background: rgba(201, 162, 74, 0.16);
        border-color: rgba(201, 162, 74, 0.45);
        color: var(--mp-gold-bright);
    }
    .track-tab-count {
        flex-shrink: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 17px;
        height: 17px;
        padding: 0 4px;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.09);
        font-size: 10px;
        line-height: 1;
    }
    @media (max-width: 390px) {
        .track-tab-icon {
            display: none;
        }
        .track-tab-btn {
            font-size: 11px;
            padding: 0.5rem 0.2rem;
            gap: 0.22rem;
        }
    }
</style>
@endpush

@section('content')
@php
    $chatTarget = $activeChatRequest ?? $request_ ?? null;
    $chatRef = $chatTarget?->reference_number ?? ($refNumber ?: 'NEW');
    $chatMessages = (!empty($chatTarget) && !empty($chatTarget->messages)) ? $chatTarget->messages : collect([]);
    $totalHistoryCount = ($recentRequests ? $recentRequests->count() : 0) + ($recentOrders ? $recentOrders->count() : 0);
    $hasActiveItem = !empty($refNumber) && (!empty($request_) || !empty($order));
    $defaultTrackTab = $hasActiveItem ? 'status' : ($totalHistoryCount > 0 ? 'history' : 'chat');
@endphp
<div class="mp-shell pb-28 antialiased">

    {{-- 1. Shared Sticky Frosted Header --}}
    @include('guest.partials.header', ['subtitle' => 'Track & Chat'])

    <div class="px-4 pt-3.5 space-y-4">

        {{-- Compact Header + Integrated Reference Lookup --}}
        <section class="mp-surface p-4 space-y-3">
            <div class="flex items-center justify-between gap-2">
                <div class="min-w-0">
                    <p class="mp-eyebrow !text-[10px]">Room {{ $room->room_number }} &middot; Live Updates</p>
                    <h1 class="font-serif text-[1.2rem] font-semibold text-[#F6F1E7] leading-tight mt-0.5">
                        Order &amp; Request Tracker
                    </h1>
                </div>
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] bg-emerald-500/10 border border-emerald-500/25 text-emerald-300 shrink-0">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                    Live Sync
                </span>
            </div>

            <form method="GET" action="{{ route('guest.track', $token) }}" class="flex gap-2">
                <input type="text" id="refSearchInput" name="ref" value="{{ $refNumber }}"
                       placeholder="Enter REQ-... or ORD-... reference"
                       aria-label="Reference Number"
                       class="mp-input flex-1 !py-2 !px-3 !text-xs font-mono uppercase">
                <button type="submit" class="mp-btn !min-h-[38px] !px-3.5 !py-1.5 !text-xs shrink-0">
                    <i class="bi bi-search"></i>
                    <span>Find</span>
                </button>
            </form>
        </section>

        {{-- Clean 3-Tab Organizer (Zero Endless Scrolling, Mobile-Fit) --}}
        <div class="track-tab-bar" role="tablist" aria-label="Tracker sections">
            <button type="button" id="tabBtn-status" role="tab" onclick="switchTrackTab('status')"
                    class="track-tab-btn {{ $defaultTrackTab === 'status' ? 'is-active' : '' }}">
                <i class="bi bi-broadcast track-tab-icon shrink-0"></i>
                <span class="truncate">Live Status</span>
            </button>
            <button type="button" id="tabBtn-chat" role="tab" onclick="switchTrackTab('chat')"
                    class="track-tab-btn {{ $defaultTrackTab === 'chat' ? 'is-active' : '' }}">
                <i class="bi bi-chat-dots track-tab-icon shrink-0"></i>
                <span class="truncate">Desk Chat</span>
                @if(count($chatMessages) > 0)
                <span class="track-tab-count mp-num">{{ count($chatMessages) }}</span>
                @endif
            </button>
            <button type="button" id="tabBtn-history" role="tab" onclick="switchTrackTab('history')"
                    class="track-tab-btn {{ $defaultTrackTab === 'history' ? 'is-active' : '' }}">
                <i class="bi bi-receipt track-tab-icon shrink-0"></i>
                <span class="truncate">History</span>
                @if($totalHistoryCount > 0)
                <span class="track-tab-count mp-num">{{ $totalHistoryCount }}</span>
                @endif
            </button>
        </div>

        {{-- TAB PANEL 1: LIVE STATUS --}}
        <div id="trackPanel-status" class="space-y-4 {{ $defaultTrackTab === 'status' ? '' : 'hidden' }}">

        {{-- Active Ticket Tracked Display --}}
        @if($refNumber)

            {{-- 1. SERVICE OR MAINTENANCE REQUEST --}}
            @if($request_)
            <div id="trackedRequestCard" class="mp-surface p-5 space-y-5">

                @php
                    $isEventReq = method_exists($request_, 'isEventInquiry') && $request_->isEventInquiry();
                @endphp
                {{-- Ticket Top Bar --}}
                <div class="flex items-start justify-between gap-3 pb-3.5 border-b border-[color:var(--mp-hairline)]">
                    <div class="flex items-center gap-3 min-w-0">
                        <span class="mp-icon !w-10 !h-10">
                            <i class="bi {{ $isEventReq ? 'bi-calendar-event' : ($request_->type === 'maintenance' ? 'bi-wrench-adjustable' : 'bi-bell') }}"></i>
                        </span>
                        <div class="min-w-0">
                            <div class="font-serif font-semibold text-base text-[#F6F1E7] leading-tight">
                                {{ $isEventReq ? 'Event Inquiry & Hold' : ucfirst($request_->type) . ' Request' }}
                            </div>
                            <div class="text-[11px] font-mono text-[color:var(--mp-gold)] tracking-wider mt-0.5 truncate">
                                {{ $request_->reference_number }}
                            </div>
                        </div>
                    </div>
                    <span id="liveRequestBadge" data-status="{{ $request_->status }}"
                          class="mp-status-pill shrink-0 {{ in_array($request_->status, ['resolved', 'closed']) ? 'is-done' : '' }}">
                        {{ $isEventReq ? $request_->display_status : str_replace('_', ' ', $request_->status) }}
                    </span>
                </div>

                {{-- Visual Progress Stepper (Context-Aware for Event Inquiries vs Standard Requests) --}}
                @php
                    $reqSteps = $isEventReq
                        ? [
                            'pending'     => ['label' => 'Inquiry',        'icon' => 'bi-inbox'],
                            'assigned'    => ['label' => 'Tentative Hold', 'icon' => 'bi-pin-angle'],
                            'in_progress' => ['label' => 'FO Appt',        'icon' => 'bi-calendar2-check'],
                            'resolved'    => ['label' => 'Confirmed',      'icon' => 'bi-check-lg'],
                        ]
                        : [
                            'pending'     => ['label' => 'Received',    'icon' => 'bi-inbox'],
                            'assigned'    => ['label' => 'Assigned',    'icon' => 'bi-person-check'],
                            'in_progress' => ['label' => 'In Progress', 'icon' => 'bi-lightning-charge'],
                            'resolved'    => ['label' => 'Resolved',    'icon' => 'bi-check-lg'],
                        ];
                    $statusOrder = ['pending', 'assigned', 'in_progress', 'resolved'];
                    $reqKey = in_array($request_->status, ['resolved', 'closed']) ? 'resolved' : $request_->status;
                    $currentIdx = array_search($reqKey, $statusOrder);
                    if ($currentIdx === false) $currentIdx = 0;
                    $progressPct = ($currentIdx / (count($statusOrder) - 1)) * 100;
                @endphp

                <div class="py-1 px-1">
                    <div class="relative flex items-center justify-between">
                        <div class="absolute left-5 right-5 top-4 h-[2px] bg-white/10"></div>
                        <div id="liveRequestProgressBar"
                             class="absolute left-5 top-4 h-[2px] transition-all duration-500"
                             style="width: calc((100% - 2.5rem) * {{ $progressPct / 100 }}); background: var(--mp-gold);"></div>

                        @foreach($reqSteps as $key => $step)
                        @php
                            $stepIdx = array_search($key, $statusOrder);
                            $nodeClass = $stepIdx < $currentIdx ? 'completed' : ($stepIdx === $currentIdx ? 'current' : 'upcoming');
                        @endphp
                        <div class="tracker-step relative z-10 flex flex-col items-center" data-step="{{ $key }}">
                            <div class="step-node {{ $nodeClass }} w-8 h-8 rounded-full border flex items-center justify-center text-xs transition-all duration-300">
                                <i class="bi {{ $stepIdx < $currentIdx ? 'bi-check-lg' : $step['icon'] }}"></i>
                            </div>
                            <span class="text-[11px] mt-1.5 {{ $stepIdx <= $currentIdx ? 'text-[#F6F1E7] font-medium' : 'text-[#6E7788]' }}">
                                {{ $step['label'] }}
                            </span>
                        </div>
                        @endforeach
                    </div>
                </div>

                @if($isEventReq && !in_array($request_->status, ['resolved', 'closed'], true))
                <div class="mp-note">
                    <i class="bi bi-building-check text-[color:var(--mp-gold)] text-sm mt-0.5 shrink-0"></i>
                    <p class="text-[11.5px] text-[#C8C2B5] leading-relaxed">
                        <strong class="text-[color:var(--mp-gold-bright)]">Visit Front Office (Ground Floor Lobby):</strong>
                        Your event date is logged as a tentative hold only. Kindly proceed to the Front Desk or reply in chat below to arrange your in-person appointment &amp; hall viewing.
                    </p>
                </div>
                @endif

                {{-- Description & Attached Photo --}}
                <div class="p-3.5 rounded-xl bg-[#080C14]/85 border border-[color:var(--mp-hairline)] space-y-2.5">
                    <div class="mp-label !mb-0">Details</div>
                    <div class="text-[13px] text-[#E7E2D7] leading-relaxed">{{ $request_->description }}</div>

                    @if(!empty($request_->photo_path))
                    <div class="pt-2.5 border-t border-[color:var(--mp-hairline)]">
                        <div class="mp-label !mb-2 flex items-center gap-1.5">
                            <i class="bi bi-camera text-[color:var(--mp-gold)]"></i> Attached Photo
                        </div>
                        <a href="{{ asset($request_->photo_path) }}" target="_blank" class="block rounded-xl overflow-hidden border border-[color:var(--mp-hairline-md)] max-h-48 group relative">
                            <img src="{{ asset($request_->photo_path) }}" alt="Attached maintenance defect" class="w-full max-h-48 object-cover group-hover:scale-[1.02] transition-transform duration-300">
                            <span class="mp-photo-badge absolute bottom-2 right-2 bg-[#080C14]/80 backdrop-blur-sm text-[11px] text-[#F6F1E7] px-2.5 py-1 rounded-lg border border-white/15">
                                <i class="bi bi-zoom-in mr-1"></i>View
                            </span>
                        </a>
                    </div>
                    @endif
                </div>

                {{-- Meta Tags --}}
                <div class="flex flex-wrap gap-1.5 text-xs">
                    @if(!empty($request_->category))
                    <span class="px-2.5 py-1 rounded-lg bg-white/[0.04] border border-[color:var(--mp-hairline-md)] text-[#E7E2D7] text-[11px] font-medium">
                        {{ $request_->category->name }}
                    </span>
                    @endif
                    @if(!empty($request_->priority))
                    <span id="liveRequestPriority" class="px-2.5 py-1 rounded-lg bg-white/[0.04] border border-[color:var(--mp-hairline-md)] text-[color:var(--mp-gold-bright)] text-[11px] font-medium">
                        Priority: {{ ucfirst($request_->priority) }}
                    </span>
                    @endif
                    @if(!empty($request_->department))
                    <span id="liveRequestDept" class="px-2.5 py-1 rounded-lg bg-white/[0.04] border border-[color:var(--mp-hairline-md)] text-[#C8C2B5] text-[11px] font-medium">
                        {{ $request_->department }}
                    </span>
                    @endif
                    @if(!empty($request_->aiTriage))
                    <span class="px-2.5 py-1 rounded-lg bg-[rgba(201,162,74,0.08)] border border-[color:var(--mp-line)] text-[color:var(--mp-gold-bright)] text-[11px] font-medium inline-flex items-center gap-1">
                        <i class="bi bi-stars"></i> AI Triaged
                    </span>
                    @endif
                </div>

                {{-- Guest Self-Cancel Request (while pending or assigned) --}}
                @if(in_array($request_->status, ['pending', 'assigned'], true))
                <form method="POST" action="{{ route('guest.requests.cancel', ['token' => $token, 'ref' => $request_->reference_number]) }}"
                      onsubmit="return confirm('Cancel request {{ $request_->reference_number }}? This will close the ticket.');">
                    @csrf
                    <button type="submit" class="w-full py-2.5 rounded-xl border border-red-400/30 bg-red-500/10 text-red-300 text-xs font-semibold hover:bg-red-500/20 transition">
                        <i class="bi bi-x-circle mr-1"></i> Cancel This Request
                    </button>
                </form>
                @endif

                {{-- Guest Satisfaction Rating (when resolved/closed) --}}
                @if(in_array($request_->status, ['resolved', 'closed'], true))
                <div class="p-3.5 rounded-xl bg-[#080C14]/85 border border-[color:var(--mp-line)] space-y-2.5">
                    @if(!empty($request_->guest_rating))
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-medium text-[#F6F1E7]">Your Rating</span>
                            <span class="text-[color:var(--mp-gold-bright)] text-xs tracking-wider">
                                @for($i = 1; $i <= 5; $i++)
                                    <i class="bi {{ $i <= $request_->guest_rating ? 'bi-star-fill' : 'bi-star' }}"></i>
                                @endfor
                                <span class="ml-1 text-[#C8C2B5]">({{ $request_->guest_rating }}/5)</span>
                            </span>
                        </div>
                        @if(!empty($request_->guest_feedback))
                            <p class="text-xs text-[#98A0AE] italic m-0">"{{ $request_->guest_feedback }}"</p>
                        @endif
                    @else
                        <div class="mp-label !mb-1">Rate Our Service</div>
                        <form method="POST" action="{{ route('guest.rate', $token) }}" class="space-y-2.5">
                            @csrf
                            <input type="hidden" name="type" value="request">
                            <input type="hidden" name="ref" value="{{ $request_->reference_number }}">
                            <div class="flex items-center gap-1.5">
                                @for($i = 1; $i <= 5; $i++)
                                <label class="cursor-pointer">
                                    <input type="radio" name="rating" value="{{ $i }}" class="sr-only peer" {{ $i === 5 ? 'checked' : '' }} required>
                                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg border border-[color:var(--mp-hairline-md)] bg-white/[0.03] text-xs text-[#C8C2B5] peer-checked:bg-[rgba(201,162,74,0.2)] peer-checked:border-[color:var(--mp-gold)] peer-checked:text-[color:var(--mp-gold-bright)] transition">
                                        {{ $i }}★
                                    </span>
                                </label>
                                @endfor
                            </div>
                            <input type="text" name="feedback" maxlength="500" placeholder="Optional comment about our service..."
                                   class="w-full px-3 py-2 rounded-lg bg-[#0E1523] border border-[color:var(--mp-hairline-md)] text-xs text-[#F6F1E7] placeholder-[#6E7788] focus:outline-none focus:border-[color:var(--mp-gold)]">
                            <button type="submit" class="w-full py-2 rounded-lg text-xs font-semibold bg-[color:var(--mp-gold)] text-[#070B12] hover:opacity-95 transition">
                                Submit Rating
                            </button>
                        </form>
                    @endif
                </div>
                @endif

            </div>

            {{-- 2. FOOD ORDER TRACKED DISPLAY --}}
            @elseif($order)
            <div id="trackedOrderCard" class="mp-surface p-5 space-y-5">

                <div class="flex items-start justify-between gap-3 pb-3.5 border-b border-[color:var(--mp-hairline)]">
                    <div class="flex items-center gap-3 min-w-0">
                        <span class="mp-icon !w-10 !h-10"><i class="bi {{ method_exists($order, 'isDineIn') && $order->isDineIn() ? 'bi-shop' : 'bi-cup-hot' }}"></i></span>
                        <div class="min-w-0">
                            <div class="font-serif font-semibold text-base text-[#F6F1E7] leading-tight">
                                {{ method_exists($order, 'isDineIn') && $order->isDineIn() ? 'Times Square Dine-In Pre-Order' : 'In-Room Dining Order' }}
                            </div>
                            <div class="text-[11px] font-mono text-[color:var(--mp-gold)] tracking-wider mt-0.5 truncate">
                                {{ $order->reference_number }}
                            </div>
                        </div>
                    </div>
                    <span id="liveOrderBadge" data-status="{{ $order->status }}"
                          class="mp-status-pill shrink-0 {{ $order->status === 'delivered' ? 'is-done' : '' }}">
                        {{ ucfirst($order->status) }}
                    </span>
                </div>

                {{-- Dining Mode & Payment Method Pills --}}
                @php
                    $isDineInOrder = method_exists($order, 'isDineIn') && $order->isDineIn();
                    $isPayDirectOrder = method_exists($order, 'isPayOnArrival') && $order->isPayOnArrival();
                @endphp
                <div class="flex flex-wrap items-center gap-2 text-[11px]">
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-white/[0.04] border border-[color:var(--mp-hairline-md)] text-[#E7E2D7]">
                        <i class="bi {{ $isDineInOrder ? 'bi-cup-hot' : 'bi-door-open' }} text-[color:var(--mp-gold-bright)]"></i>
                        {{ method_exists($order, 'getDiningModeLabelAttribute') ? $order->dining_mode_label : 'Room Delivery' }}
                    </span>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg {{ $isPayDirectOrder ? 'bg-emerald-500/10 border border-emerald-500/30 text-emerald-300' : 'bg-[rgba(201,162,74,0.12)] border border-[color:var(--mp-line)] text-[color:var(--mp-gold-bright)]' }}">
                        <i class="bi {{ $isPayDirectOrder ? 'bi-cash-coin' : 'bi-receipt-cutoff' }}"></i>
                        {{ method_exists($order, 'getPaymentMethodLabelAttribute') ? $order->payment_method_label : 'Charge to Room (FO Check-Out)' }}
                    </span>
                </div>

                @if($isDineInOrder && $order->status === 'ready')
                <div class="px-3.5 py-2.5 rounded-xl bg-[rgba(201,162,74,0.12)] border border-[color:var(--mp-line)] text-xs text-[#F6F1E7] flex items-start gap-2">
                    <i class="bi bi-bell-fill text-[color:var(--mp-gold-bright)] mt-0.5 shrink-0"></i>
                    <span>Your meal is ready at <strong>Times Square Restaurant (Ground Floor)</strong>. Please head down anytime to be seated!</span>
                </div>
                @endif

                @php
                    $orderSteps = [
                        'pending'   => ['label' => 'Sent',      'icon' => 'bi-receipt'],
                        'confirmed' => ['label' => 'Confirmed', 'icon' => 'bi-patch-check'],
                        'preparing' => ['label' => 'Cooking',   'icon' => 'bi-fire'],
                        'ready'     => ['label' => 'Ready',     'icon' => 'bi-bell'],
                        'delivered' => ['label' => $isDineInOrder ? 'Served' : 'Delivered', 'icon' => 'bi-check2-all'],
                    ];
                    $ordStatusOrder = ['pending', 'confirmed', 'preparing', 'ready', 'delivered'];
                    $ordIdx = array_search($order->status, $ordStatusOrder);
                    if ($ordIdx === false) $ordIdx = ($order->status === 'cancelled') ? -1 : 0;
                    $ordPct = ($ordIdx >= 0) ? ($ordIdx / (count($ordStatusOrder) - 1)) * 100 : 0;
                @endphp

                <div class="py-1 px-0.5">
                    <div class="relative flex items-center justify-between">
                        <div class="absolute left-4 right-4 top-3.5 h-[2px] bg-white/10"></div>
                        <div id="liveOrderProgressBar"
                             class="absolute left-4 top-3.5 h-[2px] transition-all duration-500"
                             style="width: calc((100% - 2rem) * {{ $ordPct / 100 }}); background: var(--mp-gold);"></div>

                        @foreach($orderSteps as $key => $step)
                        @php
                            $stepIdx = array_search($key, $ordStatusOrder);
                            $nodeClass = $stepIdx < $ordIdx ? 'completed' : ($stepIdx === $ordIdx ? 'current' : 'upcoming');
                        @endphp
                        <div class="tracker-step relative z-10 flex flex-col items-center" data-order-step="{{ $key }}">
                            <div class="step-node {{ $nodeClass }} w-7 h-7 rounded-full border flex items-center justify-center text-[11px] transition-all duration-300">
                                <i class="bi {{ $stepIdx < $ordIdx ? 'bi-check-lg' : $step['icon'] }}"></i>
                            </div>
                            <span class="text-[11px] mt-1.5 {{ $stepIdx <= $ordIdx ? 'text-[#F6F1E7] font-medium' : 'text-[#6E7788]' }}">
                                {{ $step['label'] }}
                            </span>
                        </div>
                        @endforeach
                    </div>
                </div>

                {{-- Order Items Breakdown --}}
                <div class="p-3.5 rounded-xl bg-[#080C14]/85 border border-[color:var(--mp-hairline)] space-y-2.5">
                    <div class="mp-label !mb-0">Order Folio</div>
                    @if(!empty($order->orderItems))
                    <div class="space-y-2 text-xs">
                        @foreach($order->orderItems as $item)
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-[#E7E2D7]">
                                {{ $item->foodItem->name ?? 'Dish' }} <span class="text-[#8C94A4] text-[11px] ml-1 mp-num">&times; {{ $item->quantity }}</span>
                            </span>
                            <span class="text-[color:var(--mp-gold-bright)] font-medium mp-num">₱{{ number_format($item->subtotal, 2) }}</span>
                        </div>
                        @endforeach
                    </div>
                    @endif
                    <div class="pt-2.5 border-t border-[color:var(--mp-hairline)] flex items-center justify-between text-xs">
                        <span class="font-semibold text-[#F6F1E7]">{{ $isPayDirectOrder ? 'Total to Pay' : 'Total Charged to Room' }}</span>
                        <span class="font-serif font-semibold text-[color:var(--mp-gold-bright)] text-base mp-num">₱{{ number_format($order->total_amount, 2) }}</span>
                    </div>
                </div>

                {{-- Guest Self-Cancel (only while order is still pending) --}}
                @if($order->status === 'pending')
                <form method="POST" action="{{ route('guest.food.cancel', ['token' => $token, 'ref' => $order->reference_number]) }}"
                      onsubmit="return confirm('Cancel order {{ $order->reference_number }}? This cannot be undone.');">
                    @csrf
                    <button type="submit" class="w-full py-2.5 rounded-xl border border-red-400/30 bg-red-500/10 text-red-300 text-xs font-semibold hover:bg-red-500/20 transition">
                        <i class="bi bi-x-circle mr-1"></i> Cancel Pending Order
                    </button>
                </form>
                @endif

                {{-- Guest Manual Touch to Confirm Order Received (when Ready for Delivery / Dine-In) --}}
                @if($order->status === 'ready')
                <form method="POST" action="{{ route('guest.food.received', ['token' => $token, 'ref' => $order->reference_number]) }}">
                    @csrf
                    <button type="submit" class="w-full py-2.5 rounded-xl bg-[color:var(--mp-gold)] text-[#070B12] text-xs font-bold hover:opacity-95 transition">
                        <i class="bi bi-check2-circle mr-1"></i> {{ $isDineInOrder ? 'Confirm Seated & Served (Complete)' : 'Confirm Order Received (Complete)' }}
                    </button>
                </form>
                @endif

                {{-- Guest Dining Rating (when delivered / completed) --}}
                @if($order->status === 'delivered')
                <div class="px-3 py-2 rounded-xl bg-emerald-500/10 border border-emerald-500/30 flex items-center justify-between text-xs text-emerald-300">
                    <span class="font-semibold"><i class="bi bi-patch-check-fill mr-1"></i> Order Completed &amp; Received</span>
                    <span class="text-[10px] uppercase tracking-wider opacity-80">Locked</span>
                </div>
                <div class="p-3.5 rounded-xl bg-[#080C14]/85 border border-[color:var(--mp-line)] space-y-2.5">
                    @if(!empty($order->guest_rating))
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-medium text-[#F6F1E7]">Your Dining Rating</span>
                            <span class="text-[color:var(--mp-gold-bright)] text-xs tracking-wider">
                                @for($i = 1; $i <= 5; $i++)
                                    <i class="bi {{ $i <= $order->guest_rating ? 'bi-star-fill' : 'bi-star' }}"></i>
                                @endfor
                                <span class="ml-1 text-[#C8C2B5]">({{ $order->guest_rating }}/5)</span>
                            </span>
                        </div>
                        @if(!empty($order->guest_feedback))
                            <p class="text-xs text-[#98A0AE] italic m-0">"{{ $order->guest_feedback }}"</p>
                        @endif
                    @else
                        <div class="mp-label !mb-1">Rate Your Meal</div>
                        <form method="POST" action="{{ route('guest.rate', $token) }}" class="space-y-2.5">
                            @csrf
                            <input type="hidden" name="type" value="order">
                            <input type="hidden" name="ref" value="{{ $order->reference_number }}">
                            <div class="flex items-center gap-1.5">
                                @for($i = 1; $i <= 5; $i++)
                                <label class="cursor-pointer">
                                    <input type="radio" name="rating" value="{{ $i }}" class="sr-only peer" {{ $i === 5 ? 'checked' : '' }} required>
                                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg border border-[color:var(--mp-hairline-md)] bg-white/[0.03] text-xs text-[#C8C2B5] peer-checked:bg-[rgba(201,162,74,0.2)] peer-checked:border-[color:var(--mp-gold)] peer-checked:text-[color:var(--mp-gold-bright)] transition">
                                        {{ $i }}★
                                    </span>
                                </label>
                                @endfor
                            </div>
                            <input type="text" name="feedback" maxlength="500" placeholder="Optional feedback for our kitchen..."
                                   class="w-full px-3 py-2 rounded-lg bg-[#0E1523] border border-[color:var(--mp-hairline-md)] text-xs text-[#F6F1E7] placeholder-[#6E7788] focus:outline-none focus:border-[color:var(--mp-gold)]">
                            <button type="submit" class="w-full py-2 rounded-lg text-xs font-semibold bg-[color:var(--mp-gold)] text-[#070B12] hover:opacity-95 transition">
                                Submit Rating
                            </button>
                        </form>
                    @endif
                </div>
                @endif

            </div>
            @endif

        @else
            <div class="mp-surface p-5 text-center space-y-2.5">
                <span class="mp-icon mx-auto"><i class="bi bi-clock-history"></i></span>
                <div class="font-serif font-semibold text-sm text-[#F6F1E7]">No Reference Selected</div>
                <p class="text-xs text-[#8C94A4]">Enter a reference number above or pick an item from <button type="button" onclick="switchTrackTab('history')" class="text-[color:var(--mp-gold-bright)] underline">History</button>.</p>
            </div>
        @endif

        {{-- Quick Switcher Strip for Other Room Activity --}}
        @if($totalHistoryCount > 1)
        <div class="space-y-2">
            <div class="flex items-center justify-between px-0.5">
                <span class="mp-label !mb-0">Switch Active Ticket</span>
                <button type="button" onclick="switchTrackTab('history')" class="text-[11px] text-[color:var(--mp-gold-bright)] hover:underline">View all &rarr;</button>
            </div>
            <div class="flex gap-2 overflow-x-auto no-scrollbar pb-1">
                @foreach(($recentOrders ?? collect([]))->take(4) as $swOrd)
                <a href="{{ route('guest.track', ['token' => $token, 'ref' => $swOrd->reference_number]) }}"
                   class="mp-chip shrink-0 {{ $refNumber === $swOrd->reference_number ? 'is-active' : '' }}">
                    <i class="bi bi-cup-hot"></i>
                    <span class="font-mono text-[11px]">{{ $swOrd->reference_number }}</span>
                </a>
                @endforeach
                @foreach(($recentRequests ?? collect([]))->take(4) as $swReq)
                <a href="{{ route('guest.track', ['token' => $token, 'ref' => $swReq->reference_number]) }}"
                   class="mp-chip shrink-0 {{ $refNumber === $swReq->reference_number ? 'is-active' : '' }}">
                    <i class="bi {{ $swReq->type === 'maintenance' ? 'bi-wrench-adjustable' : 'bi-bell' }}"></i>
                    <span class="font-mono text-[11px]">{{ $swReq->reference_number }}</span>
                </a>
                @endforeach
            </div>
        </div>
        @endif
        </div>{{-- /trackPanel-status --}}

        {{-- TAB PANEL 2: FRONT DESK & MADI AI CHAT --}}
        <div id="trackPanel-chat" class="space-y-4 {{ $defaultTrackTab === 'chat' ? '' : 'hidden' }}">
        <div id="guestChatCard" class="mp-surface p-4 sm:p-5 space-y-3.5">
            <div class="flex items-center justify-between gap-2 pb-3 border-b border-[color:var(--mp-hairline)]">
                <div class="flex items-center gap-2.5 min-w-0">
                    <span class="mp-icon !w-9 !h-9 !text-sm"><i class="bi bi-chat-dots"></i></span>
                    <div class="min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <h3 class="font-serif font-semibold text-sm text-[#F6F1E7] leading-tight">Front Desk Chat</h3>
                            <span id="chatTicketRefBadge" class="font-mono text-[11px] px-2 py-0.5 rounded-md bg-white/[0.04] border border-[color:var(--mp-hairline-md)] text-[color:var(--mp-gold-bright)]">
                                {{ $chatTarget?->reference_number ?? 'Room ' . ($room->room_number ?? '201') }}
                            </span>
                        </div>
                        <p class="text-[11px] text-[#8C94A4] mt-0.5">Direct messaging with staff &amp; Madi AI</p>
                    </div>
                </div>
                <span id="guestChatStatusBadge" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] bg-emerald-500/10 border border-emerald-500/25 text-emerald-300 shrink-0">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                    Live
                </span>
            </div>

            {{-- Chat Messages Thread --}}
            <div id="guestChatThread" class="p-3.5 rounded-xl bg-[#080C14]/90 border border-[color:var(--mp-hairline)] max-h-64 min-h-[140px] overflow-y-auto space-y-3 no-scrollbar">
                @if(count($chatMessages) > 0)
                    @foreach($chatMessages as $msg)
                    @php
                        $isGuestMsg = $msg->sender_type === 'guest';
                        $isAiBotMsg = !$isGuestMsg && (str_contains($msg->sender_name ?? '', 'AI') || str_contains($msg->sender_name ?? '', 'Auto-Reply'));
                    @endphp
                    <div class="chat-msg-row flex flex-col {{ $isGuestMsg ? 'items-end' : 'items-start' }}" data-msg-id="{{ $msg->id }}">
                        <span class="text-[11px] {{ $isAiBotMsg ? 'text-[color:var(--mp-gold-bright)] font-medium' : 'text-[#7D8696]' }} mb-1 px-0.5">
                            {{ $isGuestMsg ? 'You' : ($msg->sender_name ?? 'Front Desk') }} &middot; {{ $msg->formatted_time ?? ($msg->created_at ? $msg->created_at->diffForHumans(null, true) : 'Now') }}
                        </span>
                        <div class="px-3.5 py-2.5 rounded-2xl max-w-[85%] text-xs leading-relaxed {{ $isGuestMsg ? 'bg-[rgba(201,162,74,0.16)] border border-[color:var(--mp-line-strong)] text-[#F6F1E7] rounded-br-sm' : ($isAiBotMsg ? 'bg-[#11192A] border border-[color:var(--mp-line)] text-[#E7E2D7] rounded-bl-sm' : 'bg-[#141D2E] border border-[color:var(--mp-hairline-md)] text-[#E7E2D7] rounded-bl-sm') }}">
                            {{ $msg->message }}
                        </div>
                    </div>
                    @endforeach
                @else
                    <div id="emptyChatPlaceholder" class="text-center py-6 space-y-1">
                        <div class="text-xs font-medium text-[#C8C2B5]">Room {{ $room->room_number ?? '201' }} Direct Line</div>
                        <div class="text-[11.5px] text-[#7D8696]">Send a message below to reach our front desk team anytime.</div>
                    </div>
                @endif
            </div>

            {{-- Quick Prompt Chips --}}
            <div id="guestQuickChips" class="flex gap-1.5 overflow-x-auto no-scrollbar py-0.5">
                <button type="button" onclick="insertGuestPrompt('Any update on this please?')" class="mp-chip shrink-0 !text-[11px] !py-1 !px-2.5">
                    <i class="bi bi-clock"></i> Any update?
                </button>
                <button type="button" onclick="insertGuestPrompt('I am currently inside the room.')" class="mp-chip shrink-0 !text-[11px] !py-1 !px-2.5">
                    <i class="bi bi-door-open"></i> Inside room
                </button>
                <button type="button" onclick="insertGuestPrompt('Could we please get extra towels and bottled water?')" class="mp-chip shrink-0 !text-[11px] !py-1 !px-2.5">
                    <i class="bi bi-droplet"></i> Towels &amp; Water
                </button>
                <button type="button" onclick="insertGuestPrompt('Thank you so much!')" class="mp-chip shrink-0 !text-[11px] !py-1 !px-2.5">
                    <i class="bi bi-heart"></i> Thank you
                </button>
            </div>

            {{-- Chat Message Input Form --}}
            <form id="guestChatForm" onsubmit="handleSendGuestMessage(event)" class="flex gap-2">
                <input type="text" id="guestMessageInput" placeholder="Write a message to front desk..."
                       class="mp-input flex-1 !py-2.5 !text-xs">
                <button type="submit" id="guestSendBtn" aria-label="Send message"
                        class="mp-btn !min-h-[40px] !px-4 !py-2 !text-xs shrink-0">
                    <i class="bi bi-send"></i>
                </button>
            </form>
        </div>
        </div>{{-- /trackPanel-chat --}}

        {{-- TAB PANEL 3: SUITE BILL & ACTIVITY HISTORY --}}
        <div id="trackPanel-history" class="space-y-4 {{ $defaultTrackTab === 'history' ? '' : 'hidden' }}">

        {{-- Suite Dining Bill / Folio Summary (Current Stay) --}}
        @if(!empty($stayFolio) && ($stayFolio['delivered_count'] > 0 || $stayFolio['active_count'] > 0))
        <section id="guestStayFolioCard" class="mp-surface p-4 space-y-2.5">
            <div class="flex items-center justify-between gap-2 pb-2 border-b border-[color:var(--mp-hairline)]">
                <div class="flex items-center gap-2">
                    <span class="mp-icon !w-8 !h-8 !text-xs"><i class="bi bi-receipt-cutoff"></i></span>
                    <div>
                        <h3 class="font-serif font-semibold text-sm text-[#F6F1E7] leading-tight">Suite Dining Bill (Current Stay)</h3>
                        <p class="text-[11px] text-[#8C94A4]">
                            {{ $stayFolio['delivered_count'] }} delivered {{ $stayFolio['delivered_count'] === 1 ? 'order' : 'orders' }}
                            @if($stayFolio['active_count'] > 0)
                                &middot; {{ $stayFolio['active_count'] }} in kitchen
                            @endif
                        </p>
                    </div>
                </div>
                <span class="font-serif font-semibold text-sm text-[color:var(--mp-gold-bright)] mp-num">
                    ₱{{ number_format($stayFolio['grand_total'], 2) }}
                </span>
            </div>
            <div class="space-y-1 text-xs">
                <div class="flex items-center justify-between text-[#98A0AE]">
                    <span>Charged to Room Subtotal</span>
                    <span class="mp-num text-[#E7E2D7]">₱{{ number_format($stayFolio['subtotal'], 2) }}</span>
                </div>
                <div class="flex items-center justify-between text-[#98A0AE]">
                    <span>Service Charge (10%)</span>
                    <span class="mp-num text-[#E7E2D7]">₱{{ number_format($stayFolio['service_charge'], 2) }}</span>
                </div>
                <div class="flex items-center justify-between pt-1.5 border-t border-[color:var(--mp-hairline)] font-semibold text-[#F6F1E7]">
                    <span>Balance Due at FO Check-Out</span>
                    <span class="mp-num text-[color:var(--mp-gold-bright)]">₱{{ number_format($stayFolio['grand_total'], 2) }}</span>
                </div>
                @if(($stayFolio['paid_direct_count'] ?? 0) > 0)
                <div class="flex items-center justify-between pt-1 text-[11px] text-emerald-300/90">
                    <span>Paid on Delivery / Cashier ({{ $stayFolio['paid_direct_count'] }})</span>
                    <span class="mp-num">₱{{ number_format($stayFolio['paid_direct_total'] ?? 0, 2) }}</span>
                </div>
                @endif
            </div>
        </section>
        @endif

        {{-- Recent Orders & Requests History --}}
        @if(($recentRequests && $recentRequests->count() > 0) || ($recentOrders && $recentOrders->count() > 0))
        <section class="space-y-2.5 pt-1">
            <div class="mp-section-head">
                <h2>Recent Suite Activity</h2>
                <span class="mp-meta">Tap to view</span>
            </div>

            <div class="space-y-2">
                @if($recentRequests)
                @foreach($recentRequests as $req)
                @php
                    $isRecentEvent = method_exists($req, 'isEventInquiry') && $req->isEventInquiry();
                @endphp
                <a href="{{ route('guest.track', ['token' => $token, 'ref' => $req->reference_number]) }}"
                   class="mp-card block p-3.5 text-xs"
                   data-recent-req="{{ $req->reference_number }}">
                    <div class="flex items-center justify-between gap-2">
                        <span class="font-medium text-[#F6F1E7] flex items-center gap-2 min-w-0">
                            <i class="bi {{ $isRecentEvent ? 'bi-calendar-event' : ($req->type === 'maintenance' ? 'bi-wrench-adjustable' : 'bi-bell') }} text-[color:var(--mp-gold)]"></i>
                            <span class="truncate">{{ $isRecentEvent ? 'Event Inquiry' : ucfirst($req->type) }}</span>
                            <span class="font-mono text-[11px] text-[#7D8696] shrink-0">#{{ $req->reference_number }}</span>
                        </span>
                        <span class="recent-status-badge mp-status-pill !py-0.5 !px-2 !text-[10px] shrink-0 {{ in_array($req->status, ['resolved', 'closed']) ? 'is-done' : '' }}">
                            {{ method_exists($req, 'getDisplayStatusAttribute') ? $req->display_status : str_replace('_', ' ', $req->status) }}
                        </span>
                    </div>
                    <p class="text-xs text-[#98A0AE] truncate mt-1.5">{{ $req->description }}</p>
                </a>
                @endforeach
                @endif

                @if($recentOrders)
                @foreach($recentOrders as $ord)
                @php
                    $ordDineIn = method_exists($ord, 'isDineIn') && $ord->isDineIn();
                    $ordPayDirect = method_exists($ord, 'isPayOnArrival') && $ord->isPayOnArrival();
                @endphp
                <a href="{{ route('guest.track', ['token' => $token, 'ref' => $ord->reference_number]) }}"
                   class="mp-card block p-3.5 text-xs"
                   data-recent-ord="{{ $ord->reference_number }}">
                    <div class="flex items-center justify-between gap-2">
                        <span class="font-medium text-[#F6F1E7] flex items-center gap-2 min-w-0">
                            <i class="bi {{ $ordDineIn ? 'bi-shop' : 'bi-cup-hot' }} text-[color:var(--mp-gold)]"></i>
                            <span class="truncate">{{ $ordDineIn ? 'Times Sq. Dine-In' : 'Room Dining' }}</span>
                            <span class="font-mono text-[11px] text-[#7D8696] shrink-0">#{{ $ord->reference_number }}</span>
                        </span>
                        <span class="recent-status-badge mp-status-pill !py-0.5 !px-2 !text-[10px] shrink-0 {{ $ord->status === 'delivered' ? 'is-done' : '' }}">
                            {{ ucfirst($ord->status) }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between gap-2 mt-1.5">
                        <span class="text-xs text-[color:var(--mp-gold-bright)] font-semibold mp-num">₱{{ number_format($ord->total_amount, 2) }}</span>
                        <span class="text-[10px] {{ $ordPayDirect ? 'text-emerald-300/90' : 'text-[#8C94A4]' }}">
                            {{ $ordPayDirect ? ($ordDineIn ? 'Pay at Cashier' : 'Pay on Delivery') : 'Charge to Room' }}
                        </span>
                    </div>
                </a>
                @endforeach
                @endif
            </div>
        </section>
        @else
        <div class="mp-surface p-5 text-center space-y-2">
            <i class="bi bi-inbox text-xl text-[#6E7788]"></i>
            <div class="text-xs text-[#98A0AE]">No orders or service requests logged yet for this stay.</div>
        </div>
        @endif
        </div>{{-- /trackPanel-history --}}

    </div>

    {{-- 2. Fixed Bottom Navigation Bar --}}
    @include('guest.partials.bottom-nav', ['active' => 'track'])

</div>

@push('scripts')
<script>
window.disableLayoutPolling = true;

function switchTrackTab(tabName) {
    ['status', 'chat', 'history'].forEach(t => {
        const panel = document.getElementById('trackPanel-' + t);
        const btn = document.getElementById('tabBtn-' + t);
        if (panel) panel.classList.toggle('hidden', t !== tabName);
        if (btn) btn.classList.toggle('is-active', t === tabName);
    });
    if (tabName === 'chat') {
        pollGuestTicketMessages();
        const thread = document.getElementById('guestChatThread');
        if (thread) requestAnimationFrame(() => { thread.scrollTop = thread.scrollHeight; });
    }
}

let lastUpdatedHash = null;
let isTrackPollInFlight = false;
let isSendingGuestMsg = false;
let activeTrackRef = "{{ $refNumber }}";
let activeChatRef = "{{ $chatRef }}";
const guestPollEndpoint = "{{ route('guest.poll', $token) }}";

const knownMessageIds = new Set([
    @if(count($chatMessages) > 0)
        @foreach($chatMessages as $m)
            "{{ $m->id }}",
        @endforeach
    @endif
]);

function hasKnownMsgId(id) {
    if (id === undefined || id === null) return false;
    return knownMessageIds.has(String(id)) || knownMessageIds.has(Number(id));
}

function addKnownMsgId(id) {
    if (id === undefined || id === null) return;
    knownMessageIds.add(String(id));
}

function syncIncomingChatMessages(messages) {
    if (!Array.isArray(messages)) return;
    const thread = document.getElementById('guestChatThread');
    if (thread && messages.length === 0 && knownMessageIds.size > 0 && !isSendingGuestMsg) {
        thread.innerHTML = `
            <div id="emptyChatPlaceholder" class="text-center py-6 text-xs text-[#7D8696]">
                Send a message to coordinate directly with hotel staff.
            </div>
        `;
        knownMessageIds.clear();
        return;
    }

    const orderedMessages = [...messages].sort((a, b) => Number(a.id || 0) - Number(b.id || 0));
    orderedMessages.forEach(msg => {
        if (!hasKnownMsgId(msg.id)) {
            addKnownMsgId(msg.id);
            appendMessageToGuestThread(msg);
            if (msg.sender_type === 'staff') {
                playChatChime();
            }
        }
    });
}

function pollGuestStatus() {
    if (document.hidden) return;
    if (isTrackPollInFlight || isSendingGuestMsg) return;

    isTrackPollInFlight = true;

    const pollRef = activeTrackRef || (activeChatRef !== 'NEW' ? activeChatRef : '');
    let url = `${guestPollEndpoint}?ref=${encodeURIComponent(pollRef)}`;
    if (lastUpdatedHash) {
        url += `&last_updated=${encodeURIComponent(lastUpdatedHash)}`;
    }

    fetch(url, {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(res => res.json())
    .then(data => {
        isTrackPollInFlight = false;
        if (!data || !data.success) return;
        if (data.changed === false) return;

        if (data.last_updated) {
            lastUpdatedHash = data.last_updated;
        }

        window.dispatchEvent(new CustomEvent('guestStatusUpdated', { detail: data }));

        if (data.tracked_item) {
            const item = data.tracked_item;

            if (item.type === 'request') {
                const badge = document.getElementById('liveRequestBadge');
                if (badge) {
                    badge.textContent = item.status_label;
                    badge.setAttribute('data-status', item.status);
                    badge.classList.toggle('is-done', item.status === 'resolved' || item.status === 'closed');
                }

                const bar = document.getElementById('liveRequestProgressBar');
                if (bar) {
                    bar.style.width = `calc((100% - 2.5rem) * ${(item.progress_pct || 0) / 100})`;
                }

                const statusSteps = ['pending', 'assigned', 'in_progress', 'resolved'];
                const reqIconMap = item.is_event
                    ? { pending: 'bi-inbox', assigned: 'bi-pin-angle', in_progress: 'bi-calendar2-check', resolved: 'bi-check-lg' }
                    : { pending: 'bi-inbox', assigned: 'bi-person-check', in_progress: 'bi-lightning-charge', resolved: 'bi-check-lg' };
                statusSteps.forEach((stepKey, idx) => {
                    const node = document.querySelector(`.tracker-step[data-step="${stepKey}"]`);
                    if (!node) return;
                    const iconEl = node.querySelector('.step-node');
                    iconEl.classList.remove('completed', 'current', 'upcoming');

                    if (idx < item.step_index) {
                        iconEl.classList.add('completed');
                        iconEl.innerHTML = '<i class="bi bi-check-lg"></i>';
                    } else if (idx === item.step_index) {
                        iconEl.classList.add('current');
                        iconEl.innerHTML = `<i class="bi ${reqIconMap[stepKey]}"></i>`;
                    } else {
                        iconEl.classList.add('upcoming');
                        iconEl.innerHTML = `<i class="bi ${reqIconMap[stepKey]}"></i>`;
                    }
                });

                if (Array.isArray(item.messages) && !isSendingGuestMsg) {
                    syncIncomingChatMessages(item.messages);
                }
            } else if (item.type === 'order') {
                const badge = document.getElementById('liveOrderBadge');
                if (badge) {
                    badge.textContent = item.status_label;
                    badge.setAttribute('data-status', item.status);
                    badge.classList.toggle('is-done', item.status === 'delivered');
                }

                const bar = document.getElementById('liveOrderProgressBar');
                if (bar) {
                    bar.style.width = `calc((100% - 2rem) * ${(item.progress_pct || 0) / 100})`;
                }

                const orderSteps = ['pending', 'confirmed', 'preparing', 'ready', 'delivered'];
                const ordIconMap = { pending: 'bi-receipt', confirmed: 'bi-patch-check', preparing: 'bi-fire', ready: 'bi-bell', delivered: 'bi-check2-all' };
                orderSteps.forEach((stepKey, idx) => {
                    const node = document.querySelector(`.tracker-step[data-order-step="${stepKey}"]`);
                    if (!node) return;
                    const iconEl = node.querySelector('.step-node');
                    iconEl.classList.remove('completed', 'current', 'upcoming');

                    if (idx < item.step_index) {
                        iconEl.classList.add('completed');
                        iconEl.innerHTML = '<i class="bi bi-check-lg"></i>';
                    } else if (idx === item.step_index) {
                        iconEl.classList.add('current');
                        iconEl.innerHTML = `<i class="bi ${ordIconMap[stepKey]}"></i>`;
                    } else {
                        iconEl.classList.add('upcoming');
                        iconEl.innerHTML = `<i class="bi ${ordIconMap[stepKey]}"></i>`;
                    }
                });
            }
        }

        if (data.recent_requests) {
            data.recent_requests.forEach(r => {
                const el = document.querySelector(`[data-recent-req="${r.reference_number}"] .recent-status-badge`);
                if (el) el.textContent = r.status_label;
            });
        }
        if (data.recent_orders) {
            data.recent_orders.forEach(o => {
                const el = document.querySelector(`[data-recent-ord="${o.reference_number}"] .recent-status-badge`);
                if (el) el.textContent = o.status_label;
            });
        }
    })
    .catch(() => {
        isTrackPollInFlight = false;
    });
}

let isGuestMsgPollInFlight = false;
function pollGuestTicketMessages() {
    if (document.hidden || isSendingGuestMsg || isGuestMsgPollInFlight) return;
    // Only poll dedicated chat endpoint while Desk Chat tab is active (status poll already handles background updates)
    const chatPanel = document.getElementById('trackPanel-chat');
    if (chatPanel && chatPanel.classList.contains('hidden')) return;

    const url = getGuestMessageStoreUrl();
    if (!url) return;

    isGuestMsgPollInFlight = true;
    fetch(url, {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(r => r.json())
    .then(data => {
        isGuestMsgPollInFlight = false;
        if (!data || !data.success || isSendingGuestMsg) return;
        if (data.reference_number && data.reference_number.startsWith('REQ-')) {
            activeChatRef = data.reference_number;
            const refBadge = document.getElementById('chatTicketRefBadge');
            if (refBadge) refBadge.textContent = data.reference_number;
        }
        if (Array.isArray(data.messages)) {
            syncIncomingChatMessages(data.messages);
        }
    })
    .catch(() => {
        isGuestMsgPollInFlight = false;
    });
}

setInterval(pollGuestStatus, 6500);
setInterval(pollGuestTicketMessages, 4000);
document.addEventListener('visibilitychange', () => {
    if (!document.hidden) {
        pollGuestStatus();
        pollGuestTicketMessages();
    }
});
document.addEventListener('DOMContentLoaded', scrollChatToBottom);

function getGuestMessageStoreUrl() {
    const ref = activeChatRef || activeTrackRef || 'NEW';
    return `{{ url('/guest/' . $token . '/requests') }}/${encodeURIComponent(ref)}/messages`;
}

function playChatChime() {
    try {
        const ctx = window.__getGuestLinkAudioCtx ? window.__getGuestLinkAudioCtx() : null;
        if (!ctx) return;
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.type = 'sine';
        osc.frequency.setValueAtTime(587.33, ctx.currentTime);
        osc.frequency.exponentialRampToValueAtTime(880, ctx.currentTime + 0.12);
        gain.gain.setValueAtTime(0.15, ctx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.3);
        osc.connect(gain);
        gain.connect(ctx.destination);
        osc.start();
        osc.stop(ctx.currentTime + 0.3);
    } catch(e) {}
}

function insertGuestPrompt(txt) {
    const input = document.getElementById('guestMessageInput');
    if (input) {
        input.value = txt;
        input.focus();
    }
}

function scrollChatToBottom() {
    const thread = document.getElementById('guestChatThread');
    if (thread) thread.scrollTop = thread.scrollHeight;
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text || '';
    return div.innerHTML;
}

function appendMessageToGuestThread(msg, isSelf = false) {
    const thread = document.getElementById('guestChatThread');
    if (!thread) return;

    if (msg.id && thread.querySelector(`[data-msg-id="${msg.id}"]`)) return;

    const placeholder = document.getElementById('emptyChatPlaceholder');
    if (placeholder) placeholder.remove();

    const row = document.createElement('div');
    const isAi = (msg.sender_name && (msg.sender_name.includes('AI') || msg.sender_name.includes('Auto-Reply')));

    if (msg.sender_type === 'guest' || isSelf) {
        row.className = 'chat-msg-row flex flex-col items-end';
        row.setAttribute('data-msg-id', msg.id || ('tmp_' + Date.now()));
        row.innerHTML = `
            <span class="text-[11px] text-[#7D8696] mb-1 px-0.5">You &middot; ${escapeHtml(msg.formatted_time || msg.time_ago || 'Now')}</span>
            <div class="px-3.5 py-2.5 rounded-2xl rounded-br-sm max-w-[85%] text-xs leading-relaxed bg-[rgba(201,162,74,0.16)] border border-[color:var(--mp-line-strong)] text-[#F6F1E7] break-words">
                ${escapeHtml(msg.message)}
            </div>
        `;
    } else {
        row.className = 'chat-msg-row flex flex-col items-start';
        row.setAttribute('data-msg-id', msg.id);
        row.innerHTML = `
            <span class="text-[11px] ${isAi ? 'text-[color:var(--mp-gold-bright)] font-medium' : 'text-[#7D8696]'} mb-1 px-0.5">${escapeHtml(msg.sender_name || 'Front Desk')} &middot; ${escapeHtml(msg.formatted_time || msg.time_ago || 'Now')}</span>
            <div class="px-3.5 py-2.5 rounded-2xl rounded-bl-sm max-w-[85%] text-xs leading-relaxed ${isAi ? 'bg-[#11192A] border border-[color:var(--mp-line)]' : 'bg-[#141D2E] border border-[color:var(--mp-hairline-md)]'} text-[#E7E2D7] break-words">
                ${escapeHtml(msg.message)}
            </div>
        `;
    }
    thread.appendChild(row);
    scrollChatToBottom();
}

function handleSendGuestMessage(e) {
    if (e) e.preventDefault();
    const input = document.getElementById('guestMessageInput');
    const btn = document.getElementById('guestSendBtn');
    if (!input || !input.value.trim()) return;

    const targetUrl = getGuestMessageStoreUrl();
    if (!targetUrl) return;

    const text = input.value.trim();
    input.value = '';
    btn.disabled = true;
    isSendingGuestMsg = true;

    const tempId = 'temp_' + Date.now();
    appendMessageToGuestThread({
        id: tempId,
        sender_type: 'guest',
        message: text,
        time_ago: 'Just now'
    }, true);

    fetch(targetUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ message: text })
    })
    .then(async res => {
        btn.disabled = false;
        isSendingGuestMsg = false;
        const data = await res.json().catch(() => null);
        if (res.ok && data && data.success && data.message) {
            if (data.reference_number && data.reference_number.startsWith('REQ-')) {
                activeChatRef = data.reference_number;
                if (!activeTrackRef) activeTrackRef = data.reference_number;
                const refBadge = document.getElementById('chatTicketRefBadge');
                if (refBadge) refBadge.textContent = data.reference_number;
            }
            addKnownMsgId(data.message.id);
            const tempEl = document.querySelector(`[data-msg-id="${tempId}"]`);
            if (tempEl) tempEl.setAttribute('data-msg-id', data.message.id);
            if (data.bot_reply) {
                addKnownMsgId(data.bot_reply.id);
                appendMessageToGuestThread(data.bot_reply);
                playChatChime();
            }
        } else {
            const tempEl = document.querySelector(`[data-msg-id="${tempId}"]`);
            if (tempEl) {
                const bubble = tempEl.querySelector('div');
                if (bubble) {
                    bubble.classList.add('border-rose-500', 'bg-rose-500/20');
                    bubble.innerHTML += `<div class="text-[11px] text-rose-400 font-medium mt-1">Not delivered (${escapeHtml(data?.error || data?.message || 'Error')})</div>`;
                }
            }
        }
    })
    .catch(() => {
        btn.disabled = false;
        isSendingGuestMsg = false;
        const tempEl = document.querySelector(`[data-msg-id="${tempId}"]`);
        if (tempEl) {
            const bubble = tempEl.querySelector('div');
            if (bubble) {
                bubble.classList.add('border-rose-500', 'bg-rose-500/20');
                bubble.innerHTML += `<div class="text-[11px] text-rose-300 font-medium mt-1">Network error</div>`;
            }
        }
    });
}
</script>
@endpush
@endsection
