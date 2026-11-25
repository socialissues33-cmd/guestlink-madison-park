{{-- File: guestlink/resources/views/guest/landing.blade.php --}}
@extends('layouts.guest')
@section('title', 'Welcome')

@section('content')
@php
    $hour = (int) now()->format('G');
    $greeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');

    $services = [
        ['route' => 'guest.food',        'icon' => 'bi-cup-hot',           'title' => 'In-Room Dining',  'desc' => 'Times Square menu'],
        ['route' => 'guest.service',     'icon' => 'bi-bell',              'title' => 'Guest Services',  'desc' => 'Towels & housekeeping'],
        ['route' => 'guest.maintenance', 'icon' => 'bi-wrench-adjustable', 'title' => 'Room Repairs',    'desc' => 'Report with a photo'],
        ['route' => 'guest.track',       'icon' => 'bi-chat-dots',         'title' => 'Track & Chat',    'desc' => 'Live updates & desk'],
    ];
    $facilities = [
        ['img' => 'madison-facade-porte-cochere.jpg',    'tag' => 'Main Entrance', 'title' => 'Valet & Transfers',       'desc' => 'Chauffeured airport transport & bellhop.',      'meta' => 'Available 24 hours'],
        ['img' => 'madison-grounds-night.jpg',           'tag' => 'Courtyard',     'title' => 'Private Grounds',         'desc' => 'Evening walkways, fountains & lounge deck.',    'meta' => 'Exclusive to guests'],
    ];
@endphp

<div class="mp-shell pb-36 antialiased">

    {{-- Shared Sticky Frosted Header --}}
    @include('guest.partials.header', ['subtitle' => 'Hotel & Residences'])

    <div class="px-4 pt-5 space-y-7">

        {{-- Editorial Suite Hero --}}
        <section class="mp-page-hero mp-landing-hero min-h-[250px] flex flex-col justify-between p-5"
                 style="background-image: linear-gradient(180deg, rgba(8,12,20,.28) 0%, rgba(8,12,20,.72) 55%, rgba(8,12,20,.98) 100%), url('{{ asset('images/hotel/madison-entrance-night2-wide.jpg') }}'); background-position: center 22%; background-size: cover;">
            <div class="flex items-center justify-between gap-2">
                <span class="mp-photo-badge inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-[#080C14]/75 backdrop-blur-md border border-[color:var(--mp-line)] text-[11px] font-medium tracking-wider uppercase text-[#F4DC8B]">
                    {{ $room->room_type ?? 'Executive Suite' }} &middot; Floor {{ $room->floor ?? 1 }}
                </span>
                <span class="inline-flex items-center gap-1.5 text-[11px] text-emerald-300 bg-[#080C14]/70 backdrop-blur-md px-2.5 py-1 rounded-full border border-emerald-500/25">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                    Desk Open
                </span>
            </div>

            <div class="pt-12">
                <p class="mp-eyebrow mb-1.5">{{ $greeting }}</p>
                <h1 class="font-serif text-[1.7rem] font-semibold text-[#F6F1E7] leading-[1.15]">
                    Welcome to Room {{ $room->room_number }}
                </h1>
                <p class="text-[13px] text-[#C8C2B5] leading-relaxed mt-2 max-w-[34ch]">
                    Dine in, request housekeeping, or message our team &mdash; everything handled from your suite.
                </p>
            </div>
        </section>

        {{-- Optional Hotel Announcement Banner (controlled from Admin Overview) --}}
        @php $hotelNotice = trim((string) \Illuminate\Support\Facades\Cache::get('guestlink_guest_notice', '')); @endphp
        @if($hotelNotice !== '')
        <section class="mp-surface px-4 py-3 flex items-start gap-3 border-[color:var(--mp-line-strong)]" role="status" aria-label="Hotel Announcement">
            <i class="bi bi-megaphone text-[color:var(--mp-gold-bright)] text-base shrink-0 mt-0.5" aria-hidden="true"></i>
            <div class="min-w-0">
                <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-[color:var(--mp-gold-bright)] mb-0.5">Hotel Notice</p>
                <p class="text-[12.5px] leading-snug text-[#F6F1E7]">{{ $hotelNotice }}</p>
            </div>
        </section>
        @endif

        {{-- Editorial 2×2 Services Grid (Left-aligned, handcrafted rhythm) --}}
        <section aria-labelledby="svc-h">
            <div class="mp-section-head">
                <h2 id="svc-h">Suite Services</h2>
                <span class="mp-meta">Instant Dispatch</span>
            </div>
            <div class="grid grid-cols-2 gap-3">
                @foreach($services as $s)
                <a href="{{ route($s['route'], $token) }}" id="svc-{{ $s['route'] }}"
                   class="mp-card group p-4 flex flex-col justify-between min-h-[126px]">
                    <div class="flex items-start justify-between">
                        <span class="mp-icon"><i class="bi {{ $s['icon'] }}" aria-hidden="true"></i></span>
                        <i class="bi bi-arrow-up-right text-xs text-[#6E7788] group-hover:text-[color:var(--mp-gold-bright)] transition-colors" aria-hidden="true"></i>
                    </div>
                    <div class="mt-4">
                        <span class="block font-serif font-semibold text-[0.98rem] text-[#F6F1E7] leading-snug">{{ $s['title'] }}</span>
                        <span class="block text-[11.5px] text-[#98A0AE] mt-0.5 leading-snug">{{ $s['desc'] }}</span>
                    </div>
                </a>
                @endforeach
            </div>
        </section>

        {{-- AI Concierge & Front Desk Quick Strip --}}
        <section class="mp-surface p-4 flex items-center justify-between gap-3" aria-label="Concierge and front desk">
            <div class="flex items-center gap-3 min-w-0">
                <span class="mp-icon"><i class="bi bi-stars" aria-hidden="true"></i></span>
                <div class="min-w-0">
                    <p class="font-serif text-[0.95rem] font-semibold text-[#F6F1E7] leading-tight">Madi &middot; AI Concierge</p>
                    <p class="text-[11.5px] text-[#98A0AE] leading-snug mt-0.5">24/7 AI answers, voice &amp; photo help</p>
                </div>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <a href="tel:0" id="call-front-desk" aria-label="Call front desk extension 0"
                   class="mp-btn-ghost !min-h-[38px] !px-2.5 !py-1.5 !text-xs">
                    <i class="bi bi-telephone" aria-hidden="true"></i>
                </a>
                <button type="button" onclick="if(typeof openAiConciergeDrawer === 'function') openAiConciergeDrawer();"
                        class="mp-btn !min-h-[38px] !px-3.5 !py-1.5 !text-xs whitespace-nowrap">
                    <span>Ask Madi</span>
                </button>
            </div>
        </section>

        {{-- Hotel-Wide Free Wi-Fi Strip (Scan-Only Reconnect · No Voucher Required) --}}
        @php
            $wifiCfg = \App\Services\QrUrlService::getWifiConfig();
            $wifiSsid = $wifiCfg['wifiSsid'];
            $wifiPass = $wifiCfg['wifiPassword'];
            $wifiPayload = $wifiCfg['wifiPayload'];
            $wifiSvg = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')->size(190)->margin(2)->errorCorrection('M')->generate($wifiPayload);
        @endphp
        <section class="mp-surface p-3.5 space-y-3" aria-label="Complimentary hotel Wi-Fi">
            <div class="flex items-center gap-3 min-w-0">
                <span class="mp-icon shrink-0"><i class="bi bi-wifi" aria-hidden="true"></i></span>
                <div class="min-w-0 flex-1">
                    <div class="flex items-center justify-between gap-2">
                        <p class="font-serif text-[0.95rem] font-semibold text-[#F6F1E7] leading-tight whitespace-nowrap">Free Hotel Wi-Fi</p>
                        <span class="text-[11px] px-2 py-0.5 rounded-full bg-emerald-500/15 text-emerald-300 border border-emerald-500/30 font-medium whitespace-nowrap shrink-0">No Voucher</span>
                    </div>
                    <p class="text-[11.5px] text-[#98A0AE] truncate mt-0.5">
                        Network: <strong class="text-[#E7E2D7]">{{ $wifiSsid }}</strong> &middot; All floors
                    </p>
                </div>
            </div>
            <div class="flex items-center justify-between gap-2 pt-2.5 border-t border-[color:var(--mp-hairline)]">
                <span class="text-[11px] text-[#8C94A4] leading-snug">Scan QR to connect or reconnect</span>
                <button type="button"
                        onclick="document.getElementById('wifiQrModal').classList.remove('hidden')"
                        class="mp-btn !min-h-[36px] !px-3.5 !py-1.5 !text-xs whitespace-nowrap shrink-0"
                        title="Scan Wi-Fi QR to Connect or Reconnect">
                    <i class="bi bi-qr-code-scan" aria-hidden="true"></i>
                    <span>Scan to Connect</span>
                </button>
            </div>
        </section>

        {{-- Wi-Fi Auto-Connect / Reconnect QR Modal --}}
        <div id="wifiQrModal" class="hidden fixed inset-0 z-50 bg-black/80 backdrop-blur-sm flex items-center justify-center p-4" onclick="if(event.target===this)this.classList.add('hidden')">
            <div class="mp-card max-w-xs w-full p-5 text-center space-y-3">
                <div class="flex items-center justify-between">
                    <span class="mp-eyebrow">All Floors &middot; No Voucher</span>
                    <button type="button" onclick="document.getElementById('wifiQrModal').classList.add('hidden')" aria-label="Close" class="text-[#98A0AE] hover:text-white text-sm"><i class="bi bi-x-lg"></i></button>
                </div>
                <h3 class="font-serif text-lg font-semibold text-[#F6F1E7]">Scan to Connect Wi-Fi</h3>
                <div class="bg-white p-3 rounded-xl inline-block mx-auto shadow-lg">
                    {!! $wifiSvg !!}
                </div>
                <div class="text-xs text-[#C8C2B5] space-y-1">
                    <div>Network: <strong class="text-[#F6F1E7]">{{ $wifiSsid }}</strong></div>
                    <p class="text-[11px] text-[#8C94A4] pt-1">No voucher needed. Even after a scheduled Wi-Fi reset, simply scan this QR to reconnect automatically.</p>
                </div>
            </div>
        </div>

        {{-- Events & Banquets Editorial Feature --}}
        <a href="{{ route('guest.events', $token) }}" id="events-banner"
           class="mp-page-hero block group">
            <div class="relative h-36 overflow-hidden">
                <img src="{{ asset('images/hotel/madison-event-plaza.jpg') }}" alt="Function halls and event plaza"
                     class="w-full h-full object-cover opacity-80 group-hover:scale-[1.03] transition-transform duration-500" loading="lazy">
                <div class="absolute inset-0 bg-gradient-to-t from-[#080C14] via-[#080C14]/55 to-transparent"></div>
                <div class="absolute inset-x-0 bottom-0 p-4 flex items-end justify-between gap-3">
                    <div>
                        <p class="mp-eyebrow">Event Plaza &amp; Halls</p>
                        <p class="font-serif text-lg font-semibold text-[#F6F1E7] leading-tight mt-0.5">Event Plaza &amp; Function Halls</p>
                        <p class="text-xs text-[#C8C2B5] mt-1">Event Plaza (250 pax) &middot; Hall Rooms AB–IJ (50+ pax) &middot; from ₱350/pax</p>
                    </div>
                    <span class="mp-banner-cta-circle w-9 h-9 rounded-full border border-[#C9A24A] bg-[#C9A24A] text-[#141006] inline-flex items-center justify-center shrink-0 group-hover:bg-[#D6B25E] transition-colors">
                        <i class="bi bi-arrow-right text-sm font-bold" aria-hidden="true"></i>
                    </span>
                </div>
            </div>
        </a>

        {{-- Horizontal Hotel Guide --}}
        <section aria-labelledby="fac-h">
            <div class="mp-section-head">
                <h2 id="fac-h">Around the Hotel</h2>
                <span class="mp-meta">Swipe</span>
            </div>
            <div class="flex gap-3 overflow-x-auto no-scrollbar pb-1 snap-x">
                @foreach($facilities as $f)
                <article class="mp-card shrink-0 w-[252px] snap-start overflow-hidden flex flex-col">
                    <div class="h-28 relative overflow-hidden">
                        <img src="{{ asset('images/hotel/'.$f['img']) }}" alt="{{ $f['title'] }}" class="w-full h-full object-cover" loading="lazy">
                        <div class="absolute inset-0 bg-gradient-to-t from-[#0E1523] via-transparent to-transparent"></div>
                        <span class="mp-photo-badge absolute top-2.5 left-2.5 text-[11px] px-2.5 py-0.5 rounded-md bg-[#080C14]/80 backdrop-blur-sm border border-[color:var(--mp-hairline-md)] text-[#F4DC8B] font-semibold">{{ $f['tag'] }}</span>
                    </div>
                    <div class="p-3.5 flex flex-col gap-1 flex-1 justify-between">
                        <div>
                            <h3 class="font-serif text-[0.95rem] font-semibold text-[#F6F1E7]">{{ $f['title'] }}</h3>
                            <p class="text-xs text-[#98A0AE] leading-snug mt-0.5">{{ $f['desc'] }}</p>
                        </div>
                        <p class="text-[11px] text-[color:var(--mp-gold)] font-medium mt-2 pt-2 border-t border-[color:var(--mp-hairline)]">{{ $f['meta'] }}</p>
                    </div>
                </article>
                @endforeach
            </div>
        </section>

    </div>

    {{-- Fixed 5-Tab Bottom Navigation --}}
    @include('guest.partials.bottom-nav', ['active' => 'home'])

</div>
@endsection
