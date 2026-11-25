{{-- File: guestlink/resources/views/guest/qr-invalid.blade.php --}}
@extends('layouts.guest')
@section('title', ($reason ?? 'invalid') === 'inactive' ? 'Room QR Session Inactive' : 'Invalid or Expired QR Code')

@section('content')
<div class="mp-shell justify-between antialiased">

    {{-- Top Sticky Header --}}
    <header class="mp-header">
        <div class="flex items-center justify-between w-full gap-2">
            <div class="mp-brand">
                <img src="{{ asset('images/hotel/madison-logo.png') }}?v={{ @filemtime(public_path('images/hotel/madison-logo.png')) }}" alt="Madison Park Hotel" style="width:34px;height:34px">
                <span>
                    <span class="mp-brand-name" style="font-size:.82rem">Madison Park</span>
                    <span class="mp-brand-sub" style="font-size:.625rem">Hotel &amp; Residences</span>
                </span>
            </div>
            <span class="px-2.5 py-1 rounded-full text-[11px] font-semibold bg-rose-500/10 border border-rose-500/30 text-rose-300 inline-flex items-center gap-1.5">
                <i class="bi bi-shield-lock"></i>
                {{ ($reason ?? 'invalid') === 'inactive' ? 'Session Inactive' : 'QR Expired' }}
            </span>
        </div>
    </header>

    {{-- Main Security Notice Card --}}
    <div class="px-5 py-8 my-auto">
        <div class="mp-surface p-6 text-center space-y-5">
            <div class="w-14 h-14 rounded-2xl bg-rose-500/10 border border-rose-500/30 flex items-center justify-center text-2xl text-rose-300 mx-auto">
                <i class="bi {{ ($reason ?? 'invalid') === 'inactive' ? 'bi-door-closed' : 'bi-qr-code-scan' }}"></i>
            </div>

            @if(($reason ?? 'invalid') === 'inactive' && isset($room))
                <div class="space-y-2">
                    <p class="mp-eyebrow">Room {{ $room->room_number }} &middot; Checked Out</p>
                    <h1 class="font-serif text-xl font-semibold text-[#F6F1E7]">
                        Room QR Access Currently Inactive
                    </h1>
                    <p class="text-xs text-[#98A0AE] leading-relaxed">
                        The digital guest session for <strong class="text-[#F6F1E7]">Room {{ $room->room_number }}</strong> is deactivated following check-out or maintenance.
                    </p>
                </div>
            @else
                <div class="space-y-2">
                    <p class="mp-eyebrow">QR Security Verification</p>
                    <h1 class="font-serif text-xl font-semibold text-[#F6F1E7]">
                        Invalid or Expired Room QR Code
                    </h1>
                    <p class="text-xs text-[#98A0AE] leading-relaxed">
                        This room QR link is no longer active or was regenerated for guest privacy. Please scan the standee inside your suite.
                    </p>
                </div>
            @endif

            <div class="mp-note text-left">
                <i class="bi bi-info-circle text-[color:var(--mp-gold)] text-sm mt-0.5 shrink-0"></i>
                <p class="text-xs text-[#98A0AE] leading-relaxed">
                    Contact <strong class="text-[#F6F1E7]">Front Desk (Ext. 0)</strong> to activate your room session or obtain your updated QR access code.
                </p>
            </div>

            <div class="space-y-2.5 pt-1">
                <a href="tel:0" class="mp-btn w-full">
                    <i class="bi bi-telephone"></i>
                    <span>Call Front Desk (Ext. 0)</span>
                </a>
                <a href="{{ route('guest.test-qr') }}" class="mp-btn-ghost w-full">
                    <i class="bi bi-qr-code"></i>
                    <span>Open Room QR Scanner</span>
                </a>
            </div>
        </div>
    </div>

    <footer class="px-5 py-4 text-center text-[11px] text-[#647082] border-t border-[color:var(--mp-hairline)]">
        Madison Park Hotel &amp; Residences &middot; GuestLink Security
    </footer>

</div>
@endsection
