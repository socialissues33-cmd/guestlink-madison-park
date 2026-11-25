<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Portal – Madison Park Hotel | GuestLink</title>
    <script>(function(){try{var t=localStorage.getItem('guestlink_theme');if(t==='light'||t==='dark'){document.documentElement.setAttribute('data-theme',t);}}catch(e){}})();</script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,500;0,600;0,700;1,500&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="icon" type="image/png" href="{{ asset('images/hotel/madison-logo.png') }}?v={{ @filemtime(public_path('images/hotel/madison-logo.png')) }}">
    <link rel="stylesheet" href="{{ asset('css/madison-theme.css') }}?v={{ @filemtime(public_path('css/madison-theme.css')) }}">
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        html, body {
            margin: 0; padding: 0;
            min-height: 100dvh;
            background: #070B12;
            color: #F6F1E7;
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            -webkit-font-smoothing: antialiased;
        }

        /* ── Architectural Split Layout ─────────────────────────────── */
        .auth-shell {
            display: grid;
            grid-template-columns: 1.12fr 0.88fr;
            min-height: 100dvh;
        }

        /* Left Visual Showcase — Seamless Architectural Fade (Zero Hard Seam) */
        .auth-stage {
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 2.5rem 3rem;
            background: #070B12;
            overflow: hidden;
        }
        /* Dual Pixel-Locked Day/Night Layers (Zero Shift on Theme Toggle) */
        .stage-bg-layer {
            position: absolute;
            inset: 0;
            background-position: center;
            background-size: cover;
            background-repeat: no-repeat;
            pointer-events: none;
            will-change: opacity, filter;
        }
        .stage-bg-night {
            z-index: 0;
            opacity: 1;
            background-image:
                linear-gradient(90deg, rgba(7, 11, 18, 0.24) 0%, rgba(7, 11, 18, 0.34) 56%, rgba(7, 11, 18, 0.86) 85%, #070B12 100%),
                linear-gradient(180deg, rgba(7, 11, 18, 0.68) 0%, rgba(7, 11, 18, 0.32) 42%, rgba(7, 11, 18, 0.92) 100%),
                url('{{ asset("images/hotel/madison-entrance-night2-wide.jpg") }}');
        }
        .stage-bg-day {
            z-index: 1;
            opacity: 0;
            background-image:
                linear-gradient(90deg, rgba(15, 23, 42, 0.36) 0%, rgba(15, 23, 42, 0.12) 44%, rgba(15, 23, 42, 0.0) 72%),
                linear-gradient(180deg, rgba(255, 255, 255, 0.10) 0%, rgba(255, 255, 255, 0.0) 26%, rgba(15, 23, 42, 0.24) 60%, rgba(15, 23, 42, 0.62) 100%),
                url('{{ asset("images/hotel/madison-entrance-daylight-wide.jpg") }}?v={{ @filemtime(public_path("images/hotel/madison-entrance-daylight-wide.jpg")) }}');
            -webkit-mask-image: linear-gradient(90deg, #000 0%, #000 62%, rgba(0, 0, 0, 0.58) 78%, rgba(0, 0, 0, 0.14) 91%, transparent 100%);
            mask-image: linear-gradient(90deg, #000 0%, #000 62%, rgba(0, 0, 0, 0.58) 78%, rgba(0, 0, 0, 0.14) 91%, transparent 100%);
        }
        /* Fallback synchronized transition for browsers without View Transitions API */
        html.theme-switching-fallback .stage-bg-day,
        html.theme-switching-fallback .stage-bg-night {
            transition: opacity 0.55s cubic-bezier(0.22, 1, 0.36, 1);
        }
        html.theme-switching-fallback .auth-shell,
        html.theme-switching-fallback .auth-panel,
        html.theme-switching-fallback .auth-form-wrap,
        html.theme-switching-fallback .field-input,
        html.theme-switching-fallback .stage-brand-name,
        html.theme-switching-fallback .stage-brand-sub {
            transition: background-color 0.55s cubic-bezier(0.22, 1, 0.36, 1),
                        border-color 0.45s ease,
                        color 0.45s ease,
                        box-shadow 0.55s ease;
        }
        .auth-stage::after {
            content: none;
        }
        .stage-top, .stage-mid, .stage-bottom { position: relative; z-index: 2; }
        @media (prefers-reduced-motion: reduce) {
            .stage-bg-day, .stage-bg-night { transition: none !important; }
        }

        .stage-brand {
            display: inline-flex; align-items: center; gap: 0.85rem; text-decoration: none;
        }
        .stage-brand img {
            width: 44px; height: 44px; object-fit: contain;
        }
        .stage-brand-name {
            display: block; font-family: 'Playfair Display', Georgia, serif;
            font-size: 1.05rem; font-weight: 600; letter-spacing: 0.2em; text-transform: uppercase;
            color: #F6F1E7; line-height: 1.1;
        }
        .stage-brand-sub {
            display: block; margin-top: 4px; font-size: 0.68rem; letter-spacing: 0.28em;
            text-transform: uppercase; color: #C9A24A; font-weight: 600;
        }

        .stage-eyebrow {
            display: inline-flex; align-items: center; gap: 0.5rem;
            font-size: 0.72rem; letter-spacing: 0.12em; text-transform: uppercase;
            color: #D4AF56; font-weight: 600;
            margin-bottom: 1rem;
        }
        .stage-eyebrow::before {
            content: ''; width: 20px; height: 1px; background: #C9A24A;
        }
        .stage-headline {
            font-family: 'Playfair Display', Georgia, serif;
            font-size: clamp(2rem, 3.2vw, 2.85rem);
            font-weight: 600;
            line-height: 1.12;
            color: #F6F1E7;
            margin: 0 0 1rem;
            max-width: 16ch;
        }
        .stage-headline em {
            font-style: normal;
            color: #D4AF56;
            font-weight: 600;
        }
        .stage-copy {
            font-size: 0.92rem; line-height: 1.65; color: #C8C2B5;
            max-width: 44ch; margin: 0;
        }

        /* Clean Guest QR Notice on Left Panel */
        .stage-guest-dock {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            padding: 1rem 1.15rem;
            border-radius: 16px;
            background: rgba(11, 16, 27, 0.78);
            backdrop-filter: blur(18px);
            -webkit-backdrop-filter: blur(18px);
            border: 1px solid rgba(201, 162, 74, 0.24);
        }
        .dock-info { display: flex; align-items: center; gap: 0.85rem; min-width: 0; }
        .dock-icon {
            width: 42px; height: 42px; border-radius: 12px;
            background: rgba(201, 162, 74, 0.12);
            border: 1px solid rgba(201, 162, 74, 0.35);
            color: #E6CA7A;
            display: inline-flex; align-items: center; justify-content: center;
            font-size: 1.1rem; flex-shrink: 0;
        }
        .dock-title { font-size: 0.85rem; font-weight: 600; color: #F6F1E7; line-height: 1.25; }
        .dock-sub { font-size: 0.74rem; color: #98A0AE; margin-top: 2px; }
        .dock-btn {
            display: inline-flex; align-items: center; gap: 0.4rem;
            padding: 0.52rem 0.9rem; border-radius: 10px;
            font-size: 0.76rem; font-weight: 600; text-decoration: none;
            transition: all 0.2s ease; white-space: nowrap;
        }
        .dock-btn-ghost {
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(246, 241, 231, 0.14);
            color: #E7E2D7;
        }
        .dock-btn-ghost:hover { border-color: rgba(201, 162, 74, 0.5); color: #E6CA7A; }

        /* Right Form Workspace — calm obsidian neutral, zero radial color blobs */
        .auth-panel {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 2.5rem 2.25rem;
            background: #090C12;
            position: relative;
        }
        .auth-form-wrap {
            width: 100%;
            max-width: 480px;
            background: #101520;
            border: 1px solid rgba(246, 241, 231, 0.09);
            border-radius: 16px;
            padding: 2.5rem 2.4rem;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.45);
        }

        .mobile-brand {
            display: none;
            text-align: center;
            margin-bottom: 1.75rem;
        }
        .mobile-brand img { width: 58px; height: 58px; object-fit: contain; margin: 0 auto 0.65rem; display: block; }

        .panel-eyebrow {
            font-size: 0.72rem; font-weight: 600; letter-spacing: 0.1em;
            text-transform: uppercase; color: #C9A24A; margin-bottom: 0.5rem;
        }
        .panel-title {
            font-family: 'Playfair Display', Georgia, serif;
            font-size: 2rem; font-weight: 600; color: #F6F1E7;
            margin: 0 0 0.45rem; line-height: 1.18;
        }
        .panel-sub {
            font-size: 0.9rem; color: #8C94A4; margin: 0 0 1.9rem; line-height: 1.55;
        }

        .field-group { margin-bottom: 1.25rem; }
        .field-label {
            display: block;
            font-size: 0.72rem; font-weight: 600; letter-spacing: 0.12em;
            text-transform: uppercase; color: #A8A397; margin-bottom: 0.5rem;
        }
        .field-wrap {
            position: relative; display: flex; align-items: center;
        }
        .field-wrap i.field-ico {
            position: absolute; left: 1rem; color: #7D8696; font-size: 1rem;
            pointer-events: none; transition: color 0.2s;
        }
        .field-input {
            width: 100%;
            padding: 0.88rem 1.05rem 0.88rem 2.75rem;
            border-radius: 12px;
            background: #0B1220;
            border: 1px solid rgba(246, 241, 231, 0.12);
            color: #F6F1E7;
            font-size: 0.94rem; font-family: inherit;
            transition: border-color 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
        }
        .field-input::placeholder { color: #5E6778; }
        .field-input:focus {
            outline: none;
            background: #0E1729;
            border-color: #C9A24A;
            box-shadow: 0 0 0 3px rgba(201, 162, 74, 0.14);
        }
        /* Override ugly browser yellow autofill background */
        .field-input:-webkit-autofill,
        .field-input:-webkit-autofill:hover,
        .field-input:-webkit-autofill:focus,
        .field-input:-webkit-autofill:active {
            -webkit-box-shadow: 0 0 0 1000px #0B1220 inset !important;
            box-shadow: 0 0 0 1000px #0B1220 inset !important;
            -webkit-text-fill-color: #F6F1E7 !important;
            caret-color: #E6CA7A !important;
            border-color: rgba(201, 162, 74, 0.32) !important;
            transition: background-color 5000s ease-in-out 0s;
        }
        .field-wrap:focus-within i.field-ico { color: #E6CA7A; }

        .toggle-pw {
            position: absolute; right: 0.75rem;
            background: transparent; border: 0; color: #7D8696;
            padding: 0.25rem 0.35rem; cursor: pointer; font-size: 0.95rem;
        }
        .toggle-pw:hover { color: #E6CA7A; }

        .auth-row {
            display: flex; align-items: center; justify-content: space-between;
            margin: 0.25rem 0 1.4rem;
        }
        .remember-label {
            display: inline-flex; align-items: center; gap: 0.5rem;
            font-size: 0.8rem; color: #A8A397; cursor: pointer; user-select: none;
        }
        .remember-label input { accent-color: #C9A24A; width: 15px; height: 15px; cursor: pointer; }

        .btn-signin {
            width: 100%;
            min-height: 48px;
            border: 0;
            border-radius: 10px;
            background: #C9A24A;
            color: #141006;
            font-family: inherit;
            font-size: 0.9rem; font-weight: 600; letter-spacing: 0.01em;
            cursor: pointer;
            display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.24);
            transition: background-color 0.15s cubic-bezier(0.22, 1, 0.36, 1), transform 0.14s ease;
        }
        .btn-signin:hover { background: #D6B25E; }
        .btn-signin:active { transform: scale(0.985); }
        .btn-signin:disabled { opacity: 0.7; cursor: not-allowed; transform: none; }

        .alert-error {
            display: flex; align-items: flex-start; gap: 0.65rem;
            padding: 0.8rem 0.95rem; margin-bottom: 1.25rem;
            border-radius: 12px;
            background: rgba(244, 63, 94, 0.09);
            border: 1px solid rgba(244, 63, 94, 0.28);
            border-left: 2px solid #FB7185;
            color: #FECDD3; font-size: 0.82rem; line-height: 1.45;
        }

        .spin-sm {
            display: inline-block; width: 15px; height: 15px;
            border: 2px solid #140F04; border-top-color: transparent;
            border-radius: 50%; animation: spinAnim 0.7s linear infinite;
        }
        @keyframes spinAnim { to { transform: rotate(360deg); } }

        .login-foot-note {
            margin-top: 1.5rem;
            padding-top: 1.15rem;
            border-top: 1px solid rgba(246, 241, 231, 0.08);
            text-align: center;
            font-size: 0.74rem;
            color: #6E7788;
        }

        @media (max-width: 991.98px) {
            .auth-shell { grid-template-columns: 1fr; }
            .auth-stage { display: none; }
            .auth-panel {
                padding: 2rem 1.25rem;
                background:
                    linear-gradient(180deg, rgba(7, 11, 18, 0.88) 0%, rgba(7, 11, 18, 0.96) 100%),
                    url('{{ asset("images/hotel/madison-entrance-night2-wide.jpg") }}') center top/cover no-repeat;
            }
            .auth-form-wrap {
                background: rgba(14, 21, 35, 0.92);
                backdrop-filter: blur(20px);
                border: 1px solid rgba(246, 241, 231, 0.1);
                border-radius: 20px;
                padding: 1.75rem 1.35rem;
                max-width: 440px;
                box-shadow: 0 24px 60px rgba(0, 0, 0, 0.75);
            }
            .mobile-brand { display: block; }
            .panel-eyebrow, .panel-title, .panel-sub { text-align: center; }
            .field-input { font-size: 16px; }
        }
        .login-theme-float {
            position: absolute;
            top: 1.5rem;
            right: 1.75rem;
            z-index: 10;
        }
        /* Crisp Alabaster & Royal Gold Light Mode on Login (Full-Bleed Fade to Center) */
        html[data-theme="light"],
        html[data-theme="light"] body {
            background: #F4F5F7;
            color: #0F172A;
        }
        html[data-theme="light"] .auth-shell {
            background: #F4F5F7;
        }
        html[data-theme="light"] .auth-stage {
            background: transparent;
        }
        html[data-theme="light"] .stage-bg-night {
            opacity: 0;
        }
        html[data-theme="light"] .stage-bg-day {
            opacity: 1;
        }
        html[data-theme="light"] .auth-stage::after {
            background: none;
        }
        html[data-theme="light"] .stage-brand-name {
            color: #0F172A;
            text-shadow: 0 1px 10px rgba(255, 255, 255, 0.88);
        }
        html[data-theme="light"] .stage-brand-sub {
            color: #7A5408;
            font-weight: 700;
            text-shadow: 0 1px 8px rgba(255, 255, 255, 0.88);
        }
        html[data-theme="light"] .stage-eyebrow {
            color: #FCE69F;
            font-weight: 700;
            text-shadow: 0 1px 10px rgba(15, 23, 42, 0.92), 0 0 18px rgba(15, 23, 42, 0.72);
        }
        html[data-theme="light"] .stage-eyebrow::before {
            background: #E6C36A;
            height: 1.5px;
        }
        html[data-theme="light"] .stage-headline {
            color: #FFFFFF;
            text-shadow: 0 3px 18px rgba(15, 23, 42, 0.68);
        }
        html[data-theme="light"] .stage-headline em {
            color: #F7DF94;
            font-weight: 500;
        }
        html[data-theme="light"] .stage-copy {
            color: #F8FAFC;
            font-weight: 400;
            text-shadow: 0 2px 12px rgba(15, 23, 42, 0.72);
        }
        html[data-theme="light"] .stage-bottom div {
            color: #E2E8F0 !important;
            font-weight: 500;
            text-shadow: 0 1px 8px rgba(15, 23, 42, 0.75);
        }
        html[data-theme="light"] .auth-panel {
            background: transparent;
            padding: 2.5rem 2.25rem;
        }
        html[data-theme="light"] .login-theme-float {
            top: 1.5rem;
            right: 1.75rem;
        }
        html[data-theme="light"] .auth-form-wrap {
            background: #FFFFFF;
            border: 1px solid #D8D1C2;
            box-shadow: 0 20px 50px -16px rgba(15, 23, 42, 0.12), 0 4px 14px rgba(15, 23, 42, 0.05);
        }
        html[data-theme="light"] .panel-eyebrow { color: #7A5408; font-weight: 700; }
        html[data-theme="light"] .panel-title { color: #0F172A; font-weight: 700; }
        html[data-theme="light"] .panel-sub { color: #334155; font-weight: 400; }
        html[data-theme="light"] .field-label { color: #1E293B; font-weight: 700; }
        html[data-theme="light"] .field-wrap i.field-ico,
        html[data-theme="light"] .toggle-pw { color: #475569; }
        html[data-theme="light"] .field-wrap:focus-within i.field-ico,
        html[data-theme="light"] .toggle-pw:hover { color: #7A5408; }
        html[data-theme="light"] .field-input {
            background: #FFFFFF;
            border: 1.5px solid #CBD5E1;
            color: #0F172A;
            font-weight: 500;
        }
        html[data-theme="light"] .field-input::placeholder { color: #475569; opacity: 0.95; }
        html[data-theme="light"] .field-input:focus {
            background: #FFFFFF;
            border-color: #B5892E;
            box-shadow: 0 0 0 3px rgba(181, 137, 46, 0.22);
        }
        html[data-theme="light"] .field-input:-webkit-autofill,
        html[data-theme="light"] .field-input:-webkit-autofill:hover,
        html[data-theme="light"] .field-input:-webkit-autofill:focus {
            -webkit-box-shadow: 0 0 0 1000px #FFFFFF inset !important;
            box-shadow: 0 0 0 1000px #FFFFFF inset !important;
            -webkit-text-fill-color: #0F172A !important;
            caret-color: #7A5408 !important;
        }
        html[data-theme="light"] .remember-label { color: #1E293B; font-weight: 500; }
        html[data-theme="light"] .btn-signin { color: #0F172A; font-weight: 700; }
        html[data-theme="light"] .alert-error {
            background: #FEF2F2;
            border-color: #FCA5A5;
            border-left-color: #DC2626;
            color: #991B1B;
            font-weight: 500;
        }
        html[data-theme="light"] .login-foot-note {
            border-top-color: #E2E8F0;
            color: #475569;
            font-weight: 500;
        }
        html[data-theme="light"] .mobile-brand .stage-brand-name { color: #0F172A; }
        html[data-theme="light"] .mobile-brand .stage-brand-sub { color: #7A5408; }
        @media (max-width: 991.98px) {
            html[data-theme="light"] .auth-shell {
                padding: 0;
                gap: 0;
            }
            html[data-theme="light"] .auth-panel {
                background: #F4F5F7;
                padding: 2rem 1.25rem;
            }
            html[data-theme="light"] .login-theme-float {
                top: 1.25rem;
                right: 1.25rem;
            }
            html[data-theme="light"] .auth-form-wrap {
                background: rgba(255, 255, 255, 0.97);
                border-color: #D8D1C2;
                box-shadow: 0 20px 48px rgba(15, 23, 42, 0.12);
            }
            .login-theme-float { top: 1rem; right: 1rem; }
        }
    </style>
</head>
<body>
<div class="auth-shell">

    {{-- ── Left Editorial Hospitality Showcase ──────────────────────────── --}}
    <aside class="auth-stage">
        <div class="stage-bg-layer stage-bg-night" aria-hidden="true"></div>
        <div class="stage-bg-layer stage-bg-day" aria-hidden="true"></div>
        <div class="stage-top">
            <a href="{{ url('/') }}" class="stage-brand">
                <img src="{{ asset('images/hotel/madison-logo.png') }}?v={{ @filemtime(public_path('images/hotel/madison-logo.png')) }}" alt="Madison Park Hotel">
                <span>
                    <span class="stage-brand-name">Madison Park</span>
                    <span class="stage-brand-sub">Hotel &amp; Residences</span>
                </span>
            </a>
        </div>

        <div class="stage-mid">
            <div class="stage-eyebrow">GuestLink Operations Suite</div>
            <h1 class="stage-headline">
                Quiet hospitality, <em>orchestrated</em> in real time.
            </h1>
            <p class="stage-copy">
                Unified front desk triage, kitchen order dispatch, engineering photo diagnostics, and executive reporting &mdash; built for Madison Park staff.
            </p>
        </div>

        <div class="stage-bottom">
            <div style="font-size:0.74rem; color:#7E8899; letter-spacing:0.06em;">
                &copy; {{ date('Y') }} Madison Park Hotel &amp; Residences &middot; GuestLink Operations
            </div>
        </div>
    </aside>

    {{-- ── Right Clean Staff Sign-In Workspace ──────────────────────────── --}}
    <main class="auth-panel">
        <button type="button" id="btnLoginThemeToggle" onclick="toggleGuestLinkTheme()" class="mp-theme-toggle login-theme-float" aria-label="Switch light or dark theme" title="Switch Light / Dark Mode">
            <span class="icon-sun"><i class="bi bi-sun-fill"></i></span>
            <span class="icon-moon"><i class="bi bi-moon-stars-fill"></i></span>
            <span id="loginThemeLabel">Light</span>
        </button>

        <div class="auth-form-wrap">

            {{-- Mobile Crest Lockup --}}
            <div class="mobile-brand">
                <img src="{{ asset('images/hotel/madison-logo.png') }}?v={{ @filemtime(public_path('images/hotel/madison-logo.png')) }}" alt="Madison Park Hotel">
                <span class="stage-brand-name" style="font-size:0.95rem;">Madison Park</span>
                <span class="stage-brand-sub" style="font-size:0.62rem;">GuestLink Staff Portal</span>
            </div>

            <div class="panel-eyebrow">Staff Portal</div>
            <h2 class="panel-title">Welcome back</h2>
            <p class="panel-sub">Sign in with your department credentials to access your shift workspace.</p>

            @if(session('error'))
            <div class="alert-error">
                <i class="bi bi-exclamation-circle" style="color:#FB7185;flex-shrink:0;margin-top:2px;"></i>
                <span>{{ session('error') }}</span>
            </div>
            @endif
            @if($errors->any())
            <div class="alert-error">
                <i class="bi bi-exclamation-circle" style="color:#FB7185;flex-shrink:0;margin-top:2px;"></i>
                <span>{{ $errors->first() }}</span>
            </div>
            @endif

            <form method="POST" action="{{ url('/login') }}" id="loginForm" autocomplete="off">
                @csrf
                <div class="field-group">
                    <label for="emailInput" class="field-label">Username or Email</label>
                    <div class="field-wrap">
                        <i class="bi bi-person field-ico"></i>
                        <input type="text" name="email" id="emailInput" class="field-input"
                               placeholder="Enter username or email" value="{{ old('email') }}"
                               autocomplete="off" readonly onfocus="this.removeAttribute('readonly');" required>
                    </div>
                </div>

                <div class="field-group">
                    <label for="passwordInput" class="field-label">Password</label>
                    <div class="field-wrap">
                        <i class="bi bi-lock field-ico"></i>
                        <input type="password" name="password" id="passwordInput" class="field-input"
                               style="padding-right:2.6rem;" placeholder="Enter your password"
                               autocomplete="new-password" readonly onfocus="this.removeAttribute('readonly');" required>
                        <button type="button" class="toggle-pw" onclick="togglePw()" aria-label="Show or hide password">
                            <i class="bi bi-eye" id="pwEye"></i>
                        </button>
                    </div>
                </div>

                <div class="auth-row">
                    <label class="remember-label">
                        <input type="checkbox" name="remember" checked>
                        <span>Keep me signed in</span>
                    </label>
                </div>

                <button type="submit" class="btn-signin" id="submitBtn">
                    <span>Sign In to Workspace</span>
                    <i class="bi bi-arrow-right"></i>
                </button>
            </form>

            <div class="login-foot-note">
                Authorized Madison Park Hotel personnel only.
            </div>
        </div>
    </main>

</div>

<script>
let isSubmitting = false;

function togglePw() {
    const inp = document.getElementById('passwordInput');
    const eye = document.getElementById('pwEye');
    if (!inp) return;
    const isHidden = inp.type === 'password';
    inp.type = isHidden ? 'text' : 'password';
    if (eye) eye.className = isHidden ? 'bi bi-eye-slash' : 'bi bi-eye';
}

document.getElementById('loginForm').addEventListener('submit', function(e) {
    if (isSubmitting) {
        e.preventDefault();
        return false;
    }
    isSubmitting = true;
    const submitBtn = document.getElementById('submitBtn');
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spin-sm"></span><span>Signing in...</span>';
    }
});

function syncLoginThemeLabel() {
    const lbl = document.getElementById('loginThemeLabel');
    if (!lbl) return;
    const isLight = document.documentElement.getAttribute('data-theme') === 'light';
    lbl.textContent = isLight ? 'Dark' : 'Light';
}
let themeSwitchTimer = null;
let activeThemeVt = null;
function toggleGuestLinkTheme(ev) {
    const root = document.documentElement;
    const btn = document.getElementById('btnLoginThemeToggle');
    const next = root.getAttribute('data-theme') === 'light' ? 'dark' : 'light';
    const goingDark = next === 'dark';

    const applyTheme = () => {
        root.setAttribute('data-theme', next);
        try { localStorage.setItem('guestlink_theme', next); } catch (e) {}
        syncLoginThemeLabel();
    };

    if (btn) {
        btn.classList.remove('is-popping');
        void btn.offsetWidth;
        btn.classList.add('is-popping');
    }

    const reducedMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (!reducedMotion && typeof document.startViewTransition === 'function') {
        if (activeThemeVt && typeof activeThemeVt.skipTransition === 'function') {
            try { activeThemeVt.skipTransition(); } catch (e) {}
        }
        const rect = btn ? btn.getBoundingClientRect() : null;
        const cx = rect ? rect.left + rect.width / 2 : window.innerWidth - 60;
        const cy = rect ? rect.top + rect.height / 2 : 36;
        const maxR = Math.ceil(Math.hypot(
            Math.max(cx, window.innerWidth - cx),
            Math.max(cy, window.innerHeight - cy)
        ));

        root.classList.toggle('theme-to-dark', goingDark);
        const vt = document.startViewTransition(() => applyTheme());
        activeThemeVt = vt;
        vt.ready.then(() => {
            if (activeThemeVt !== vt) return;
            const clipPath = goingDark
                ? [`circle(${maxR}px at ${cx}px ${cy}px)`, `circle(0px at ${cx}px ${cy}px)`]
                : [`circle(0px at ${cx}px ${cy}px)`, `circle(${maxR}px at ${cx}px ${cy}px)`];
            document.documentElement.animate(
                { clipPath },
                {
                    duration: 480,
                    easing: 'cubic-bezier(0.25, 0.85, 0.3, 1)',
                    fill: 'forwards',
                    pseudoElement: goingDark ? '::view-transition-old(root)' : '::view-transition-new(root)'
                }
            );
        }).catch(() => {});
        vt.finished.finally(() => {
            if (activeThemeVt === vt) {
                root.classList.remove('theme-to-dark');
                activeThemeVt = null;
            }
        });
        return;
    }

    root.classList.add('theme-switching-fallback');
    applyTheme();
    clearTimeout(themeSwitchTimer);
    themeSwitchTimer = setTimeout(() => root.classList.remove('theme-switching-fallback'), 480);
}
syncLoginThemeLabel();

document.addEventListener('DOMContentLoaded', function() {
    const emailEl = document.getElementById('emailInput');
    const pwEl = document.getElementById('passwordInput');
    const hasOldEmail = @json(!empty(old('email')));
    setTimeout(() => {
        if (emailEl && !hasOldEmail) emailEl.value = '';
        if (pwEl) pwEl.value = '';
    }, 60);
});

window.addEventListener('pageshow', function(event) {
    if (event.persisted) window.location.reload();
});
</script>
</body>
</html>
