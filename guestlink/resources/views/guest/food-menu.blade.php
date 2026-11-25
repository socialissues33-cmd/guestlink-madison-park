{{-- File: guestlink/resources/views/guest/food-menu.blade.php --}}
@extends('layouts.guest')
@section('title', 'In-Room Dining')

@push('styles')
<style>
    #cartBottomSheet {
        transition: transform 0.35s cubic-bezier(0.22, 1, 0.36, 1), opacity 0.25s ease;
    }
    #cartBottomSheet.translate-y-full {
        transform: translateY(105%);
        pointer-events: none;
    }
    #cartBottomSheet:not(.translate-y-full) {
        transform: translateY(0);
        pointer-events: auto;
    }
    .food-item-card.is-selected {
        border-color: var(--mp-line-strong);
        background: linear-gradient(180deg, rgba(201,162,74,0.09) 0%, rgba(14,21,35,0.96) 100%);
    }
</style>
@endpush

@section('content')
<div class="mp-shell pb-36 antialiased">

    {{-- 1. Shared Sticky Frosted Header --}}
    @include('guest.partials.header', ['subtitle' => 'In-Room Dining'])

    {{-- Sticky Horizontal Category Tabs --}}
    <div class="sticky top-[60px] z-30 bg-[#080C14]/92 backdrop-blur-xl border-b border-[color:var(--mp-hairline)] px-4 py-2.5">
        <div class="flex gap-2 overflow-x-auto no-scrollbar">
            <button type="button" class="cat-pill mp-chip is-active shrink-0"
                    onclick="filterCategory('all', this)">
                All Menu
            </button>
            @foreach($categories as $category)
            @if($category->foodItems->count() > 0)
            <button type="button" class="cat-pill mp-chip shrink-0"
                    onclick="filterCategory('cat-{{ $category->id }}', this)">
                {{ $category->name }}
                <span class="opacity-65 text-[11px] mp-num">{{ $category->foodItems->count() }}</span>
            </button>
            @endif
            @endforeach
        </div>
    </div>

    {{-- Main Food Menu Content --}}
    <div class="px-4 pt-4 space-y-6">

        {{-- Kitchen Paused Banner (controlled from Admin Overview) --}}
        @if(!\Illuminate\Support\Facades\Cache::get('guestlink_kitchen_ordering_open', true))
        <div class="mp-note is-warn text-xs">
            <i class="bi bi-clock-history text-amber-400 text-base shrink-0 mt-0.5"></i>
            <div>
                <div class="font-semibold mb-0.5">Kitchen Ordering Currently Paused</div>
                <p class="text-[11.5px] opacity-85 m-0">You may browse our Times Square menu, and ordering will reopen shortly. For urgent dining requests, message Front Desk.</p>
            </div>
        </div>
        @endif

        {{-- Error Notification Banner --}}
        @if(session('error') || $errors->any())
        <div class="mp-note is-danger text-xs text-rose-200">
            <i class="bi bi-exclamation-circle text-rose-400 text-base shrink-0 mt-0.5"></i>
            <div>
                <div class="font-semibold mb-1">{{ session('error') ?? 'Please check the following:' }}</div>
                @if($errors->any())
                <ul class="list-disc list-inside space-y-0.5 text-[11.5px] text-rose-300/90">
                    @foreach($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
                @endif
            </div>
        </div>
        @endif

        {{-- Editorial Restaurant Hero Card --}}
        <section class="mp-page-hero min-h-[156px] p-5 flex flex-col justify-between"
                 style="background-image: linear-gradient(180deg, rgba(8,12,20,.25) 0%, rgba(8,12,20,.82) 62%, #080C14 100%), url('{{ asset('images/hotel/madison-times-square-restaurant.jpg') }}'); background-position: center; background-size: cover;">
            <span class="mp-photo-badge inline-flex items-center gap-1.5 self-start px-2.5 py-1 rounded-full bg-[#080C14]/75 backdrop-blur-md border border-[color:var(--mp-line)] text-[11px] font-medium text-[#F4DC8B]">
                <i class="bi bi-clock"></i> 6:00 AM – 11:00 PM
            </span>
            <div class="pt-6">
                <p class="mp-eyebrow">Times Square Restaurant</p>
                <h1 class="font-serif text-[1.35rem] font-semibold text-[#F6F1E7] leading-tight mt-0.5">
                    In-Room Dining Menu
                </h1>
                <p class="text-xs text-[#C8C2B5] mt-1">
                    Freshly prepared &amp; delivered to Room <strong class="text-[color:var(--mp-gold-bright)]">{{ $room->room_number }}</strong>.
                </p>
            </div>
        </section>

        {{-- Categories & Editorial Dish Rows --}}
        @foreach($categories as $category)
        @if($category->foodItems->count() > 0)
        <section class="category-block space-y-2.5" id="cat-{{ $category->id }}">
            <div class="mp-section-head pb-1.5 border-b border-[color:var(--mp-hairline)]">
                <h2>{{ $category->name }}</h2>
                <span class="mp-meta mp-num">{{ $category->foodItems->count() }} {{ $category->foodItems->count() === 1 ? 'dish' : 'dishes' }}</span>
            </div>

            <div class="space-y-2.5">
                @foreach($category->foodItems as $item)
                <div class="food-item-card mp-card p-3.5 flex items-center justify-between gap-3.5"
                     id="card-{{ $item->id }}"
                     data-id="{{ $item->id }}" data-name="{{ $item->name }}" data-price="{{ $item->price }}">

                    @if($item->image)
                    <img src="{{ asset($item->image) }}" alt="{{ $item->name }}"
                         class="w-14 h-14 rounded-xl object-cover border border-[color:var(--mp-hairline-md)] shrink-0">
                    @endif

                    <div class="flex-1 min-w-0">
                        <div class="font-serif font-semibold text-[0.95rem] text-[#F6F1E7] leading-snug">{{ $item->name }}</div>
                        @if($item->description)
                        <div class="text-xs text-[#98A0AE] line-clamp-2 mt-0.5 leading-relaxed">{{ $item->description }}</div>
                        @endif
                        <div class="text-[13px] font-semibold text-[color:var(--mp-gold-bright)] mt-2 mp-num">
                            ₱{{ number_format($item->price, 2) }}
                        </div>
                    </div>

                    {{-- Tactile Minimalist Stepper --}}
                    <div class="flex items-center gap-1 shrink-0 bg-[#080C14]/90 border border-[color:var(--mp-hairline-md)] rounded-full p-1">
                        <button type="button" aria-label="Decrease quantity of {{ $item->name }}"
                                class="qty-btn minus w-8 h-8 rounded-full bg-white/[0.04] hover:bg-white/10 text-[#C8C2B5] hover:text-white flex items-center justify-center text-sm transition-colors cursor-pointer active:scale-90"
                                data-id="{{ $item->id }}">
                            <i class="bi bi-dash"></i>
                        </button>
                        <span class="qty-display w-6 text-center text-xs font-semibold text-[#F6F1E7] mp-num" id="qty-{{ $item->id }}">0</span>
                        <button type="button" aria-label="Increase quantity of {{ $item->name }}"
                                class="qty-btn plus w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold transition-transform cursor-pointer active:scale-90"
                                style="background:var(--mp-metal);color:#140F04;"
                                data-id="{{ $item->id }}">
                            <i class="bi bi-plus"></i>
                        </button>
                    </div>
                </div>
                @endforeach
            </div>
        </section>
        @endif
        @endforeach

    </div>

    {{-- Floating Cart Summary Bar (Cleanly docked above 5-tab nav & raised AI center orb) --}}
    <div id="floatingCartBar"
         class="hidden fixed bottom-[104px] inset-x-0 z-30 max-w-md mx-auto px-4 cursor-pointer"
         onclick="openCartDrawer()">
        <div class="rounded-2xl p-3 bg-[#101828]/96 border border-[color:var(--mp-line-strong)] shadow-[0_18px_40px_rgba(0,0,0,0.8)] flex items-center justify-between gap-3 backdrop-blur-xl">
            <div class="flex items-center gap-3 min-w-0">
                <div class="w-10 h-10 rounded-xl bg-[rgba(201,162,74,0.14)] border border-[color:var(--mp-line-strong)] flex items-center justify-center text-[color:var(--mp-gold-bright)] font-bold text-xs mp-num shrink-0">
                    <span id="cartCount">0</span>
                </div>
                <div class="min-w-0">
                    <div class="text-sm font-serif font-semibold text-[#F6F1E7] leading-tight mp-num">
                        ₱<span id="cartTotal">0.00</span>
                    </div>
                    <div class="text-[11px] text-[#A8A397] mt-0.5 truncate">Review room delivery ticket</div>
                </div>
            </div>
            <button type="button" class="mp-btn !min-h-[38px] !px-4 !py-1.5 !text-xs shrink-0">
                <span>View Cart</span>
                <i class="bi bi-arrow-right"></i>
            </button>
        </div>
    </div>

    {{-- Slide-up Bottom Sheet Cart Drawer --}}
    <div id="cartDrawerBackdrop"
         class="hidden fixed inset-0 z-50 bg-black/75 backdrop-blur-sm transition-opacity"
         onclick="closeCartDrawer()"></div>

    <div id="cartBottomSheet"
         class="fixed bottom-0 inset-x-0 z-50 max-w-md mx-auto rounded-t-[24px] bg-[#0B111D] border-t border-[color:var(--mp-line-strong)] shadow-[0_-24px_60px_rgba(0,0,0,0.9)] translate-y-full max-h-[88dvh] flex flex-col overflow-hidden">

        {{-- Fixed Sheet Header --}}
        <div class="px-5 pt-3 pb-3 border-b border-[color:var(--mp-hairline)] shrink-0">
            <div class="w-10 h-1 rounded-full bg-white/15 mx-auto mb-3"></div>
            <div class="flex items-center justify-between gap-2">
                <div class="flex items-center gap-3 min-w-0">
                    <span class="mp-icon !w-9 !h-9 shrink-0"><i id="cartHeaderIcon" class="bi bi-receipt"></i></span>
                    <div class="min-w-0">
                        <h2 id="cartHeaderTitle" class="font-serif text-base font-semibold text-[#F6F1E7] leading-tight">Room Delivery Ticket</h2>
                        <p id="cartHeaderSubtitle" class="text-[11.5px] text-[#98A0AE] mt-0.5 truncate">Room <strong class="text-[color:var(--mp-gold-bright)]">{{ $room->room_number }}</strong> &middot; Pay on delivery (Cash / GCash)</p>
                    </div>
                </div>
                <button type="button" onclick="closeCartDrawer()" aria-label="Close cart"
                        class="w-8 h-8 rounded-full bg-white/[0.05] border border-[color:var(--mp-hairline-md)] text-[#A8A397] hover:text-white flex items-center justify-center cursor-pointer shrink-0">
                    <i class="bi bi-x-lg text-xs"></i>
                </button>
            </div>
        </div>

        {{-- Food Order Form (Scrollable Middle + Sticky Confirm Footer) --}}
        <form method="POST" action="{{ route('guest.food.order', $token) }}" id="foodOrderForm" class="flex-1 min-h-0 flex flex-col">
            @csrf
            <div id="orderItemsInput"></div>

            <div class="flex-1 overflow-y-auto no-scrollbar px-5 py-3.5 space-y-3.5">
                {{-- Order Items Summary List --}}
                <div id="orderSummary" class="max-h-40 overflow-y-auto no-scrollbar divide-y divide-[color:var(--mp-hairline)]"></div>

            {{-- 1. Dining Mode Selector --}}
            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <label class="mp-label !mb-0">How Would You Like to Dine?</label>
                    <span class="text-[11px] text-[#7D8696]">Choose 1</span>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <label class="cursor-pointer">
                        <input type="radio" name="dining_mode" value="room_delivery" class="sr-only peer" checked onchange="syncOrderPreferences()">
                        <div class="h-full p-2.5 rounded-xl border border-[color:var(--mp-hairline-md)] bg-white/[0.025] peer-checked:border-[color:var(--mp-gold)] peer-checked:bg-[rgba(201,162,74,0.12)] transition">
                            <div class="flex items-center gap-1.5 text-xs font-semibold text-[#F6F1E7]">
                                <i class="bi bi-door-open text-[color:var(--mp-gold-bright)]"></i>
                                <span>Deliver to Room</span>
                            </div>
                            <p class="text-[11px] text-[#8C94A4] mt-0.5 leading-snug">Bring tray to Room {{ $room->room_number }}</p>
                        </div>
                    </label>
                    <label class="cursor-pointer">
                        <input type="radio" name="dining_mode" value="dine_in" class="sr-only peer" onchange="syncOrderPreferences()">
                        <div class="h-full p-2.5 rounded-xl border border-[color:var(--mp-hairline-md)] bg-white/[0.025] peer-checked:border-[color:var(--mp-gold)] peer-checked:bg-[rgba(201,162,74,0.12)] transition">
                            <div class="flex items-center gap-1.5 text-xs font-semibold text-[#F6F1E7]">
                                <i class="bi bi-cup-hot text-[color:var(--mp-gold-bright)]"></i>
                                <span>Dine-In · Times Sq.</span>
                            </div>
                            <p class="text-[11px] text-[#8C94A4] mt-0.5 leading-snug">Pre-order &amp; eat at Ground Floor</p>
                        </div>
                    </label>
                </div>
            </div>

            {{-- 2. Payment Method Selector (Mid-scale hotel default: Pay on Delivery / Cashier) --}}
            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <label class="mp-label !mb-0">Payment Method</label>
                    <span class="text-[11px] text-[#7D8696]">Cash / GCash / Room Bill</span>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <label class="cursor-pointer">
                        <input type="radio" name="payment_method" value="pay_on_arrival" class="sr-only peer" checked onchange="syncOrderPreferences()">
                        <div class="h-full p-2.5 rounded-xl border border-[color:var(--mp-hairline-md)] bg-white/[0.025] peer-checked:border-[color:var(--mp-gold)] peer-checked:bg-[rgba(201,162,74,0.12)] transition">
                            <div class="flex items-center gap-1.5 text-xs font-semibold text-[#F6F1E7]">
                                <i class="bi bi-cash-coin text-[color:var(--mp-gold-bright)]"></i>
                                <span id="payDirectTitle">Pay on Delivery</span>
                            </div>
                            <p id="payDirectSub" class="text-[11px] text-[#8C94A4] mt-0.5 leading-snug">Cash / GCash to server upon arrival</p>
                        </div>
                    </label>
                    <label class="cursor-pointer">
                        <input type="radio" name="payment_method" value="charge_to_room" class="sr-only peer" onchange="syncOrderPreferences()">
                        <div class="h-full p-2.5 rounded-xl border border-[color:var(--mp-hairline-md)] bg-white/[0.025] peer-checked:border-[color:var(--mp-gold)] peer-checked:bg-[rgba(201,162,74,0.12)] transition">
                            <div class="flex items-center gap-1.5 text-xs font-semibold text-[#F6F1E7]">
                                <i class="bi bi-receipt-cutoff text-[color:var(--mp-gold-bright)]"></i>
                                <span>Charge to Room</span>
                            </div>
                            <p class="text-[11px] text-[#8C94A4] mt-0.5 leading-snug">Settle at FO check-out (+10% SC)</p>
                        </div>
                    </label>
                </div>
            </div>

            <div>
                <label class="mp-label">Guest Name (Optional)</label>
                <input type="text" name="guest_name" placeholder="e.g. Pedro Santos" class="mp-input !py-2.5 !text-xs">
            </div>

            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <label class="mp-label !mb-0">Kitchen Notes</label>
                    <span class="text-[11px] text-[#7D8696]">Tap to add</span>
                </div>
                <div class="flex flex-wrap gap-1.5 mb-2.5">
                    <button type="button" class="instruction-chip mp-chip !text-[11px] !py-1 !px-2.5" data-text="Extra spicy please.">
                        <i class="bi bi-fire"></i> Extra Spicy
                    </button>
                    <button type="button" class="instruction-chip mp-chip !text-[11px] !py-1 !px-2.5" data-text="No onions or garlic please.">
                        <i class="bi bi-slash-circle"></i> No Onions
                    </button>
                    <button type="button" class="instruction-chip mp-chip !text-[11px] !py-1 !px-2.5" data-text="Include extra steamed rice.">
                        <i class="bi bi-plus-circle"></i> Extra Rice
                    </button>
                    <button type="button" class="instruction-chip mp-chip !text-[11px] !py-1 !px-2.5" data-text="Provide extra sauce and gravy.">
                        <i class="bi bi-droplet"></i> Extra Gravy
                    </button>
                    <button type="button" class="instruction-chip mp-chip !text-[11px] !py-1 !px-2.5" data-text="Please bring extra cutlery & napkins.">
                        <i class="bi bi-cup-straw"></i> Extra Cutlery
                    </button>
                    <button type="button" class="instruction-chip mp-chip !text-[11px] !py-1 !px-2.5" data-text="Deliver as soon as prepared.">
                        <i class="bi bi-lightning-charge"></i> Priority
                    </button>
                </div>
                <textarea name="notes" id="orderNotes" rows="2"
                          placeholder="Dietary preferences, allergies, or cutlery requests..."
                          class="mp-input !py-2.5 !text-xs resize-none"></textarea>
            </div>
            </div>

            {{-- Sticky Bottom Total & Confirm Action (Always Visible on Mobile & PC) --}}
            <div class="px-5 pt-3 pb-[max(1rem,env(safe-area-inset-bottom))] border-t border-[color:var(--mp-hairline)] space-y-2.5 shrink-0">
                <div class="flex items-center justify-between px-3.5 py-2.5 rounded-xl bg-[rgba(201,162,74,0.08)] border border-[color:var(--mp-line)]">
                    <div class="min-w-0 pr-2">
                        <span id="cartTotalLabel" class="block text-[10.5px] uppercase tracking-wider text-[#A8A397] font-semibold">Amount to Pay</span>
                        <span id="cartTotalSub" class="text-[11px] text-[#8C94A4] block truncate">Pay Cash / GCash upon room delivery</span>
                    </div>
                    <span class="text-lg font-serif font-semibold text-[color:var(--mp-gold-bright)] mp-num shrink-0">
                        ₱<span id="finalTotal">0.00</span>
                    </span>
                </div>

                <button type="submit" id="placeOrderBtn" class="mp-btn w-full">
                    <i class="bi bi-bag-check"></i>
                    <span id="placeOrderBtnText">Confirm &amp; Send to Kitchen</span>
                </button>
            </div>
        </form>
    </div>

    {{-- 2. Fixed Bottom Navigation Bar --}}
    @include('guest.partials.bottom-nav', ['active' => 'food'])

</div>

@push('scripts')
<script>
const cart = {};
const prices = {};
const names = {};

document.querySelectorAll('.food-item-card').forEach(card => {
    const id = card.dataset.id;
    prices[id] = parseFloat(card.dataset.price);
    names[id]  = card.dataset.name;
    cart[id]   = 0;
});

function updateCart() {
    let count = 0, total = 0;
    let summaryHtml = '';
    let inputsHtml  = '';

    for (const [id, qty] of Object.entries(cart)) {
        const card = document.getElementById('card-' + id);
        if (qty > 0) {
            count += qty;
            const sub = qty * prices[id];
            total += sub;
            summaryHtml += `
                <div class="flex items-center justify-between py-2.5 text-xs">
                    <div class="min-w-0 pr-3">
                        <span class="text-[#F6F1E7] font-medium">${escapeHtml(names[id])}</span>
                        <span class="text-[#8C94A4] text-[11px] ml-1.5 mp-num">&times; ${qty}</span>
                    </div>
                    <span class="text-[color:var(--mp-gold-bright)] font-semibold mp-num shrink-0">₱${sub.toFixed(2)}</span>
                </div>`;
            inputsHtml += `<input type="hidden" name="items[${id}][id]" value="${id}">
                           <input type="hidden" name="items[${id}][quantity]" value="${qty}">`;
            if (card) card.classList.add('is-selected');
        } else {
            if (card) card.classList.remove('is-selected');
        }
    }

    document.getElementById('cartCount').textContent = count;
    document.getElementById('cartTotal').textContent  = total.toFixed(2);
    document.getElementById('finalTotal').textContent = total.toFixed(2);

    const floatingBar = document.getElementById('floatingCartBar');
    if (floatingBar) {
        if (count > 0) {
            floatingBar.classList.remove('hidden');
        } else {
            floatingBar.classList.add('hidden');
            closeCartDrawer();
        }
    }

    document.getElementById('orderSummary').innerHTML   = summaryHtml || '<div class="text-xs text-[#7D8696] py-4 text-center">Your delivery ticket is empty.</div>';
    document.getElementById('orderItemsInput').innerHTML = inputsHtml;
}

document.querySelectorAll('.qty-btn.plus').forEach(btn => {
    btn.addEventListener('click', () => {
        const id = btn.dataset.id;
        if (cart[id] < 20) cart[id]++;
        document.getElementById('qty-' + id).textContent = cart[id];
        updateCart();
    });
});

document.querySelectorAll('.qty-btn.minus').forEach(btn => {
    btn.addEventListener('click', () => {
        const id = btn.dataset.id;
        if (cart[id] > 0) cart[id]--;
        document.getElementById('qty-' + id).textContent = cart[id];
        updateCart();
    });
});

function openCartDrawer() {
    const backdrop = document.getElementById('cartDrawerBackdrop');
    const sheet    = document.getElementById('cartBottomSheet');
    backdrop.classList.remove('hidden');
    sheet.classList.remove('translate-y-full');
}

function closeCartDrawer() {
    const backdrop = document.getElementById('cartDrawerBackdrop');
    const sheet    = document.getElementById('cartBottomSheet');
    sheet.classList.add('translate-y-full');
    setTimeout(() => {
        backdrop.classList.add('hidden');
    }, 250);
}

function filterCategory(catId, btn) {
    document.querySelectorAll('.cat-pill').forEach(p => {
        p.classList.remove('is-active');
    });
    btn.classList.add('is-active');

    if (catId === 'all') {
        document.querySelectorAll('.category-block').forEach(b => b.classList.remove('hidden'));
    } else {
        document.querySelectorAll('.category-block').forEach(b => {
            b.classList.toggle('hidden', b.id !== catId);
        });
    }
}

document.querySelectorAll('.instruction-chip').forEach(chip => {
    chip.addEventListener('click', function() {
        const text = this.getAttribute('data-text');
        const notes = document.getElementById('orderNotes');
        if (!notes.value.trim()) {
            notes.value = text;
        } else if (!notes.value.includes(text)) {
            notes.value = notes.value.trim() + ' ' + text;
        }
        this.classList.toggle('is-active');
    });
});

function syncOrderPreferences() {
    const modeInput = document.querySelector('input[name="dining_mode"]:checked');
    const payInput  = document.querySelector('input[name="payment_method"]:checked');
    const mode = modeInput ? modeInput.value : 'room_delivery';
    const pay  = payInput ? payInput.value : 'pay_on_arrival';
    const roomNum = @json($room->room_number);

    const headerTitle = document.getElementById('cartHeaderTitle');
    const headerSub   = document.getElementById('cartHeaderSubtitle');
    const headerIcon  = document.getElementById('cartHeaderIcon');
    const payDirectTitle = document.getElementById('payDirectTitle');
    const payDirectSub   = document.getElementById('payDirectSub');
    const totalLabel  = document.getElementById('cartTotalLabel');
    const totalSub    = document.getElementById('cartTotalSub');
    const submitText  = document.getElementById('placeOrderBtnText');

    const isDineIn = mode === 'dine_in';
    const isDirect = pay === 'pay_on_arrival';

    if (payDirectTitle) payDirectTitle.textContent = isDineIn ? 'Pay at Cashier' : 'Pay on Delivery';
    if (payDirectSub) {
        payDirectSub.textContent = isDineIn
            ? 'Cash / GCash at Times Square Cashier'
            : 'Cash / GCash to server upon arrival';
    }

    if (headerIcon) {
        headerIcon.className = isDineIn ? 'bi bi-cup-hot' : 'bi bi-receipt';
    }
    if (headerTitle) {
        headerTitle.textContent = isDineIn ? 'Times Square Pre-Order' : 'Room Delivery Ticket';
    }
    if (headerSub) {
        const payTag = isDirect
            ? (isDineIn ? 'Pay at Times Square Cashier' : 'Pay on delivery (Cash / GCash)')
            : 'Charged to Room Folio';
        headerSub.innerHTML = isDineIn
            ? `Ground Floor Restaurant &middot; <strong class="text-[color:var(--mp-gold-bright)]">Room ${escapeHtml(roomNum)}</strong> &middot; ${payTag}`
            : `Room <strong class="text-[color:var(--mp-gold-bright)]">${escapeHtml(roomNum)}</strong> &middot; ${payTag}`;
    }

    if (totalLabel) {
        totalLabel.textContent = isDirect ? 'Amount to Pay' : 'Charge to Room Folio';
    }
    if (totalSub) {
        if (isDirect) {
            totalSub.textContent = isDineIn
                ? 'Pay Cash / GCash / Card at Times Square Cashier'
                : `Pay Cash / GCash upon arrival at Room ${roomNum}`;
        } else {
            totalSub.textContent = `Room ${roomNum} Account Folio (+10% SC at FO Check-Out)`;
        }
    }
    if (submitText) {
        submitText.textContent = isDineIn
            ? 'Pre-Order for Times Square Dine-In'
            : 'Confirm & Send to Kitchen';
    }
}

syncOrderPreferences();

document.getElementById('foodOrderForm').addEventListener('submit', function(e) {
    let hasItems = false;
    for (const qty of Object.values(cart)) {
        if (qty > 0) { hasItems = true; break; }
    }
    if (!hasItems) {
        e.preventDefault();
        alert('Your delivery ticket is empty. Please add at least one dish.');
        return;
    }
    const btn = document.getElementById('placeOrderBtn');
    btn.innerHTML = '<span class="inline-block w-4 h-4 border-2 border-[#140F04] border-t-transparent rounded-full animate-spin mr-1.5"></span>Sending to Kitchen...';
    btn.disabled = true;
});
</script>
@endpush
@endsection
