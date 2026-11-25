{{-- Shared guest bottom nav. Usage: @include('guest.partials.bottom-nav', ['active' => 'food']) --}}
@php
    $__leftItems = [
        'home' => ['guest.landing', 'bi-house-door', 'bi-house-door-fill', 'Home'],
        'food' => ['guest.food',    'bi-cup-hot',    'bi-cup-hot-fill',    'Dining'],
    ];
    $__rightItems = [
        'service'     => ['guest.service',     'bi-bell',             'bi-bell-fill',         'Services'],
        'maintenance' => ['guest.maintenance', 'bi-wrench-adjustable','bi-wrench-adjustable', 'Repairs'],
    ];
    $__active = $active ?? '';
@endphp
<nav class="fixed bottom-0 inset-x-0 z-40 max-w-md mx-auto px-2 pt-1.5 pb-[max(0.6rem,env(safe-area-inset-bottom))] overflow-visible" aria-label="Guest navigation">
    <div class="grid grid-cols-5 gap-1 items-end">
        {{-- Left 2 Tabs: Home & Dining --}}
        @foreach($__leftItems as $key => [$r, $ic, $icFill, $label])
            @php $on = $key === $__active; @endphp
            <a href="{{ route($r, $token) }}" @if($on) aria-current="page" @endif
               class="relative flex flex-col items-center justify-center min-h-[50px] rounded-xl transition-colors {{ $on ? 'text-[color:var(--mp-gold-bright)] bg-[rgba(201,162,74,0.08)]' : 'text-[#8C94A4] hover:text-[#F6F1E7]' }}">
                <i class="bi {{ $on ? $icFill : $ic }} text-[1.1rem] leading-none mb-1" aria-hidden="true"></i>
                <span class="text-[11px] tracking-[0.02em] leading-none {{ $on ? 'font-semibold' : 'font-medium' }}">{{ $label }}</span>
                @if($on)<span class="absolute top-0 left-1/2 -translate-x-1/2 w-6 h-[2px] rounded-full" style="background:var(--mp-gold)"></span>@endif
            </a>
        @endforeach

        {{-- Center Action Button: Madi Concierge (Flat Madison Park Logo Gold, zero neon glow) --}}
        <div class="flex flex-col items-center justify-end -mt-6">
            <button type="button" id="btnOpenAiConcierge"
                    onclick="if(typeof openAiConciergeDrawer === 'function') openAiConciergeDrawer();"
                    aria-label="Open Madi AI Concierge" title="Ask Madi AI Concierge"
                    class="group relative flex flex-col items-center justify-center cursor-pointer focus:outline-none">
                <span class="w-14 h-14 rounded-full flex items-center justify-center transition-[background-color,transform] duration-150 group-hover:bg-[#D6B25E] group-active:scale-95 ring-4 ring-[#080C14]"
                      style="background: #C9A24A; box-shadow: 0 6px 16px rgba(0,0,0,0.42), inset 0 1px 0 rgba(255,255,255,0.28);">
                    <i class="bi bi-stars text-[#141006] text-[1.35rem] leading-none" aria-hidden="true"></i>
                </span>
                <span class="text-[11px] font-semibold tracking-[0.02em] text-[color:var(--mp-gold-bright)] leading-none mt-1.5">
                    Ask Madi
                </span>
            </button>
        </div>

        {{-- Right 2 Tabs: Services & Repairs --}}
        @foreach($__rightItems as $key => [$r, $ic, $icFill, $label])
            @php $on = $key === $__active; @endphp
            <a href="{{ route($r, $token) }}" @if($on) aria-current="page" @endif
               class="relative flex flex-col items-center justify-center min-h-[50px] rounded-xl transition-colors {{ $on ? 'text-[color:var(--mp-gold-bright)] bg-[rgba(201,162,74,0.08)]' : 'text-[#8C94A4] hover:text-[#F6F1E7]' }}">
                <i class="bi {{ $on ? $icFill : $ic }} text-[1.1rem] leading-none mb-1" aria-hidden="true"></i>
                <span class="text-[11px] tracking-[0.02em] leading-none {{ $on ? 'font-semibold' : 'font-medium' }}">{{ $label }}</span>
                @if($on)<span class="absolute top-0 left-1/2 -translate-x-1/2 w-6 h-[2px] rounded-full" style="background:var(--mp-gold)"></span>@endif
            </a>
        @endforeach
    </div>
</nav>
