{{-- File: guestlink/resources/views/guest/service-request.blade.php --}}
@extends('layouts.guest')
@section('title', 'Guest Service Request')

@section('content')
<div class="mp-shell pb-28 antialiased">

    {{-- 1. Shared Sticky Frosted Header --}}
    @include('guest.partials.header', ['subtitle' => 'Guest Services'])

    <div class="px-4 pt-4 space-y-5">

        {{-- Editorial Page Hero --}}
        <section class="mp-page-hero p-5 min-h-[126px] flex flex-col justify-end"
                 style="background-image: linear-gradient(135deg, rgba(8,12,20,.92) 0%, rgba(8,12,20,.72) 100%), url('{{ asset('images/hotel/madison-facade-porte-cochere.jpg') }}'); background-position: center; background-size: cover;">
            <p class="mp-eyebrow">Room {{ $room->room_number }} &middot; Housekeeping &amp; Concierge</p>
            <h1 class="font-serif text-[1.35rem] font-semibold text-[#F6F1E7] leading-tight mt-1">
                Guest Service Request
            </h1>
            <p class="text-xs text-[#C8C2B5] mt-1">
                Automatically routed to Front Desk or Housekeeping with live tracking.
            </p>
        </section>

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

        {{-- Unified Hand-Crafted Form Card --}}
        <div class="mp-surface p-5">
            <form method="POST" action="{{ route('guest.service.store', $token) }}" id="serviceForm" class="space-y-5">
                @csrf

                {{-- Guest Name --}}
                <div>
                    <label for="guestNameInput" class="mp-label">Your Name (Optional)</label>
                    <input type="text" id="guestNameInput" name="guest_name" value="{{ old('guest_name') }}"
                           placeholder="e.g. Juan dela Cruz" class="mp-input">
                </div>

                {{-- Category Select --}}
                <div>
                    <label for="categorySelect" class="mp-label">Request Category</label>
                    <select name="category_id" id="categorySelect" class="mp-input appearance-none">
                        <option value="">— Select Category —</option>
                        @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" data-name="{{ strtolower($cat->name) }}" {{ old('category_id') == $cat->id ? 'selected' : '' }}>
                            {{ $cat->name }}
                        </option>
                        @endforeach
                    </select>
                </div>

                {{-- Quick Tap Suggestions --}}
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label class="mp-label !mb-0">Quick Requests</label>
                        <span class="text-[11px] text-[#7D8696]">Tap to prefill</span>
                    </div>
                    <div class="flex flex-wrap gap-1.5">
                        <button type="button" class="quick-chip mp-chip is-gold"
                                data-category="event" data-text="Does the hotel have a function hall? Is there an available function hall and how can we reserve it?">
                            <i class="bi bi-bank"></i> Function Hall
                        </button>
                        <button type="button" class="quick-chip mp-chip is-gold"
                                data-category="dining" data-text="How much is the price of food and what food is available? What are your chef recommendations for our group?">
                            <i class="bi bi-cup-hot"></i> Dining Inquiry
                        </button>
                        <button type="button" class="quick-chip mp-chip is-gold"
                                data-category="event" data-text="I would like to reserve the Main Function Hall (FH-01) with banquet catering package. Please contact us for schedule booking.">
                            <i class="bi bi-calendar3"></i> Reserve Hall
                        </button>
                        <button type="button" class="quick-chip mp-chip"
                                data-category="towel" data-text="Please send 2 extra clean bath towels to the room.">
                            <i class="bi bi-layers"></i> 2 Extra Towels
                        </button>
                        <button type="button" class="quick-chip mp-chip"
                                data-category="clean" data-text="Room makeup requested. Please clean the room and change bedsheets.">
                            <i class="bi bi-brush"></i> Room Makeup
                        </button>
                        <button type="button" class="quick-chip mp-chip"
                                data-category="amenities" data-text="Please provide 2 additional complimentary bottles of drinking water.">
                            <i class="bi bi-droplet"></i> Bottled Water
                        </button>
                        <button type="button" class="quick-chip mp-chip"
                                data-category="amenities" data-text="Please provide extra toiletries set (shampoo, conditioner, soap, and dental kit).">
                            <i class="bi bi-droplet-half"></i> Toiletries Kit
                        </button>
                        <button type="button" class="quick-chip mp-chip"
                                data-category="pillow" data-text="Please send 2 extra pillows and a warm blanket.">
                            <i class="bi bi-moon-stars"></i> Pillows &amp; Blanket
                        </button>
                        <button type="button" class="quick-chip mp-chip"
                                data-category="clean" data-text="Trash bin in room is full. Kindly send staff for trash collection.">
                            <i class="bi bi-trash"></i> Trash Collection
                        </button>
                        <button type="button" class="quick-chip mp-chip"
                                data-category="amenities" data-text="Please refill complimentary coffee pods, tea bags, creamer, and sugar.">
                            <i class="bi bi-cup"></i> Coffee &amp; Tea
                        </button>
                    </div>
                </div>

                {{-- Description Textarea --}}
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label for="descTextarea" class="mp-label !mb-0">
                            Details <span class="text-rose-400">*</span>
                        </label>
                        <span id="charCounter" class="text-[11px] text-[#7D8696] mp-num">0 / 1000</span>
                    </div>
                    <textarea name="description" id="descTextarea" rows="4" required minlength="10" maxlength="1000"
                              placeholder="Tell us how we can make your stay more comfortable..."
                              class="mp-input resize-none">{{ old('description', request('desc')) }}</textarea>
                    @error('description')
                        <div class="text-xs text-rose-400 mt-1.5">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Action Buttons --}}
                <div class="grid grid-cols-3 gap-2.5 pt-1">
                    <a href="{{ route('guest.landing', $token) }}" class="mp-btn-ghost col-span-1">
                        <i class="bi bi-arrow-left"></i> Back
                    </a>
                    <button type="submit" id="submitBtn" class="mp-btn col-span-2">
                        <i class="bi bi-send"></i>
                        <span>Submit Request</span>
                    </button>
                </div>
            </form>
        </div>

        {{-- Subtle Inline AI Concierge Note --}}
        <div class="mp-note items-center justify-between">
            <div class="flex items-start gap-2.5 min-w-0">
                <i class="bi bi-stars text-[color:var(--mp-gold)] text-sm mt-0.5 shrink-0"></i>
                <p class="text-xs text-[#98A0AE] leading-relaxed">
                    Just need a quick answer? Ask <strong class="text-[#F6F1E7]">Madi AI</strong> for instant hotel info.
                </p>
            </div>
            <button type="button" onclick="openAiConciergeDrawer()"
                    class="mp-chip is-gold shrink-0 !text-[11px]">
                Ask Madi
            </button>
        </div>

    </div>

    {{-- 2. Fixed Bottom Navigation Bar --}}
    @include('guest.partials.bottom-nav', ['active' => 'service'])

</div>

@push('scripts')
<script>
const textarea = document.getElementById('descTextarea');
const counter = document.getElementById('charCounter');
const catSelect = document.getElementById('categorySelect');

function updateCount() {
    const len = textarea.value.length;
    counter.textContent = `${len} / 1000`;
    counter.className = len > 0 && len < 10
        ? 'text-[11px] text-rose-400 mp-num'
        : 'text-[11px] text-[#7D8696] mp-num';
}

textarea.addEventListener('input', updateCount);
updateCount();

document.querySelectorAll('.quick-chip').forEach(chip => {
    chip.addEventListener('click', function() {
        const text = this.getAttribute('data-text');
        const catKey = this.getAttribute('data-category');

        if (!textarea.value.trim() || textarea.value.length < 5) {
            textarea.value = text;
        } else if (!textarea.value.includes(text)) {
            textarea.value = textarea.value.trim() + ' ' + text;
        }

        if (catSelect && catKey) {
            for (let i = 0; i < catSelect.options.length; i++) {
                const optText = (catSelect.options[i].getAttribute('data-name') || catSelect.options[i].text).toLowerCase();
                if (optText.includes(catKey)) {
                    catSelect.selectedIndex = i;
                    break;
                }
            }
        }

        this.classList.add('is-active');
        setTimeout(() => this.classList.remove('is-active'), 450);

        updateCount();
        textarea.focus();
    });
});

window.addEventListener('DOMContentLoaded', () => {
    updateCount();
    const urlParams = new URLSearchParams(window.location.search);
    const catParam = urlParams.get('category');
    if (catParam && catSelect) {
        for (let i = 0; i < catSelect.options.length; i++) {
            const optText = (catSelect.options[i].getAttribute('data-name') || catSelect.options[i].text).toLowerCase();
            if (optText.includes(catParam.toLowerCase())) {
                catSelect.selectedIndex = i;
                break;
            }
        }
    }
});

document.getElementById('serviceForm').addEventListener('submit', function() {
    const btn = document.getElementById('submitBtn');
    btn.innerHTML = '<span class="inline-block w-3.5 h-3.5 border-2 border-[#140F04] border-t-transparent rounded-full animate-spin mr-1.5"></span>Submitting...';
    btn.disabled = true;
});
</script>
@endpush
@endsection
