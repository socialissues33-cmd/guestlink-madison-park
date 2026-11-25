{{-- Shared guest header. Usage: @include('guest.partials.header', ['subtitle' => 'In-Room Dining']) --}}
@php
    $isGuestHome = request()->routeIs('guest.landing');
    $onTrack = request()->routeIs('guest.track');
    $homeUrl = route('guest.landing', $token);
@endphp
<header class="mp-header !px-2 sm:!px-4">
    <div class="flex items-center justify-between w-full gap-1 min-[420px]:gap-1.5">
        <div class="flex items-center gap-1 min-[420px]:gap-1.5 min-w-0 flex-1">
            @if(!$isGuestHome)
            <a href="{{ $homeUrl }}" id="btnHeaderBack"
               onclick="if (window.history.length > 1 && document.referrer && document.referrer.indexOf(window.location.origin) === 0 && document.referrer !== window.location.href) { event.preventDefault(); window.history.back(); }"
               aria-label="Go back" title="Back"
               class="w-8 h-8 sm:w-[34px] sm:h-[34px] rounded-full inline-flex items-center justify-center shrink-0 border border-[color:var(--mp-hairline-md)] bg-white/[0.04] text-[#E7E2D7] hover:border-[color:var(--mp-line-strong)] hover:text-[color:var(--mp-gold-bright)] transition-colors">
                <i class="bi bi-arrow-left text-sm" aria-hidden="true"></i>
            </a>
            @endif
            <a href="{{ $homeUrl }}" class="mp-brand min-w-0 !gap-1 min-[420px]:!gap-1.5" aria-label="Madison Park Hotel – back to home">
                <img src="{{ asset('images/hotel/madison-logo.png') }}?v={{ @filemtime(public_path('images/hotel/madison-logo.png')) }}" alt="" class="!w-7 !h-7 min-[420px]:!w-8 min-[420px]:!h-8 shrink-0">
                <span class="min-w-0 overflow-hidden">
                    <span class="mp-brand-name block truncate !text-[10.5px] min-[420px]:!text-[11.5px] !tracking-[0.04em] min-[420px]:!tracking-[0.08em]">Madison Park</span>
                    <span class="mp-brand-sub block truncate !text-[8px] min-[420px]:!text-[9px] !tracking-[0.06em] min-[420px]:!tracking-[0.11em]">{{ $subtitle ?? 'Hotel & Residences' }}</span>
                </span>
            </a>
        </div>
        <div class="flex items-center gap-1 min-[420px]:gap-1.5 shrink-0">
            <span class="mp-room-chip !h-[34px] !px-2 min-[420px]:!px-2.5 !py-0 !text-[11px] min-[420px]:!text-[11.5px] whitespace-nowrap">
                <i class="bi bi-door-closed hidden min-[440px]:inline text-xs" aria-hidden="true"></i>
                <span class="min-[440px]:hidden">Rm {{ $room->room_number ?? '' }}</span>
                <span class="hidden min-[440px]:inline">Room {{ $room->room_number ?? '' }}</span>
            </span>
            <button type="button" id="btnGuestThemeToggle" onclick="toggleGuestLinkTheme()"
                    aria-label="Switch Light or Dark Mode" title="Switch Light / Dark Mode"
                    class="mp-theme-toggle !h-[34px] !w-[34px] !p-0 shrink-0">
                <span class="icon-sun text-[13px]"><i class="bi bi-sun-fill"></i></span>
                <span class="icon-moon text-[13px]"><i class="bi bi-moon-stars-fill"></i></span>
            </button>
            @if(!$onTrack)
            <a href="{{ route('guest.track', $token) }}" id="btnHeaderTrackChat"
               aria-label="Track orders and requests" title="Track Orders &amp; Requests"
               class="h-[34px] px-2 min-[420px]:px-2.5 sm:px-3 rounded-full inline-flex items-center justify-center gap-1 min-[420px]:gap-1.5 border transition-colors shrink-0 border-[color:var(--mp-line-strong)] bg-[rgba(201,162,74,0.1)] text-[color:var(--mp-gold-bright)] hover:bg-[color:var(--mp-gold)] hover:text-[#0A0E16]">
                <i class="bi bi-clipboard2-check text-[13px]" aria-hidden="true"></i>
                <span class="text-[11.5px] font-semibold tracking-wide">Track</span>
            </a>
            @endif
        </div>
    </div>
</header>
