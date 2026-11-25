<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Room {{ $room->room_number }} QR Code – Madison Park Hotel GuestLink</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="icon" type="image/png" href="{{ asset('images/hotel/madison-logo.png') }}">
    <style>
        :root {
            --navy: #0B1422;
            --navy-mid: #162236;
            --gold: #C9A84C;
            --gold-light: #E0C06E;
            --border: rgba(201,168,76,0.3);
        }
        body {
            font-family: 'Inter', sans-serif;
            background: #0B1422;
            color: #fff;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem 1rem;
            margin: 0;
        }
        .qr-card-container {
            max-width: 620px;
            width: 100%;
            background: linear-gradient(180deg, #162236 0%, #0F1B2D 100%);
            border: 1px solid var(--border);
            border-radius: 24px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.6), 0 0 40px rgba(201,168,76,0.15);
            padding: 2.25rem 1.75rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        .hotel-brand {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.85rem;
            margin-bottom: 1.25rem;
        }
        .hotel-logo {
            width: 48px;
            height: 48px;
            object-fit: contain;
            filter: drop-shadow(0 2px 8px rgba(201,168,76,0.4));
        }
        .hotel-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.45rem;
            font-weight: 700;
            color: var(--gold);
            margin: 0;
            letter-spacing: 0.5px;
        }
        .hotel-sub {
            font-size: 0.74rem;
            color: #8899AA;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        /* Room Switcher Box */
        .room-selector-box {
            background: rgba(0, 0, 0, 0.35);
            border: 1px solid rgba(201, 168, 76, 0.25);
            border-radius: 16px;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }
        .room-selector-title {
            font-size: 0.78rem;
            color: #8899AA;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
        }
        .room-select-dropdown {
            background: #0B1422;
            border: 1px solid var(--border);
            color: #E8D08D;
            font-weight: 600;
            border-radius: 10px;
            padding: 0.5rem 1rem;
            font-size: 0.95rem;
            width: 100%;
            max-width: 320px;
            margin: 0 auto;
            cursor: pointer;
        }
        .room-select-dropdown:focus {
            outline: none;
            box-shadow: 0 0 0 2px rgba(201,168,76,0.4);
            border-color: var(--gold);
        }
        .room-pills-row {
            display: flex;
            flex-wrap: wrap;
            gap: 0.4rem;
            justify-content: center;
            margin-top: 0.75rem;
        }
        .room-quick-pill {
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.12);
            color: #CCD6E0;
            padding: 0.25rem 0.65rem;
            border-radius: 20px;
            font-size: 0.76rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.15s;
        }
        .room-quick-pill:hover {
            background: rgba(201, 168, 76, 0.25);
            color: #FFFFFF;
            border-color: var(--gold);
        }
        .room-quick-pill.active {
            background: linear-gradient(135deg, var(--gold), var(--gold-light));
            color: #0B1422;
            font-weight: 700;
            border-color: var(--gold);
            box-shadow: 0 2px 8px rgba(201,168,76,0.35);
        }

        /* Nav Tabs */
        .nav-tabs-custom {
            display: flex;
            gap: 0.5rem;
            background: rgba(0,0,0,0.35);
            padding: 0.35rem;
            border-radius: 14px;
            margin-bottom: 1.5rem;
            border: 1px solid rgba(255,255,255,0.08);
        }
        .tab-btn {
            flex: 1;
            padding: 0.65rem 0.75rem;
            border-radius: 10px;
            border: none;
            background: transparent;
            color: #8899AA;
            font-size: 0.84rem;
            font-weight: 600;
            transition: all 0.2s;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
        }
        .tab-btn.active {
            background: linear-gradient(135deg, var(--gold), var(--gold-light));
            color: #0B1422;
            font-weight: 700;
            box-shadow: 0 4px 12px rgba(201,168,76,0.35);
        }

        .qr-display-box {
            background: #FFFFFF;
            padding: 1.25rem;
            border-radius: 20px;
            display: inline-block;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
            border: 4px solid #FFFFFF;
            margin-bottom: 1.25rem;
        }
        .qr-display-box svg {
            display: block;
            width: min(280px, 68vw);
            height: min(280px, 68vw);
        }
        .scan-instruction {
            font-size: 1rem;
            font-weight: 700;
            color: #FFFFFF;
            margin-bottom: 0.25rem;
        }
        .scan-sub {
            font-size: 0.82rem;
            color: #8899AA;
            margin-bottom: 1.25rem;
        }
        .url-box {
            background: rgba(0,0,0,0.4);
            border: 1px solid rgba(255,255,255,0.12);
            border-radius: 12px;
            padding: 0.75rem 1rem;
            font-family: monospace;
            font-size: 0.78rem;
            color: #E8D08D;
            word-break: break-all;
            margin-bottom: 1.25rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.5rem;
        }
        .btn-gold {
            background: linear-gradient(135deg, var(--gold), var(--gold-light));
            color: #0B1422;
            font-weight: 700;
            border: none;
            border-radius: 12px;
            padding: 0.75rem 1.5rem;
            font-size: 0.92rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.2s;
            box-shadow: 0 4px 15px rgba(201,168,76,0.3);
        }
        .btn-gold:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(201,168,76,0.45);
            color: #0B1422;
        }
        .troubleshoot-box {
            background: rgba(220, 53, 69, 0.1);
            border: 1px solid rgba(220, 53, 69, 0.3);
            border-radius: 12px;
            padding: 0.85rem 1rem;
            text-align: left;
            margin-top: 1.5rem;
            font-size: 0.78rem;
        }
        .troubleshoot-title {
            color: #FFA4A4;
            font-weight: 700;
            margin-bottom: 0.35rem;
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }
        .tips-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.75rem;
            text-align: left;
            margin-top: 1.25rem;
            padding-top: 1.25rem;
            border-top: 1px solid rgba(255,255,255,0.1);
        }
        .tip-box {
            background: rgba(255,255,255,0.04);
            border-radius: 12px;
            padding: 0.75rem;
            border: 1px solid rgba(255,255,255,0.06);
        }
        .tip-title {
            font-size: 0.76rem;
            font-weight: 700;
            color: #E8D08D;
            margin-bottom: 0.25rem;
        }
        .tip-desc {
            font-size: 0.72rem;
            color: #8899AA;
            line-height: 1.35;
        }
    </style>
    {{-- Madison Park brand layer --}}
    <script>(function(){try{var t=localStorage.getItem('guestlink_theme');if(t==='light'||t==='dark'){document.documentElement.setAttribute('data-theme',t);}}catch(e){}})();</script>
    <link rel="stylesheet" href="{{ asset('css/madison-theme.css') }}?v={{ @filemtime(public_path('css/madison-theme.css')) }}">
    <style>
        html:not([data-theme="light"]) body {
            background: #070B12 !important;
            background-image: radial-gradient(ellipse 80% 340px at 50% 0%, rgba(201,162,74,0.08), transparent 72%) !important;
        }
        html:not([data-theme="light"]) .qr-card-container {
            background: linear-gradient(180deg, rgba(17,25,40,0.95) 0%, rgba(11,16,27,0.98) 100%) !important;
            border-radius: 20px !important;
            box-shadow: 0 28px 70px -20px rgba(0,0,0,.85), inset 0 1px 0 rgba(255,255,255,0.05) !important;
            border: 1px solid var(--mp-hairline-md) !important;
        }
        html[data-theme="light"] body {
            background: #F4F5F7 !important;
            color: #0F172A !important;
        }
        html[data-theme="light"] .qr-card-container {
            background: #FFFFFF !important;
            border: 1px solid #D4CFC2 !important;
            box-shadow: 0 20px 50px rgba(15, 23, 42, 0.1) !important;
            color: #0F172A !important;
        }
        html[data-theme="light"] .room-selector-box,
        html[data-theme="light"] .nav-tabs-custom,
        html[data-theme="light"] .url-box,
        html[data-theme="light"] .tip-box {
            background: #F8FAFC !important;
            border: 1px solid #CBD5E1 !important;
            color: #0F172A !important;
        }
        html[data-theme="light"] .hotel-title,
        html[data-theme="light"] .tip-title { color: #0F172A !important; }
        html[data-theme="light"] .tip-desc { color: #475569 !important; }
        .hotel-logo { width: 54px; height: 54px; filter: none; }
        .hotel-title { font-size: 1.15rem; letter-spacing: .2em; text-transform: uppercase; font-weight: 600; color: var(--mp-ivory); }
        .hotel-sub { color: var(--mp-gold); letter-spacing: .24em; font-size: .6875rem; }
        html:not([data-theme="light"]) .room-selector-box,
        html:not([data-theme="light"]) .nav-tabs-custom,
        html:not([data-theme="light"]) .url-box,
        html:not([data-theme="light"]) .tip-box {
            background: #080C14 !important;
            border: 1px solid var(--mp-hairline-md) !important;
        }
        .room-quick-pill.active, .tab-btn.active, .btn-gold {
            background: var(--mp-metal) !important;
            color: #140F04 !important;
            box-shadow: none !important;
        }
    </style>
</head>
<body>

<div class="qr-card-container">
    {{-- Hotel Brand Header --}}
    <div class="hotel-brand">
        <img src="{{ asset('images/hotel/madison-logo.png') }}?v={{ @filemtime(public_path('images/hotel/madison-logo.png')) }}" alt="Madison Park Logo" class="hotel-logo">
        <div>
            <h1 class="hotel-title">Madison Park</h1>
            <div class="hotel-sub">GuestLink &middot; Room QR Access</div>
        </div>
    </div>

    {{-- Room Selection & Switcher Box --}}
    <div class="room-selector-box">
        <div class="room-selector-title">
            <i class="bi bi-door-open"></i> Select Hotel Room to Test QR
        </div>
        <select class="room-select-dropdown" onchange="window.location.href='?room=' + this.value">
            @foreach($allRooms as $r)
                <option value="{{ $r->room_number }}" {{ $r->id === $room->id ? 'selected' : '' }}>
                    Room {{ $r->room_number }} ({{ $r->room_type }}) — Floor {{ $r->floor }}
                </option>
            @endforeach
        </select>
        
        <div class="room-pills-row">
            @foreach($allRooms->take(8) as $r)
                <a href="?room={{ $r->room_number }}" class="room-quick-pill {{ $r->id === $room->id ? 'active' : '' }}">
                    Room {{ $r->room_number }}
                </a>
            @endforeach
        </div>
    </div>

    {{-- Tabs: Public Tunnel vs Local WiFi vs Free Hotel Wi-Fi --}}
    @php
        $isLocalHost = ($preferredMode ?? 'local') === 'local' || empty($hasTunnel) || str_contains($publicUrl, '127.0.0.1') || str_contains($publicUrl, 'localhost');
    @endphp
    <div class="nav-tabs-custom">
        <button type="button" class="tab-btn {{ $isLocalHost ? '' : 'active' }}" id="btnTabPublic" onclick="switchQr('public')">
            <i class="bi bi-globe"></i> Worldwide Public Link
        </button>
        <button type="button" class="tab-btn {{ $isLocalHost ? 'active' : '' }}" id="btnTabLocal" onclick="switchQr('local')">
            <i class="bi bi-wifi"></i> Local Wi-Fi ({{ $lanHost }}{{ $portSuffix }})
        </button>
        <button type="button" class="tab-btn" id="btnTabWifi" onclick="switchQr('wifi')">
            <i class="bi bi-router"></i> Free Hotel Wi-Fi (No Voucher)
        </button>
    </div>

    {{-- Public QR Pane --}}
    <div id="panePublic" style="{{ $isLocalHost ? 'display:none;' : '' }}">
        <div class="qr-display-box">
            {!! $publicQr !!}
        </div>
        <div class="scan-instruction"><i class="bi bi-camera me-1"></i> Scan with Phone Camera</div>
        <div class="scan-sub">Guaranteed to work on any phone (iOS / Android) using 4G/5G mobile cellular data or Wi-Fi</div>

        <div class="url-box">
            <span id="publicUrlText">{{ $publicUrl }}</span>
            <button type="button" class="btn btn-sm btn-outline-light py-0 px-2" style="font-size:0.7rem; white-space:nowrap;" onclick="copyUrl('{{ $publicUrl }}')">
                <i class="bi bi-copy me-1"></i> Copy
            </button>
        </div>

        <div class="d-flex gap-2 justify-content-center flex-wrap">
            <a href="{{ $publicUrl }}" target="_blank" class="btn-gold">
                <i class="bi bi-box-arrow-up-right"></i> Open Room {{ $room->room_number }} Portal in Browser
            </a>
        </div>
    </div>

    {{-- Local QR Pane --}}
    <div id="paneLocal" style="{{ $isLocalHost ? '' : 'display:none;' }}">
        <div class="qr-display-box">
            {!! $localQr !!}
        </div>
        <div class="scan-instruction"><i class="bi bi-wifi me-1"></i> Same Local Wi-Fi Scanner</div>
        <div class="scan-sub">Point phone camera here. Works when smartphone is connected to the SAME Wi-Fi network as this PC</div>

        <div class="url-box">
            <span id="localUrlText">{{ $localUrl }}</span>
            <button type="button" class="btn btn-sm btn-outline-light py-0 px-2" style="font-size:0.7rem; white-space:nowrap;" onclick="copyUrl('{{ $localUrl }}')">
                <i class="bi bi-copy me-1"></i> Copy
            </button>
        </div>

        <div class="d-flex gap-2 justify-content-center flex-wrap">
            <a href="{{ $localUrl }}" target="_blank" class="btn-gold">
                <i class="bi bi-box-arrow-up-right"></i> Open Local Portal
            </a>
        </div>
    </div>

    {{-- Free Hotel Wi-Fi Auto-Connect QR Pane --}}
    <div id="paneWifi" style="display:none;">
        <div class="qr-display-box">
            {!! $wifiQr !!}
        </div>
        <div class="scan-instruction"><i class="bi bi-router me-1"></i> Scan to Auto-Connect Hotel Wi-Fi</div>
        <div class="scan-sub">One free Wi-Fi network for all floors (1–4) &mdash; no voucher or password typing needed</div>

        <div class="url-box">
            <span>SSID: <strong>{{ $wifiSsid }}</strong> &middot; Password: <strong>{{ $wifiPassword ?: 'Open (No Password)' }}</strong></span>
            <button type="button" class="btn btn-sm btn-outline-light py-0 px-2" style="font-size:0.7rem; white-space:nowrap;" onclick="copyUrl('{{ $wifiPassword }}')">
                <i class="bi bi-copy me-1"></i> Copy Pass
            </button>
        </div>
    </div>

    {{-- Why did phone say "Site can't be reached"? Guide --}}
    <div class="troubleshoot-box">
        <div class="troubleshoot-title">
            <i class="bi bi-exclamation-triangle-fill"></i> Did your phone say "Site can't be reached"?
        </div>
        <div style="color: #CCD6E0; line-height: 1.45;">
            <strong>Solution:</strong> Use the <strong>"Worldwide Public Link"</strong> tab above! 
            If your phone is on cellular mobile data (4G/5G) or if your Wi-Fi router blocks device-to-device connections, the Local Wi-Fi link cannot be reached. The Worldwide Cloudflare link works anywhere in the world on any phone without restrictions.
        </div>
    </div>

    {{-- How to Scan Tips --}}
    <div class="tips-grid">
        <div class="tip-box">
            <div class="tip-title"><i class="bi bi-apple me-1"></i> iPhone / iPad (iOS)</div>
            <div class="tip-desc">Open default <strong>Camera</strong> app, point at the QR code, and tap the yellow banner that appears.</div>
        </div>
        <div class="tip-box">
            <div class="tip-title"><i class="bi bi-android2 me-1"></i> Android / Samsung</div>
            <div class="tip-desc">Open your <strong>Camera</strong> or <strong>Google Lens</strong>, point at the QR code, and tap the link pop-up.</div>
        </div>
    </div>

    <div class="mt-3 text-center">
        <a href="{{ route('login') }}" class="text-secondary text-decoration-none" style="font-size: 0.8rem;">
            <i class="bi bi-shield-lock me-1"></i> Staff & Admin Login Portal →
        </a>
    </div>
</div>

<script>
function switchQr(type) {
    const panePub = document.getElementById('panePublic');
    const paneLoc = document.getElementById('paneLocal');
    const paneWifi = document.getElementById('paneWifi');
    const btnPub  = document.getElementById('btnTabPublic');
    const btnLoc  = document.getElementById('btnTabLocal');
    const btnWifi = document.getElementById('btnTabWifi');

    panePub.style.display = type === 'public' ? 'block' : 'none';
    paneLoc.style.display = type === 'local' ? 'block' : 'none';
    if (paneWifi) paneWifi.style.display = type === 'wifi' ? 'block' : 'none';

    btnPub.classList.toggle('active', type === 'public');
    btnLoc.classList.toggle('active', type === 'local');
    if (btnWifi) btnWifi.classList.toggle('active', type === 'wifi');
}

function copyUrl(text) {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(text).then(() => {
            alert('Guest Portal URL copied to clipboard!\n' + text);
        });
    } else {
        prompt('Copy Guest Portal URL:', text);
    }
}
</script>
<script src="{{ asset('js/madison-icons.js') }}?v={{ @filemtime(public_path('js/madison-icons.js')) }}" defer></script>

</body>
</html>
