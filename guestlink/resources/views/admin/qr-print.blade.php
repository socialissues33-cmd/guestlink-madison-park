<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Code – Room {{ $room->room_number }} | Madison Park Hotel</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', sans-serif;
            background: #0B1422;
            color: #FFFFFF;
            min-height: 100vh;
            display: flex; align-items: center; justify-content: center;
            padding: 2rem 1rem;
        }
        .card {
            background: linear-gradient(180deg, #162236 0%, #0F1B2D 100%);
            border-radius: 24px;
            padding: 2.25rem 2rem;
            max-width: 440px;
            width: 100%;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0,0,0,0.6), 0 0 40px rgba(201,168,76,0.15);
            border: 1px solid rgba(201,168,76,0.3);
        }
        .hotel-header {
            display: flex; align-items: center; justify-content: center; gap: 0.85rem;
            margin-bottom: 1.25rem;
        }
        .logo-img {
            width: 46px; height: 46px;
            object-fit: contain;
            filter: drop-shadow(0 2px 8px rgba(201,168,76,0.4));
        }
        .hotel-name { font-family: 'Playfair Display', serif; font-size: 1.35rem; color: #C9A84C; font-weight: 700; }
        .hotel-sub { font-size: 0.72rem; color: #8899AA; text-transform: uppercase; letter-spacing: 2px; }

        .room-label {
            background: rgba(201,168,76,0.15);
            color: #E8D08D;
            font-family: 'Playfair Display', serif;
            font-size: 1.4rem; font-weight: 700;
            border-radius: 12px;
            padding: 0.4rem 1.5rem;
            display: inline-block;
            margin-bottom: 0.35rem;
            border: 1px solid rgba(201,168,76,0.3);
        }
        .room-type { color: #8899AA; font-size: 0.85rem; margin-bottom: 1.25rem; }

        .tabs-nav {
            display: flex;
            gap: 0.4rem;
            background: rgba(0,0,0,0.3);
            padding: 0.3rem;
            border-radius: 12px;
            margin-bottom: 1.25rem;
            border: 1px solid rgba(255,255,255,0.08);
        }
        .tab-btn {
            flex: 1;
            padding: 0.5rem 0.6rem;
            border-radius: 8px;
            border: none;
            background: transparent;
            color: #8899AA;
            font-size: 0.78rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        .tab-btn.active {
            background: linear-gradient(135deg, #C9A84C, #E0C06E);
            color: #0B1422;
            font-weight: 700;
            box-shadow: 0 4px 10px rgba(201,168,76,0.3);
        }

        .qr-wrapper {
            background: white;
            border: 3px solid #FFFFFF;
            border-radius: 18px;
            padding: 1rem;
            display: inline-block;
            margin: 0.5rem 0 1rem 0;
            box-shadow: 0 10px 25px rgba(0,0,0,0.4);
        }
        .qr-wrapper svg { display: block; width: 220px; height: 220px; }

        .instructions {
            background: rgba(0,0,0,0.35);
            border-radius: 12px;
            padding: 0.85rem 1rem;
            margin-top: 0.75rem;
            font-size: 0.82rem; color: #D1D5DB; line-height: 1.5;
            border: 1px solid rgba(255,255,255,0.08);
            text-align: left;
        }
        .url-text {
            font-family: monospace; font-size: 0.72rem; color: #E8D08D;
            word-break: break-all; margin-top: 0.5rem;
            background: rgba(0,0,0,0.4);
            padding: 0.4rem 0.6rem;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.5rem;
        }

        .actions {
            display: flex;
            gap: 0.75rem;
            margin-top: 1.25rem;
        }
        .btn-gold {
            flex: 1;
            background: linear-gradient(135deg, #C9A84C, #E0C06E);
            color: #0F1B2D; font-weight: 700;
            border: none; border-radius: 10px;
            padding: 0.7rem 1.25rem; font-size: 0.88rem;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
            transition: all 0.2s;
        }
        .btn-gold:hover { transform: translateY(-1px); box-shadow: 0 4px 15px rgba(201,168,76,0.4); color: #0F1B2D; }
        .btn-ghost {
            background: rgba(255,255,255,0.08);
            color: #FFFFFF; font-weight: 600;
            border: 1px solid rgba(255,255,255,0.15);
            border-radius: 10px;
            padding: 0.7rem 1rem; font-size: 0.88rem;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
            transition: all 0.2s;
        }
        .btn-ghost:hover { background: rgba(255,255,255,0.15); color: #FFFFFF; }
        .back-link { display: inline-block; margin-top: 1rem; color: #8899AA; font-size: 0.8rem; text-decoration: none; }
        .back-link:hover { color: #C9A84C; }

        @media print {
            body { background: white; color: black; }
            .tabs-nav, .actions, .back-link, .no-print { display: none !important; }
            .card { box-shadow: none; border: 1px solid #ccc; background: white; color: black; }
            .hotel-name { color: #0F1B2D; }
            .room-label { background: #0F1B2D; color: #C9A84C; }
            .url-text { color: #333; background: #eee; }
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="hotel-header">
            <img src="{{ asset('images/hotel/madison-logo.png') }}" alt="Madison Park Logo" class="logo-img">
            <div>
                <div class="hotel-name">Madison Park Hotel</div>
                <div class="hotel-sub">GuestLink Room QR Access</div>
            </div>
        </div>

        <div class="room-label">Room {{ $room->room_number }}</div>
        <div class="room-type">{{ $room->room_type }} — Floor {{ $room->floor }}</div>

        {{-- Tabs: Public vs Wi-Fi vs Free Internet --}}
        @php
            $defaultPub = ($preferredMode ?? 'local') === 'public' && $hasTunnel;
        @endphp
        <div class="tabs-nav no-print">
            <button type="button" class="tab-btn {{ $defaultPub ? '' : 'active' }}" id="tabLocalBtn" onclick="switchQrMode('local')">
                <i class="bi bi-wifi"></i> Local Wi-Fi ({{ $lanHost }})
            </button>
            <button type="button" class="tab-btn {{ $defaultPub ? 'active' : '' }}" id="tabPublicBtn" onclick="switchQrMode('public')">
                <i class="bi bi-globe"></i> Worldwide Cloudflare
            </button>
            <button type="button" class="tab-btn" id="tabWifiBtn" onclick="switchQrMode('wifi')">
                <i class="bi bi-router"></i> Free Wi-Fi QR
            </button>
        </div>

        {{-- Public QR Pane --}}
        <div id="panePublic" style="{{ $defaultPub ? '' : 'display:none;' }}">
            <div class="qr-wrapper">
                {!! $publicQr !!}
            </div>
            <div style="font-size:0.84rem; color:#CBD5E1; margin-top:0.25rem; font-weight:500;">
                Scan with your phone camera to access Room {{ $room->room_number }} Concierge &amp; Dining
            </div>
        </div>

        {{-- Local Wi-Fi QR Pane --}}
        <div id="paneLocal" style="{{ $defaultPub ? 'display:none;' : '' }}">
            <div class="qr-wrapper">
                {!! $localQr !!}
            </div>
            <div style="font-size:0.84rem; color:#CBD5E1; margin-top:0.25rem; font-weight:500;">
                Scan with your phone camera to access Room {{ $room->room_number }} Concierge &amp; Dining
            </div>
        </div>

        {{-- Free Hotel Wi-Fi Auto-Connect QR Pane (Tab 3 on screen, scan-only) --}}
        <div id="paneWifi" style="display:none;" data-wifi-payload="{{ $wifiPayload }}">
            <div class="qr-wrapper">
                {!! $wifiQr !!}
            </div>
            <div style="font-size:0.84rem; color:#CBD5E1; margin-top:0.25rem; font-weight:500;">
                Free Hotel Wi-Fi (No Voucher Needed) &mdash; Scan to auto-connect to <strong>{{ $wifiSsid }}</strong>
            </div>
        </div>

        <div class="actions no-print">
            <button class="btn-gold" onclick="window.print()">
                <i class="bi bi-printer"></i> Print QR
            </button>
            <a id="btnTestPortal" href="{{ $hasTunnel ? $publicUrl : $localUrl }}" target="_blank" class="btn-ghost">
                <i class="bi bi-box-arrow-up-right"></i> Open Portal
            </a>
        </div>

        <a href="{{ route('admin.rooms') }}" class="back-link no-print">
            ← Back to Rooms Management
        </a>
    </div>

    <script>
        function switchQrMode(mode) {
            const pPub = document.getElementById('panePublic');
            const pLoc = document.getElementById('paneLocal');
            const pWifi = document.getElementById('paneWifi');
            const bPub = document.getElementById('tabPublicBtn');
            const bLoc = document.getElementById('tabLocalBtn');
            const bWifi = document.getElementById('tabWifiBtn');
            const btnTest = document.getElementById('btnTestPortal');

            pPub.style.display = mode === 'public' ? 'block' : 'none';
            pLoc.style.display = mode === 'local' ? 'block' : 'none';
            if (pWifi) pWifi.style.display = mode === 'wifi' ? 'block' : 'none';

            bPub.classList.toggle('active', mode === 'public');
            bLoc.classList.toggle('active', mode === 'local');
            if (bWifi) bWifi.classList.toggle('active', mode === 'wifi');

            if (btnTest && mode !== 'wifi') {
                btnTest.href = mode === 'public' ? "{{ $publicUrl }}" : "{{ $localUrl }}";
            }
        }

        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                alert('Copied link to clipboard!\n' + text);
            }).catch(() => {
                prompt('Copy link:', text);
            });
        }
    </script>
</body>
</html>
