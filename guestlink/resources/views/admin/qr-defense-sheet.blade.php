<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print 3 Defense Room QR Cards | Madison Park Hotel</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', sans-serif;
            background: #070B12;
            color: #F6F1E7;
            min-height: 100vh;
            padding: 1.5rem 1rem 3rem;
        }
        .toolbar {
            max-width: 1120px;
            margin: 0 auto 1.5rem;
            background: #101828;
            border: 1px solid rgba(201,162,74,0.32);
            border-radius: 16px;
            padding: 1rem 1.25rem;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }
        .mode-switch {
            display: inline-flex;
            background: #070B12;
            border: 1px solid rgba(201,162,74,0.28);
            border-radius: 10px;
            padding: 3px;
            gap: 4px;
        }
        .mode-btn {
            border: none;
            background: transparent;
            color: #98A0AE;
            font-size: 0.78rem;
            font-weight: 600;
            padding: 0.45rem 0.85rem;
            border-radius: 8px;
            cursor: pointer;
        }
        .mode-btn.active {
            background: linear-gradient(135deg, #C9A24A, #E6CA7A);
            color: #070B12;
            font-weight: 700;
        }
        .btn-gold {
            background: linear-gradient(135deg, #C9A24A, #E6CA7A);
            color: #070B12;
            font-weight: 700;
            border: none;
            border-radius: 10px;
            padding: 0.55rem 1.15rem;
            font-size: 0.82rem;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
        }
        .btn-ghost {
            background: rgba(255,255,255,0.06);
            color: #E7E2D7;
            font-weight: 600;
            border: 1px solid rgba(255,255,255,0.16);
            border-radius: 10px;
            padding: 0.55rem 0.95rem;
            font-size: 0.8rem;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
        }
        .sheet-grid {
            max-width: 1120px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.1rem;
        }
        @media (max-width: 960px) {
            .sheet-grid { grid-template-columns: 1fr; max-width: 420px; }
        }
        .qr-card {
            background: linear-gradient(180deg, #131D31 0%, #0B1220 100%);
            border: 1px solid rgba(201,162,74,0.35);
            border-radius: 20px;
            padding: 1.35rem 1.15rem;
            text-align: center;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .hotel-brand {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.6rem;
            margin-bottom: 0.75rem;
        }
        .hotel-brand img { width: 34px; height: 34px; object-fit: contain; }
        .hotel-name { font-family: 'Playfair Display', serif; font-size: 1.05rem; color: #E6CA7A; font-weight: 700; }
        .hotel-sub { font-size: 0.64rem; color: #98A0AE; text-transform: uppercase; letter-spacing: 1.6px; }
        .room-pill {
            display: inline-block;
            background: rgba(201,162,74,0.14);
            border: 1px solid rgba(201,162,74,0.35);
            color: #F6F1E7;
            font-family: 'Playfair Display', serif;
            font-size: 1.25rem;
            font-weight: 700;
            padding: 0.28rem 1.1rem;
            border-radius: 10px;
            margin-bottom: 0.2rem;
        }
        .room-meta { font-size: 0.76rem; color: #98A0AE; margin-bottom: 0.85rem; }
        .qr-box {
            background: #FFFFFF;
            border-radius: 14px;
            padding: 0.7rem;
            display: inline-block;
            margin: 0 auto 0.65rem;
        }
        .qr-box svg { display: block; width: 185px; height: 185px; }
        .url-chip {
            font-family: monospace;
            font-size: 0.66rem;
            color: #E6CA7A;
            background: rgba(0,0,0,0.45);
            border-radius: 6px;
            padding: 0.35rem 0.5rem;
            word-break: break-all;
            margin-bottom: 0.8rem;
        }
        .wifi-strip {
            display: flex;
            align-items: center;
            gap: 0.65rem;
            text-align: left;
            background: rgba(201,162,74,0.08);
            border: 1px solid rgba(201,162,74,0.28);
            border-radius: 12px;
            padding: 0.55rem 0.65rem;
        }
        .wifi-mini-box {
            background: #FFFFFF;
            padding: 4px;
            border-radius: 6px;
            flex-shrink: 0;
            line-height: 0;
        }
        .wifi-mini-box svg { width: 68px !important; height: 68px !important; display: block; }
        @media print {
            @page { size: landscape; margin: 10mm; }
            body { background: #FFFFFF; color: #000000; padding: 0; }
            .no-print { display: none !important; }
            .sheet-grid { grid-template-columns: repeat(3, 1fr); gap: 8mm; max-width: 100%; }
            .qr-card {
                background: #FFFFFF;
                color: #000000;
                border: 1.5px solid #222222;
                page-break-inside: avoid;
            }
            .hotel-name { color: #0F1B2D; }
            .room-pill { background: #0F1B2D; color: #E6CA7A; }
            .room-meta { color: #444444; }
            .url-chip { background: #F1F5F9; color: #1E293B; border: 1px solid #CBD5E1; }
            .wifi-strip { background: #F8FAFC; border: 1px solid #94A3B8; color: #0F172A; }
        }
    </style>
</head>
<body>

    <div class="toolbar no-print">
        <div>
            <div style="font-family:'Playfair Display',serif; font-size:1.1rem; font-weight:700; color:#F6F1E7;">
                <i class="bi bi-qr-code-scan me-1" style="color:#C9A24A;"></i> 3-Room Capstone Defense QR Print Sheet
            </div>
            <div style="font-size:0.75rem; color:#98A0AE; margin-top:2px;">
                Option A Ready: Each card includes <strong>Step 1 Free Wi-Fi Auto-Connect QR ({{ $wifiSsid }})</strong> + <strong>Step 2 Room Portal QR</strong>.
            </div>
        </div>

        <div style="display:flex; align-items:center; gap:0.65rem; flex-wrap:wrap;">
            <div class="mode-switch">
                <button type="button" id="btnModeLocal" class="mode-btn {{ ($activeMode ?? 'local') === 'local' ? 'active' : '' }}" onclick="setSheetMode('local')">
                    <i class="bi bi-wifi"></i> Local Wi-Fi Network QR
                </button>
                <button type="button" id="btnModePublic" class="mode-btn {{ ($activeMode ?? 'local') === 'public' ? 'active' : '' }}" onclick="setSheetMode('public')">
                    <i class="bi bi-globe"></i> Public Cloudflare QR
                </button>
            </div>

            <button type="button" class="btn-gold" onclick="window.print()">
                <i class="bi bi-printer-fill"></i> Print 3 QR Cards
            </button>
            <a href="{{ route('admin.rooms') }}" class="btn-ghost">
                <i class="bi bi-arrow-left"></i> Back to Rooms
            </a>
        </div>
    </div>

    <div class="sheet-grid">
        @foreach($cards as $c)
        @php
            $r = $c['room'];
            $showLocal = ($activeMode ?? 'local') === 'local';
        @endphp
        <div class="qr-card">
            <div>
                <div class="hotel-brand">
                    <img src="{{ asset('images/hotel/madison-logo.png') }}" alt="Madison Park">
                    <div style="text-align:left;">
                        <div class="hotel-name">Madison Park Hotel</div>
                        <div class="hotel-sub">In-Room Digital Concierge</div>
                    </div>
                </div>

                <div class="room-pill">Room {{ $r->room_number }}</div>
                <div class="room-meta">{{ $r->room_type }} &middot; Floor {{ $r->floor }}</div>

                {{-- Local Wi-Fi QR (Scan-Only, No Raw IP/URL Text) --}}
                <div class="pane-local" style="{{ $showLocal ? '' : 'display:none;' }}" data-url="{{ $c['localUrl'] }}">
                    <div class="qr-box">{!! $c['localQr'] !!}</div>
                    <div style="font-size:0.78rem; font-weight:600; margin-top:0.25rem;">
                        Scan with phone camera to open Room {{ $r->room_number }} Guest Portal
                    </div>
                </div>

                {{-- Public Tunnel QR (Scan-Only, No Raw URL Text) --}}
                <div class="pane-public" style="{{ $showLocal ? 'display:none;' : '' }}" data-url="{{ $c['publicUrl'] }}">
                    <div class="qr-box">{!! $c['publicQr'] !!}</div>
                    <div style="font-size:0.78rem; font-weight:600; margin-top:0.25rem;">
                        Scan with phone camera to open Room {{ $r->room_number }} Guest Portal
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <script>
        function setSheetMode(mode) {
            document.querySelectorAll('.pane-local').forEach(el => el.style.display = mode === 'local' ? 'block' : 'none');
            document.querySelectorAll('.pane-public').forEach(el => el.style.display = mode === 'public' ? 'block' : 'none');
            document.getElementById('btnModeLocal').classList.toggle('active', mode === 'local');
            document.getElementById('btnModePublic').classList.toggle('active', mode === 'public');
        }
    </script>
</body>
</html>
