@php
    $__prefUser = auth()->user();
    $__savedTheme = in_array($__prefUser?->theme_preference, ['light', 'dark'], true)
        ? $__prefUser->theme_preference
        : 'dark';
    $__savedSidebarCollapsed = (bool) ($__prefUser?->sidebar_collapsed ?? false);
    $__savedBotEnabled = (bool) ($__prefUser?->bot_auto_reply_enabled ?? true);
@endphp
<!DOCTYPE html>
<html lang="en" data-theme="{{ $__savedTheme }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Dashboard') – GuestLink Staff</title>
    <script>(function(){try{var acctTheme=@json($__savedTheme);var acctCol=@json($__savedSidebarCollapsed);document.documentElement.setAttribute('data-theme',acctTheme);localStorage.setItem('guestlink_theme',acctTheme);localStorage.setItem('guestlink_sidebar_collapsed',acctCol?'1':'0');if(acctCol&&window.innerWidth>992){document.documentElement.classList.add('sidebar-collapsed');}}catch(e){}})();</script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="icon" type="image/png" href="{{ asset('images/hotel/madison-logo.png') }}?v={{ @filemtime(public_path('images/hotel/madison-logo.png')) }}">
    <style>
        :root {
            --navy: #0F1B2D;
            --navy-mid: #162236;
            --navy-light: #1E3050;
            --gold: #C9A84C;
            --gold-light: #E0C06E;
            --gold-pale: rgba(201,168,76,0.12);
            --border: rgba(201,168,76,0.22);
            --border-hover: rgba(201,168,76,0.45);
            --muted: #8E9EAF;
            --sidebar-w: 240px;
        }
        * { box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: var(--navy); color: #fff; margin: 0; display: flex; min-height: 100vh; overflow-x: clip; }

        /* ── Sidebar ───────────────────────────────────────────────────── */
        .sidebar {
            width: var(--sidebar-w);
            background: linear-gradient(180deg, #0B1320 0%, #070D16 100%);
            border-right: 1px solid var(--border);
            display: flex; flex-direction: column;
            position: fixed; top: 0; left: 0; bottom: 0;
            height: 100vh;
            height: 100dvh;
            z-index: 1060;
            transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            box-shadow: 4px 0 24px rgba(0, 0, 0, 0.4);
            overflow: hidden;
        }
        .sidebar-brand {
            padding: 1.15rem 1.25rem 1.05rem;
            border-bottom: 1px solid var(--border);
            display: flex; align-items: center; gap: 0.85rem;
            background: rgba(201,168,76,0.05);
            flex-shrink: 0;
        }
        .sidebar-body {
            flex: 1 1 auto;
            overflow-y: auto;
            overflow-x: hidden;
            -webkit-overflow-scrolling: touch;
            padding-bottom: 0.5rem;
        }
        .brand-logo-img {
            width: 40px; height: 40px;
            object-fit: contain;
            flex-shrink: 0;
            filter: drop-shadow(0 2px 8px rgba(201,168,76,0.4));
            transition: transform 0.25s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .sidebar-brand:hover .brand-logo-img {
            transform: scale(1.08) rotate(3deg);
        }
        .brand-text .brand-name { 
            font-family: 'Playfair Display', serif; 
            font-size: 1.12rem; 
            font-weight: 700; 
            background: linear-gradient(135deg, #FFFFFF 0%, #F5E6B8 50%, #C9A84C 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            line-height: 1.2; 
        }
        .brand-text .brand-sub { 
            font-size: 0.64rem; 
            color: var(--gold-light); 
            text-transform: uppercase; 
            letter-spacing: 1.8px;
            opacity: 0.85; 
            margin-top: 1px;
        }

        /* ── Nav Items ─────────────────────────────────────────────────── */
        .nav-section { padding: 0.9rem 0.85rem 0.4rem; }
        .nav-label { 
            font-size: 0.65rem; 
            text-transform: uppercase; 
            letter-spacing: 2px; 
            color: #64748B; 
            padding: 0 0.5rem; 
            margin-bottom: 0.5rem; 
            font-weight: 700; 
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .nav-item { 
            display: flex; 
            align-items: center; 
            gap: 0.75rem; 
            padding: 0.65rem 0.85rem; 
            border-radius: 12px; 
            text-decoration: none; 
            color: #94A3B8; 
            font-size: 0.84rem; 
            transition: all 0.22s cubic-bezier(0.16, 1, 0.3, 1); 
            margin-bottom: 4px; 
            font-weight: 500; 
            position: relative;
        }
        .nav-item:hover { 
            background: rgba(201,168,76,0.1); 
            color: #FFF; 
            transform: translateX(4px); 
        }
        .nav-item.active { 
            background: linear-gradient(90deg, rgba(201,168,76,0.2) 0%, rgba(201,168,76,0.06) 100%); 
            color: #FFF; 
            border-left: 3px solid var(--gold); 
            border-radius: 0 12px 12px 0;
            font-weight: 600; 
            box-shadow: 0 2px 12px rgba(201,168,76,0.15);
        }
        .nav-item.active i { color: var(--gold); }
        .nav-item i { font-size: 1.05rem; width: 20px; text-align: center; color: #64748B; transition: color 0.2s; }
        .nav-item:hover i { color: var(--gold-light); }
        .nav-badge { 
            margin-left: auto; 
            background: #EF4444; 
            color: #fff; 
            font-size: 0.65rem; 
            padding: 0.15rem 0.5rem; 
            border-radius: 20px; 
            font-weight: 700; 
            box-shadow: 0 0 10px rgba(239,68,68,0.55); 
        }
        .nav-badge.badge-amber {
            background: #F59E0B;
            box-shadow: 0 0 10px rgba(245,158,11,0.5);
        }

        /* ── Sidebar Footer (Always Pinned & Accessible) ──────────────── */
        .sidebar-footer {
            margin-top: auto;
            flex-shrink: 0;
            padding: 0.95rem 1rem;
            border-top: 1px solid var(--border);
            background: rgba(7, 13, 22, 0.97);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            position: relative;
            z-index: 10;
        }
        .user-card { 
            display: flex; 
            align-items: center; 
            gap: 0.75rem; 
            padding: 0.4rem;
            border-radius: 12px;
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.06);
        }
        .user-avatar {
            width: 38px; height: 38px; border-radius: 50%;
            background: linear-gradient(135deg, rgba(201,168,76,0.35), rgba(201,168,76,0.1));
            border: 1.5px solid var(--gold);
            display: flex; align-items: center; justify-content: center;
            font-size: 0.95rem; color: #FFF; font-weight: 700;
            box-shadow: 0 2px 8px rgba(0,0,0,0.3);
            flex-shrink: 0;
        }
        .user-name { font-size: 0.84rem; font-weight: 600; line-height: 1.2; color: #fff; }
        .user-role { font-size: 0.7rem; color: var(--gold-light); font-weight: 500; display: flex; align-items: center; gap: 4px; margin-top: 1px; }
        .btn-signout {
            background: rgba(239, 68, 68, 0.12);
            border: 1px solid rgba(239, 68, 68, 0.35);
            color: #FCA5A5;
            border-radius: 10px;
            font-size: 0.82rem;
            font-weight: 600;
            padding: 0.55rem 0.85rem;
            transition: all 0.2s ease;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            cursor: pointer;
            min-height: 42px;
        }
        .btn-signout:hover {
            background: rgba(239, 68, 68, 0.25);
            border-color: #EF4444;
            color: #FFFFFF;
            transform: translateY(-1px);
            box-shadow: 0 4px 14px rgba(239,68,68,0.3);
        }
        .topbar-user-btn {
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(201,168,76,0.3);
            border-radius: 20px;
            padding: 0.3rem 0.65rem;
            color: #fff;
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            transition: all 0.2s ease;
            cursor: pointer;
        }
        .topbar-user-btn:hover {
            border-color: var(--gold);
            background: rgba(201,168,76,0.15);
            color: #fff;
        }
        .topbar-user-avatar {
            width: 24px; height: 24px; border-radius: 50%;
            background: linear-gradient(135deg, var(--gold), #E8D08D);
            color: #0B1422;
            font-weight: 700;
            font-size: 0.75rem;
            display: flex; align-items: center; justify-content: center;
        }

        /* ── Main Content ──────────────────────────────────────────────── */
        .main-content {
            margin-left: var(--sidebar-w);
            flex: 1; display: flex; flex-direction: column;
            min-height: 100vh;
            min-width: 0;
            max-width: 100%;
            overflow-x: clip;
            background: #0B1422;
            transition: margin-left 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .topbar {
            background: rgba(13, 22, 36, 0.85);
            border-bottom: 1px solid var(--border);
            padding: 0.75rem 1.5rem;
            display: flex; align-items: center; justify-content: space-between;
            position: sticky; top: 0; z-index: 50;
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            box-shadow: 0 4px 20px rgba(0,0,0,0.25);
            flex-wrap: wrap;
            gap: 0.65rem;
        }
        .topbar-brand-title {
            display: flex; flex-direction: column; gap: 2px;
        }
        .topbar-subtitle {
            font-size: 0.68rem;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: var(--gold-light);
            font-weight: 600;
        }
        .topbar-title { font-family: 'Playfair Display', serif; font-size: 1.25rem; font-weight: 700; color: #fff; margin: 0; line-height: 1.2; }
        .page-content { 
            padding: clamp(0.85rem, 1.8vw, 1.5rem); 
            flex: 1; 
            min-width: 0;
            max-width: 100%;
            overflow-x: hidden;
        }

        /* ── Topbar Widgets ────────────────────────────────────────────── */
        .topbar-widget {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 20px;
            padding: 0.35rem 0.85rem;
            font-size: 0.76rem;
            color: #CBD5E1;
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            transition: all 0.2s;
        }
        .topbar-widget:hover {
            border-color: var(--border-hover);
            background: rgba(255,255,255,0.08);
        }
        .widget-date {
            color: #E2E8F0;
            font-weight: 500;
        }
        .widget-clock {
            color: var(--gold-light);
            font-family: 'Inter', monospace;
            font-weight: 600;
            letter-spacing: 0.5px;
        }
        .duty-pill {
            background: rgba(34, 197, 94, 0.1);
            border: 1px solid rgba(34, 197, 94, 0.3);
            border-radius: 20px;
            padding: 0.3rem 0.8rem;
            font-size: 0.74rem;
            color: #86EFAC;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
        }
        .duty-dot {
            width: 7px; height: 7px;
            border-radius: 50%;
            background: #22C55E;
            box-shadow: 0 0 8px #22C55E;
            animation: pulseDot 2s infinite;
        }
        .ai-status-pill {
            background: rgba(201,168,76, 0.12);
            border: 1px solid rgba(201,168,76, 0.3);
            border-radius: 20px;
            padding: 0.3rem 0.75rem;
            font-size: 0.74rem;
            color: #E6CB7E;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
        }
        .btn-guest-view {
            background: linear-gradient(135deg, rgba(201,168,76,0.25), rgba(201,168,76,0.1));
            border: 1px solid rgba(201,168,76,0.4);
            color: var(--gold-light);
            border-radius: 20px;
            padding: 0.35rem 0.85rem;
            font-size: 0.76rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            transition: all 0.25s ease;
        }
        .btn-guest-view:hover {
            background: var(--gold);
            color: #0B1422;
            border-color: var(--gold);
            transform: translateY(-1px);
            box-shadow: 0 4px 14px rgba(201,168,76,0.35);
        }
        .topbar-notif-btn {
            position: relative;
            height: 38px;
            padding: 0 12px;
            border-radius: 12px;
            background: rgba(201, 168, 76, 0.12);
            border: 1px solid rgba(201, 168, 76, 0.45);
            color: #E8D08D;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            font-size: 0.95rem;
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
            cursor: pointer;
            text-decoration: none;
        }
        .topbar-notif-btn:hover, .topbar-notif-btn:focus, .topbar-notif-btn.show {
            background: rgba(201, 168, 76, 0.25);
            border-color: #F5D77F;
            color: #fff;
            box-shadow: 0 0 16px rgba(201, 168, 76, 0.45);
        }
        .topbar-notif-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: linear-gradient(135deg, #ef4444 0%, #b91c1c 100%);
            color: #fff;
            font-size: 0.65rem;
            font-weight: 800;
            min-width: 20px;
            height: 20px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0 5px;
            border: 2px solid #0B1422;
            box-shadow: 0 0 12px rgba(239, 68, 68, 0.85);
            animation: badgePulse 2s infinite;
        }
        .topbar-notif-item {
            display: block;
            padding: 0.8rem 1rem;
            border-bottom: 1px solid rgba(246, 241, 231, 0.06);
            text-decoration: none;
            cursor: pointer;
            transition: background 0.16s ease;
        }
        .topbar-notif-item:hover {
            background: rgba(201, 162, 74, 0.06);
        }
        .topbar-notif-item.unread {
            background: rgba(201, 162, 74, 0.055);
            border-left: 2px solid var(--mp-gold, #C9A24A);
        }
        .topbar-notif-item:last-child {
            border-bottom: none;
        }
        .notif-icon-box {
            width: 34px;
            height: 34px;
            min-width: 34px;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
            flex-shrink: 0;
            margin-top: 1px;
        }
        .notif-filter-tab {
            flex: 1 1 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.3rem;
            padding: 0.34rem 0.5rem;
            border-radius: 7px;
            font-size: 0.72rem;
            font-weight: 600;
            color: #8C94A4;
            background: transparent;
            border: 1px solid transparent;
            white-space: nowrap;
            cursor: pointer;
            transition: all 0.15s ease;
        }
        .notif-filter-tab:hover {
            color: #F6F1E7;
            background: rgba(255, 255, 255, 0.05);
        }
        .notif-filter-tab.active {
            background: rgba(201, 162, 74, 0.16);
            border-color: rgba(201, 162, 74, 0.42);
            color: #E6CA7A;
        }
        .stat-card {
            background: rgba(255,255,255,0.04);
            border: 1px solid var(--border);
            border-radius: 16px; padding: 1.25rem;
            transition: all 0.25s ease;
            position: relative;
            overflow: hidden;
        }
        .stat-card:hover {
            border-color: var(--gold);
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(201,168,76,0.15);
            background: rgba(201,168,76,0.06);
        }
        .stat-value { font-size: 2.1rem; font-weight: 700; line-height: 1; margin: 0.35rem 0; }
        .stat-label { color: var(--muted); font-size: 0.78rem; font-weight: 500; }
        .stat-icon  { font-size: 1.6rem; opacity: 0.85; }

        /* ── Data Table ────────────────────────────────────────────────── */
        .data-table { width: 100%; border-collapse: collapse; }
        .data-table th { color: #94A3B8; font-size: 0.73rem; text-transform: uppercase; letter-spacing: 1px; padding: 0.85rem 1rem; text-align: left; border-bottom: 1px solid var(--border); font-weight: 600; background: rgba(255,255,255,0.02); }
        .data-table td { padding: 0.9rem 1rem; border-bottom: 1px solid rgba(255,255,255,0.05); font-size: 0.84rem; vertical-align: middle; }
        .data-table tr:hover td { background: rgba(201,168,76,0.04); }
        .table-card { background: rgba(255,255,255,0.03); border: 1px solid var(--border); border-radius: 16px; overflow: hidden; box-shadow: 0 8px 30px rgba(0,0,0,0.3); }

        /* ── Badges ────────────────────────────────────────────────────── */
        .badge { border-radius: 20px !important; font-size: 0.72rem !important; letter-spacing: 0.3px; padding: 0.35rem 0.65rem !important; font-weight: 600; }

        /* ── Form Elements ─────────────────────────────────────────────── */
        .form-control, .form-select {
            background: rgba(255,255,255,0.06) !important;
            border: 1px solid rgba(255,255,255,0.15) !important;
            color: #fff !important; border-radius: 10px;
        }
        .form-control:focus, .form-select:focus {
            border-color: var(--gold) !important;
            box-shadow: 0 0 0 3px rgba(201,168,76,0.15) !important;
        }
        .form-control::placeholder { color: var(--muted) !important; }
        .form-select option { background: #132034; color: #fff; }
        .form-label { color: #CBD5E1; font-size: 0.82rem; font-weight: 500; }

        /* ── Buttons ───────────────────────────────────────────────────── */
        .btn-gold { background: linear-gradient(135deg, var(--gold), var(--gold-light)); color: var(--navy); font-weight: 600; border: none; border-radius: 10px; transition: all 0.2s; box-shadow: 0 4px 12px rgba(201,168,76,0.25); }
        .btn-gold:hover { transform: translateY(-1px); color: var(--navy); box-shadow: 0 6px 18px rgba(201,168,76,0.4); }
        .btn-ghost { background: rgba(255,255,255,0.05); border: 1px solid var(--border); color: #CBD5E1; border-radius: 10px; transition: all 0.2s; font-size: 0.82rem; }
        .btn-ghost:hover { border-color: var(--gold); color: var(--gold-light); background: var(--gold-pale); }

        /* ── Segmented Filter Controls ─────────────────────────────────── */
        .filter-segmented {
            background: rgba(11, 20, 34, 0.85);
            border: 1px solid rgba(201, 168, 76, 0.3);
            border-radius: 12px;
            padding: 4px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.35);
        }
        .filter-segment-btn {
            padding: 0.38rem 0.85rem;
            font-size: 0.78rem;
            font-weight: 500;
            color: #94A3B8;
            text-decoration: none;
            border-radius: 8px;
            border: 1px solid transparent;
            background: transparent;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            white-space: nowrap;
            line-height: 1.35;
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .filter-segment-btn:hover {
            color: #FFFFFF;
            background: rgba(255, 255, 255, 0.08);
            border-color: rgba(255, 255, 255, 0.12);
        }
        .filter-segment-btn.active {
            background: rgba(201, 168, 76, 0.22);
            border-color: rgba(201, 168, 76, 0.6);
            color: #F5E6B8;
            font-weight: 600;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.25);
        }
        .filter-segment-btn.active i {
            color: #E8D08D !important;
        }
        .text-gold {
            color: var(--gold) !important;
        }

        /* ── Priority Attention Deck & Status Badges ─────────────────── */
        .deck-formal {
            background: linear-gradient(145deg, rgba(17, 24, 39, 0.95) 0%, rgba(10, 15, 26, 0.98) 100%) !important;
            border: 1px solid rgba(244, 63, 94, 0.35) !important;
            border-left: 4px solid #f43f5e !important;
            border-radius: 14px !important;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.4), 0 0 15px rgba(244, 63, 94, 0.1) !important;
        }
        .deck-formal-title {
            font-family: 'Playfair Display', Georgia, serif !important;
            font-size: 1.05rem !important;
            font-weight: 700 !important;
            color: #FFFFFF !important;
            display: inline-flex !important;
            align-items: center !important;
            gap: 0.35rem !important;
        }
        .deck-item-formal {
            background: rgba(255, 255, 255, 0.035) !important;
            border: 1px solid rgba(255, 255, 255, 0.08) !important;
            border-radius: 10px !important;
            padding: 0.85rem 1rem !important;
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1) !important;
        }
        .deck-item-formal:hover {
            background: rgba(255, 255, 255, 0.065) !important;
            border-color: rgba(201, 168, 76, 0.4) !important;
            transform: translateY(-1px) !important;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3) !important;
        }
        .deck-item-icon {
            width: 38px !important;
            height: 38px !important;
            min-width: 38px !important;
            border-radius: 10px !important;
            background: rgba(201, 168, 76, 0.12) !important;
            border: 1px solid rgba(201, 168, 76, 0.25) !important;
            color: #E8D08D !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            font-size: 1.05rem !important;
        }
        .badge-status-urgent {
            background: rgba(244, 63, 94, 0.18) !important;
            color: #fda4af !important;
            border: 1px solid rgba(244, 63, 94, 0.45) !important;
            font-weight: 600 !important;
            padding: 0.25rem 0.6rem !important;
            border-radius: 6px !important;
            font-size: 0.72rem !important;
            letter-spacing: 0.02em !important;
            display: inline-flex !important;
            align-items: center !important;
        }
        .badge-status-high {
            background: rgba(245, 158, 11, 0.18) !important;
            color: #fde68a !important;
            border: 1px solid rgba(245, 158, 11, 0.45) !important;
            font-weight: 600 !important;
            padding: 0.25rem 0.6rem !important;
            border-radius: 6px !important;
            font-size: 0.72rem !important;
            letter-spacing: 0.02em !important;
            display: inline-flex !important;
            align-items: center !important;
        }
        .badge-status-dept {
            background: rgba(56, 189, 248, 0.14) !important;
            color: #bae6fd !important;
            border: 1px solid rgba(56, 189, 248, 0.35) !important;
            font-weight: 500 !important;
            padding: 0.25rem 0.55rem !important;
            border-radius: 6px !important;
            font-size: 0.7rem !important;
            display: inline-flex !important;
            align-items: center !important;
        }
        .room-badge-formal {
            background: rgba(201, 168, 76, 0.12) !important;
            border: 1px solid rgba(201, 168, 76, 0.3) !important;
            color: #E8D08D !important;
            font-size: 0.72rem !important;
            font-weight: 700 !important;
            padding: 0.2rem 0.55rem !important;
            border-radius: 6px !important;
            display: inline-flex !important;
            align-items: center !important;
            letter-spacing: 0.02em !important;
            white-space: nowrap !important;
            flex-shrink: 0 !important;
        }
        .action-link-formal {
            color: var(--gold-light, #E0C06E) !important;
            font-size: 0.78rem !important;
            font-weight: 600 !important;
            text-decoration: none !important;
            display: inline-flex !important;
            align-items: center !important;
            gap: 0.25rem !important;
            padding: 0.28rem 0.65rem !important;
            border-radius: 6px !important;
            background: rgba(201, 168, 76, 0.08) !important;
            border: 1px solid rgba(201, 168, 76, 0.25) !important;
            white-space: nowrap !important;
            flex-shrink: 0 !important;
            transition: all 0.2s ease !important;
        }
        .action-link-formal:hover {
            background: rgba(201, 168, 76, 0.18) !important;
            border-color: var(--gold, #C9A84C) !important;
            color: #FFFFFF !important;
        }

        /* ── AI Panel ──────────────────────────────────────────────────── */
        .ai-panel { background: rgba(201,168,76,0.08); border: 1px solid rgba(201,168,76,0.3); border-radius: 16px; padding: 1.25rem; }
        .ai-badge { background: rgba(201,168,76,0.15); border: 1px solid rgba(201,168,76,0.4); color: #E6CB7E; border-radius: 20px; padding: 0.2rem 0.6rem; font-size: 0.7rem; font-weight: 600; }

        /* ── KDS Stepper ───────────────────────────────────────────────── */
        .stepper-track { display: flex; align-items: center; justify-content: space-between; position: relative; margin: 1.5rem 0; }
        .stepper-line { position: absolute; top: 18px; left: 20px; right: 20px; height: 3px; background: rgba(255,255,255,0.1); z-index: 1; }
        .stepper-progress { position: absolute; top: 18px; left: 20px; height: 3px; background: linear-gradient(90deg, #C9A84C, #22c55e); z-index: 2; transition: width 0.4s ease; }
        .step-item { position: relative; z-index: 3; display: flex; flex-direction: column; align-items: center; text-align: center; }
        .step-circle { width: 38px; height: 38px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.85rem; font-weight: 700; background: #0D1726; border: 2px solid rgba(255,255,255,0.2); color: var(--muted); transition: all 0.3s; }
        .step-item.active .step-circle { border-color: var(--gold); background: var(--gold); color: #0B1422; box-shadow: 0 0 15px rgba(201,168,76,0.5); }
        .step-item.completed .step-circle { border-color: #22c55e; background: #22c55e; color: #0B1422; }
        .step-label { font-size: 0.72rem; color: var(--muted); margin-top: 0.4rem; font-weight: 500; }
        .step-item.active .step-label { color: var(--gold); font-weight: 700; }
        .step-item.completed .step-label { color: #86efac; }

        /* ── Pulse Indicator ───────────────────────────────────────────── */
        @keyframes pulseDot { 0%, 100% { opacity: 1; transform: scale(1); } 50% { opacity: 0.4; transform: scale(1.3); } }
        .pulse-dot { width: 8px; height: 8px; border-radius: 50%; display: inline-block; animation: pulseDot 1.8s infinite; }

        /* ── Flash Alert ───────────────────────────────────────────────── */
        .alert-success-custom { background: rgba(34,197,94,0.12); border: 1px solid rgba(34,197,94,0.35); color: #86efac; border-radius: 12px; }
        .alert-danger-custom  { background: rgba(239,68,68,0.12);  border: 1px solid rgba(239,68,68,0.35);  color: #fca5a5; border-radius: 12px; }

        /* ── Animations ────────────────────────────────────────────────── */
        @keyframes fadeInUp { from { opacity:0; transform:translateY(12px); } to { opacity:1; transform:translateY(0); } }
        .fade-in { animation: fadeInUp 0.35s ease; }

        /* ── Scrollbar ─────────────────────────────────────────────────── */
        ::-webkit-scrollbar { width: 5px; height: 5px; }
        ::-webkit-scrollbar-track { background: #0B1422; }
        ::-webkit-scrollbar-thumb { background: var(--border); border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: var(--gold); }

        /* ── Mobile Responsive & Drawer Enhancements ──────────────────── */
        .sidebar-backdrop {
            display: none;
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(4, 8, 15, 0.78);
            backdrop-filter: blur(5px);
            -webkit-backdrop-filter: blur(5px);
            z-index: 1040;
            opacity: 0;
            transition: opacity 0.28s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .sidebar-backdrop.show {
            display: block;
            opacity: 1;
        }

        /* Mobile Bottom Nav */
        .mobile-bottom-nav {
            position: fixed;
            bottom: 0; left: 0; right: 0;
            height: 64px;
            background: rgba(11, 20, 34, 0.97);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-top: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-around;
            z-index: 1020;
            box-shadow: 0 -4px 25px rgba(0, 0, 0, 0.6);
            padding: 0 0.35rem;
            padding-bottom: max(0px, env(safe-area-inset-bottom));
        }
        .mobile-nav-item {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: #94A3B8;
            text-decoration: none;
            font-size: 0.65rem;
            font-weight: 500;
            padding: 0.3rem 0;
            gap: 2px;
            transition: all 0.2s ease;
            cursor: pointer;
            min-height: 48px;
            position: relative;
        }
        .mobile-nav-item i {
            font-size: 1.25rem;
            line-height: 1;
            transition: transform 0.2s ease;
        }
        .mobile-nav-item:hover, .mobile-nav-item.active {
            color: #FFFFFF;
        }
        .mobile-nav-item.active i {
            color: var(--gold);
            transform: translateY(-2px);
        }
        .mobile-nav-item.active span {
            color: var(--gold-light);
            font-weight: 700;
        }
        .mobile-nav-item.guest-item {
            color: #E8D08D;
        }
        .mobile-nav-item.guest-item i {
            color: #F59E0B;
        }
        .mobile-nav-badge {
            position: absolute;
            top: -3px;
            right: -8px;
            font-size: 0.58rem;
            font-weight: 700;
            padding: 0.1rem 0.35rem;
            border-radius: 10px;
            line-height: 1;
            box-shadow: 0 0 8px rgba(0,0,0,0.5);
        }

        @media (max-width: 991.98px) {
            .sidebar {
                transform: translateX(-100%);
                z-index: 1060;
                width: min(300px, 86vw);
                box-shadow: 10px 0 40px rgba(0, 0, 0, 0.9);
            }
            .sidebar.open {
                transform: translateX(0);
            }
            .sidebar-footer {
                padding-bottom: max(1.25rem, env(safe-area-inset-bottom));
            }
            .main-content {
                margin-left: 0 !important;
                padding-bottom: 74px; /* Space for bottom navigation bar on handhelds */
            }
            .topbar {
                padding: 0.6rem 0.85rem;
            }
            .topbar-subtitle {
                font-size: 0.58rem;
                letter-spacing: 0.8px;
            }
            .topbar-title {
                font-size: 1.05rem;
            }
            .page-content {
                padding: 0.85rem 0.75rem;
            }
            .stat-card {
                padding: 0.85rem 0.7rem;
                border-radius: 14px;
            }
            .stat-value {
                font-size: 1.55rem;
            }
            .stat-label {
                font-size: 0.7rem;
            }
            .stat-icon {
                font-size: 1.25rem;
            }
            .table-card {
                border-radius: 14px;
            }
            .data-table th, .data-table td {
                padding: 0.65rem 0.75rem;
            }
            .table-responsive {
                -webkit-overflow-scrolling: touch;
            }
            .form-control, .form-select {
                font-size: 16px !important; /* Prevents auto-zoom on mobile safari/chrome */
            }
        }

        /* ── Real-Time Staff Live Notifications & Highlights ──────── */
        #staffNotificationToastContainer {
            position: fixed;
            top: 76px;
            right: 24px;
            z-index: 1095;
            display: flex;
            flex-direction: column;
            gap: 12px;
            max-width: 420px;
            width: calc(100vw - 48px);
            pointer-events: none;
        }
        @media (max-width: 768px) {
            #staffNotificationToastContainer {
                top: 70px;
                right: 14px;
                width: calc(100vw - 28px);
            }
        }
        .staff-toast {
            pointer-events: auto;
            background: linear-gradient(135deg, rgba(22, 34, 54, 0.96) 0%, rgba(11, 20, 34, 0.98) 100%);
            border: 1.5px solid rgba(201, 168, 76, 0.6);
            border-radius: 14px;
            padding: 1rem 1.15rem;
            box-shadow: 0 12px 35px rgba(0, 0, 0, 0.65), 0 0 20px rgba(201, 168, 76, 0.2);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            animation: toastSlideIn 0.35s cubic-bezier(0.16, 1, 0.3, 1);
            transition: all 0.3s ease;
        }
        .staff-toast.urgent {
            border-color: rgba(239, 68, 68, 0.85);
            box-shadow: 0 12px 35px rgba(0, 0, 0, 0.75), 0 0 25px rgba(239, 68, 68, 0.45);
            animation: toastSlideIn 0.35s cubic-bezier(0.16, 1, 0.3, 1), pulseRedToast 2s infinite;
        }
        .staff-toast .st-close {
            text-decoration: none !important;
            transition: background-color 0.15s ease, color 0.15s ease;
        }
        .staff-toast .st-close:hover {
            background: rgba(255, 255, 255, 0.08) !important;
            color: #F8FAFC !important;
        }
        @keyframes toastSlideIn {
            from { opacity: 0; transform: translateX(50px) scale(0.95); }
            to   { opacity: 1; transform: translateX(0) scale(1); }
        }
        @keyframes pulseRedToast {
            0%, 100% { box-shadow: 0 12px 35px rgba(0, 0, 0, 0.75), 0 0 15px rgba(239, 68, 68, 0.3); }
            50%      { box-shadow: 0 12px 35px rgba(0, 0, 0, 0.75), 0 0 30px rgba(239, 68, 68, 0.65); }
        }
        .badge-pulse {
            animation: badgePulseAnim 1.2s cubic-bezier(0.16, 1, 0.3, 1) 3;
        }
        @keyframes badgePulseAnim {
            0%   { transform: scale(1); box-shadow: 0 0 0 rgba(201,168,76,0); }
            50%  { transform: scale(1.35); box-shadow: 0 0 18px rgba(201,168,76,0.9); }
            100% { transform: scale(1); box-shadow: 0 0 0 rgba(201,168,76,0); }
        }
        .highlight-new-row {
            animation: highlightNewRowAnim 4s ease-out;
        }
        @keyframes highlightNewRowAnim {
            0%   { background-color: rgba(201, 168, 76, 0.35) !important; }
            100% { background-color: transparent; }
        }
    </style>
    {{-- Madison Park brand layer (remove this line to roll back the theme) --}}
    <link rel="stylesheet" href="{{ asset('css/madison-theme.css') }}?v={{ @filemtime(public_path('css/madison-theme.css')) }}">
    @stack('styles')
</head>
<body>
<!-- Mobile Sidebar Backdrop -->
<div class="sidebar-backdrop" id="sidebarBackdrop" onclick="toggleSidebar(false)"></div>

<!-- Sidebar -->
<nav class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <span class="brand-crest-frame">
            <img src="{{ asset('images/hotel/madison-logo.png') }}?v={{ @filemtime(public_path('images/hotel/madison-logo.png')) }}" alt="Madison Park Logo" class="brand-logo-img">
        </span>
        <div class="brand-text flex-grow-1 min-w-0">
            <div class="brand-name">Madison Park</div>
            <div class="brand-sub">GuestLink Staff</div>
        </div>
        <button type="button" class="sidebar-collapse-btn d-none d-lg-inline-flex" id="btnSidebarCollapse" onclick="toggleSidebarCollapse()" title="Collapse / Expand Sidebar" aria-label="Collapse or expand sidebar">
            <i class="bi bi-chevron-double-left"></i>
        </button>
        <button type="button" class="btn btn-sm btn-link text-decoration-none text-white-50 d-lg-none p-1 border-0 ms-auto" onclick="toggleSidebar(false)" aria-label="Close menu">
            <i class="bi bi-x-lg fs-5"></i>
        </button>
    </div>

    @php
        $authUser = auth()->user();
        $userRole = $authUser->getRoleNames()->first() ?? 'front_desk';
        $isAdmin = $authUser->hasRole('admin');
        $isOwner = $authUser->hasRole('owner');
        $isFrontDesk = $authUser->hasRole('front_desk') || $isOwner || $isAdmin;
        $isRestaurant = $authUser->hasRole('restaurant');
        $isMaintenance = $authUser->hasRole('maintenance') || $authUser->hasRole('maintenance_supervisor');
        $isHousekeeping = $authUser->hasRole('housekeeping');
        
        $roleEmoji = match($userRole) {
            'admin' => 'Administrator',
            'front_desk' => 'Front Desk',
            'restaurant' => 'Restaurant',
            'maintenance' => 'Maintenance',
            'maintenance_supervisor' => 'Maint. Supervisor',
            'housekeeping' => 'Housekeeping',
            'owner' => 'Hotel Owner',
            default => ucfirst(str_replace('_', ' ', $userRole))
        };
        
        $sidebarCounts = \Illuminate\Support\Facades\DB::selectOne("
            SELECT
                sr.pending_reqs,
                sr.urgent_reqs,
                sr.maint_pending,
                fo.pending_orders
            FROM
                (SELECT
                    COALESCE(SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END), 0) AS pending_reqs,
                    COALESCE(SUM(CASE WHEN LOWER(priority) = 'urgent' AND status NOT IN ('resolved','closed') THEN 1 ELSE 0 END), 0) AS urgent_reqs,
                    COALESCE(SUM(CASE WHEN type = 'maintenance' AND status NOT IN ('resolved','closed') THEN 1 ELSE 0 END), 0) AS maint_pending
                 FROM service_requests) sr,
                (SELECT
                    COALESCE(SUM(CASE WHEN status IN ('pending','confirmed','preparing') THEN 1 ELSE 0 END), 0) AS pending_orders
                 FROM food_orders) fo
        ");
        $pendingReqs = (int) ($sidebarCounts->pending_reqs ?? 0);
        $urgentReqs = (int) ($sidebarCounts->urgent_reqs ?? 0);
        $pendingOrders = (int) ($sidebarCounts->pending_orders ?? 0);
        $maintPending = (int) ($sidebarCounts->maint_pending ?? 0);
    @endphp

    <div class="sidebar-body">
        {{-- FRONT DESK & OWNER VIEW --}}
        @if($isFrontDesk)
        <div class="nav-section">
            <div class="nav-label">
                <span>Command Center</span>
                <span id="sidebarUrgentBadge" class="badge bg-danger text-white py-0 px-1 {{ $urgentReqs > 0 ? '' : 'd-none' }}" style="font-size:0.6rem;">{{ $urgentReqs }} Urgent</span>
            </div>
            <a href="{{ route('dashboard') }}" class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <i class="bi bi-speedometer2"></i>
                <span>Operations Dashboard</span>
            </a>
            <a href="{{ route('requests.index') }}" class="nav-item {{ request()->routeIs('requests.*') ? 'active' : '' }}">
                <i class="bi bi-ticket-perforated"></i>
                <span>Guest Requests</span>
                <span id="sidebarAllRequestsBadge" class="nav-badge {{ $pendingReqs > 0 ? '' : 'd-none' }}">{{ $pendingReqs }}</span>
            </a>
            <a href="{{ route('food.orders') }}" class="nav-item {{ request()->routeIs('food.orders*') ? 'active' : '' }}">
                <i class="bi bi-bag-check"></i>
                <span>Room Food Orders</span>
                <span id="sidebarOrdersBadge" class="nav-badge badge-amber {{ $pendingOrders > 0 ? '' : 'd-none' }}">{{ $pendingOrders }}</span>
            </a>
            <a href="{{ route('food.menu') }}" class="nav-item {{ request()->routeIs('food.menu*') ? 'active' : '' }}">
                <i class="bi bi-journal-bookmark"></i>
                <span>Times Square Menu</span>
            </a>
            @if(!$isAdmin)
            <a href="{{ route('admin.rooms') }}" class="nav-item {{ request()->routeIs('admin.rooms*') ? 'active' : '' }}">
                <i class="bi bi-qr-code-scan"></i>
                <span>Rooms &amp; QR Codes</span>
            </a>
            @endif
            @if($isOwner && !$isAdmin)
            <a href="{{ route('admin.reports') }}" class="nav-item {{ request()->routeIs('admin.reports*') ? 'active' : '' }}">
                <i class="bi bi-graph-up-arrow"></i>
                <span>Analytics &amp; Reports</span>
            </a>
            @endif
        </div>
        @endif

        {{-- HOUSEKEEPING VIEW --}}
        @if($isHousekeeping && !$isAdmin)
        <div class="nav-section">
            <div class="nav-label">
                <span>Housekeeping Services</span>
                <span class="badge bg-warning text-dark py-0 px-1 {{ $pendingReqs > 0 ? '' : 'd-none' }}" style="font-size:0.6rem;">{{ $pendingReqs }} Pending</span>
            </div>
            <a href="{{ route('dashboard') }}" class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <i class="bi bi-speedometer2"></i>
                <span>Service Overview</span>
            </a>
            <a href="{{ route('requests.index') }}" class="nav-item {{ request()->routeIs('requests.*') ? 'active' : '' }}">
                <i class="bi bi-bell"></i>
                <span>Guest Service Tickets</span>
                <span class="nav-badge {{ $pendingReqs > 0 ? '' : 'd-none' }}">{{ $pendingReqs }}</span>
            </a>
        </div>
        @endif

        {{-- RESTAURANT / KITCHEN VIEW --}}
        @if($isRestaurant)
        <div class="nav-section">
            <div class="nav-label">
                <span>Kitchen & Dining</span>
                <span class="badge py-0 px-1" style="font-size:0.6rem; background:rgba(201,168,76,0.22); color:#E8D08D; border:1px solid rgba(201,168,76,0.4);">KDS</span>
            </div>
            <a href="{{ route('dashboard') }}" class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <i class="bi bi-speedometer2"></i>
                <span>Kitchen Overview</span>
            </a>
            <a href="{{ route('food.orders') }}" class="nav-item {{ request()->routeIs('food.orders*') ? 'active' : '' }}">
                <i class="bi bi-fire"></i>
                <span>Active Orders Queue</span>
                <span id="sidebarKitchenOrdersBadge" class="nav-badge {{ $pendingOrders > 0 ? '' : 'd-none' }}">{{ $pendingOrders }}</span>
            </a>
            <a href="{{ route('food.menu') }}" class="nav-item {{ request()->routeIs('food.menu*') ? 'active' : '' }}">
                <i class="bi bi-egg-fried"></i>
                <span>Menu & Stock Toggle</span>
            </a>
        </div>
        @endif

        {{-- MAINTENANCE & ENGINEERING VIEW --}}
        @if($isMaintenance && !$isAdmin)
        <div class="nav-section">
            <div class="nav-label">
                <span>Maintenance & Engineering</span>
                <span id="sidebarMaintActiveBadge" class="badge bg-danger text-white py-0 px-1 {{ $maintPending > 0 ? '' : 'd-none' }}" style="font-size:0.6rem;">{{ $maintPending }} Active</span>
            </div>
            <a href="{{ route('dashboard') }}" class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <i class="bi bi-speedometer2"></i>
                <span>Workboard Overview</span>
            </a>
            <a href="{{ route('requests.index') }}" class="nav-item {{ request()->routeIs('requests.*') ? 'active' : '' }}">
                <i class="bi bi-tools"></i>
                <span>Repair Tickets</span>
                <span id="sidebarMaintBadge" class="nav-badge {{ $maintPending > 0 ? '' : 'd-none' }}">{{ $maintPending }}</span>
            </a>
        </div>
        @endif

        {{-- ADMIN MANAGEMENT SECTION --}}
        @if($isAdmin)
        <div class="nav-section">
            <div class="nav-label">Hotel Administration</div>
            <a href="{{ route('admin.dashboard') }}" class="nav-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                <i class="bi bi-shield-check"></i>
                <span>Admin Overview</span>
            </a>
            <a href="{{ route('admin.rooms') }}" class="nav-item {{ request()->routeIs('admin.rooms*') ? 'active' : '' }}">
                <i class="bi bi-qr-code-scan"></i>
                <span>Rooms & QR Codes</span>
            </a>
            <a href="{{ route('admin.users') }}" class="nav-item {{ request()->routeIs('admin.users*') ? 'active' : '' }}">
                <i class="bi bi-people"></i>
                <span>Staff Accounts</span>
            </a>
            <a href="{{ route('admin.categories') }}" class="nav-item {{ request()->routeIs('admin.categories*') ? 'active' : '' }}">
                <i class="bi bi-tags"></i>
                <span>Request Categories</span>
            </a>
            <a href="{{ route('admin.reports') }}" class="nav-item {{ request()->routeIs('admin.reports*') ? 'active' : '' }}">
                <i class="bi bi-graph-up-arrow"></i>
                <span>Analytics & Reports</span>
            </a>
        </div>
        @endif
    </div>

</nav>

<!-- Main Content -->
<div class="main-content">
    <div class="topbar">
        <div class="d-flex align-items-center gap-3">
            <button class="d-lg-none btn-ghost p-1 px-2" onclick="toggleSidebar(true)" aria-label="Open navigation menu">
                <i class="bi bi-list fs-5"></i>
            </button>
            <div class="topbar-brand-title">
                <span class="topbar-subtitle">Madison Park Hotel • Command Center</span>
                <h2 class="topbar-title">@yield('page-title', 'Operations Dashboard')</h2>
            </div>
        </div>

        <div class="topbar-controls d-flex align-items-center gap-2 justify-content-end">
            <!-- Unified Live Telemetry Strip (Clock + AI Triage) -->
            <div class="topbar-telemetry d-none d-xl-inline-flex align-items-center" title="Hotel System Clock & AI Engine">
                <span class="topbar-tel-seg d-inline-flex align-items-center gap-1.5">
                    <i class="bi bi-clock topbar-tel-ico"></i>
                    <span class="widget-date" id="clockDate">{{ now()->format('D, M j') }}</span>
                    <span class="topbar-tel-dot">&middot;</span>
                    <span class="widget-clock" id="clockTime">{{ now()->format('h:i:s A') }}</span>
                </span>
                @if($isFrontDesk)
                <span class="topbar-tel-divider" aria-hidden="true"></span>
                <span class="topbar-tel-seg topbar-tel-ai d-inline-flex align-items-center gap-1.5" title="Gemini AI Triage Active">
                    <i class="bi bi-stars"></i>
                    <span>AI Triage</span>
                </span>
                @endif
            </div>

            <!-- Compact Clock for Tablet / Laptop Screens -->
            <div class="topbar-telemetry d-inline-flex d-xl-none align-items-center" title="Current Time">
                <span class="topbar-tel-seg d-inline-flex align-items-center gap-1.5">
                    <i class="bi bi-clock topbar-tel-ico"></i>
                    <span class="widget-clock" id="clockTimeMobile">{{ now()->format('h:i A') }}</span>
                </span>
            </div>

            <!-- Global Light / Dark Theme Switcher -->
            <button type="button" id="btnStaffThemeToggle" onclick="toggleGuestLinkTheme()" class="mp-theme-toggle topbar-pill-btn" aria-label="Toggle Light or Dark Mode" title="Switch Light / Dark Mode">
                <span class="icon-sun"><i class="bi bi-sun-fill"></i></span>
                <span class="icon-moon"><i class="bi bi-moon-stars-fill"></i></span>
                <span class="d-none d-lg-inline" id="staffThemeLabel">Light</span>
            </button>

            <!-- Topbar Notification Bell Dropdown -->
            <div class="dropdown">
                <button class="topbar-notif-btn topbar-pill-btn" type="button" id="topbarNotifBtn" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false" title="Hotel Operations Alerts & Live Notifications">
                    <i class="bi bi-bell"></i>
                    <span class="d-none d-md-inline topbar-btn-label">Alerts</span>
                    <span id="topbarNotifBadge" class="topbar-notif-badge d-none">0</span>
                </button>
                <div class="dropdown-menu dropdown-menu-end p-0" id="topbarNotifDropdown" aria-labelledby="topbarNotifBtn" style="background:#0B111E; border:1px solid rgba(201,162,74,0.32); border-radius:16px; width:392px; max-width:calc(100vw - 28px); overflow:hidden; margin-top:10px; padding:0; box-shadow:0 22px 54px rgba(0,0,0,0.82);">
                    <!-- Clean Header -->
                    <div class="px-3 py-3 border-bottom d-flex align-items-center justify-content-between gap-2" style="border-color:rgba(246,241,231,0.08) !important; background:#0F1727;">
                        <div class="d-flex align-items-center gap-2.5 min-w-0">
                            <span style="width:32px; height:32px; min-width:32px; border-radius:9px; background:rgba(201,162,74,0.12); border:1px solid rgba(201,162,74,0.28); display:inline-flex; align-items:center; justify-content:center; color:#E6CA7A;">
                                <i class="bi bi-bell-fill" style="font-size:0.85rem;"></i>
                            </span>
                            <div class="min-w-0">
                                <div class="d-flex align-items-center gap-1.5">
                                    <span style="font-weight:700; color:#F6F1E7; font-size:0.88rem; line-height:1.2; white-space:nowrap;">Live Alerts</span>
                                    <span id="topbarNotifUnreadPill" class="badge bg-danger d-none" style="font-size:0.62rem; padding:0.15rem 0.45rem !important;">0 new</span>
                                </div>
                                <div style="font-size:0.7rem; color:#7D8696; white-space:nowrap;">
                                    {{ $isRestaurant ? 'Live kitchen & dining orders' : ($isMaintenance ? 'Maintenance work orders feed' : 'Real-time operations feed') }}
                                </div>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-1.5 flex-shrink-0">
                            <button type="button" class="btn-ghost py-1 px-2 text-decoration-none text-nowrap" onclick="markAllNotificationsRead(event);" style="font-size:0.7rem; border-radius:7px;" title="Mark all as read">
                                Mark Read
                            </button>
                            <button type="button" class="btn-ghost py-1 px-2 text-decoration-none text-nowrap" onclick="clearAllNotifications(event);" style="font-size:0.7rem; border-radius:7px;" title="Clear all">
                                Clear
                            </button>
                        </div>
                    </div>
                    <!-- Segmented Category Filter Tabs (Role-Scoped) -->
                    @if(!$isRestaurant && !$isMaintenance && !($isHousekeeping && !$isAdmin))
                    <div class="px-2.5 py-2 border-bottom" style="background:#080D17; border-color:rgba(246,241,231,0.07) !important;">
                        <div class="d-flex align-items-center gap-1 p-1 rounded-3" style="background:rgba(255,255,255,0.025); border:1px solid rgba(246,241,231,0.06);" onclick="event.stopPropagation();">
                            <button type="button" class="notif-filter-tab active" data-filter="all" onclick="event.stopPropagation(); filterTopbarNotifications('all', this)">All</button>
                            <button type="button" class="notif-filter-tab" data-filter="requests" onclick="event.stopPropagation(); filterTopbarNotifications('requests', this)"><i class="bi bi-bell"></i><span>Requests</span></button>
                            <button type="button" class="notif-filter-tab" data-filter="orders" onclick="event.stopPropagation(); filterTopbarNotifications('orders', this)"><i class="bi bi-cup-hot"></i><span>Orders</span></button>
                            <button type="button" class="notif-filter-tab" data-filter="messages" onclick="event.stopPropagation(); filterTopbarNotifications('messages', this)"><i class="bi bi-chat-dots"></i><span>Chat</span></button>
                        </div>
                    </div>
                    @endif
                    <div id="topbarNotifList" style="max-height:340px; overflow-y:auto; display:flex; flex-direction:column;">
                        <div id="topbarNotifEmpty" class="text-center py-4 px-3" style="color:#7D8696; font-size:0.78rem;">
                            <i class="bi bi-bell-slash d-block mb-2" style="font-size:1.35rem; opacity:0.55;"></i>
                            @if($isRestaurant)
                                No active kitchen alerts.<br>New room delivery &amp; Times Square dine-in orders appear here live.
                            @elseif($isMaintenance)
                                No active maintenance alerts.<br>New repair tickets assigned to Maintenance appear here live.
                            @else
                                No active notifications.<br>New guest requests, orders &amp; messages appear here live.
                            @endif
                        </div>
                    </div>
                    <div class="px-3 py-2.5 border-top d-flex align-items-center justify-content-between" style="border-color:rgba(246,241,231,0.08) !important; background:#090E18;">
                        @if($isRestaurant)
                            <a href="{{ route('food.orders') }}" class="text-decoration-none text-nowrap" style="font-size:0.75rem; font-weight:600; color:#E6CA7A;">
                                Kitchen Queue &rarr;
                            </a>
                            <a href="{{ route('food.menu') }}" class="text-decoration-none text-nowrap" style="font-size:0.75rem; font-weight:500; color:#94A3B8;">
                                Times Square Menu &rarr;
                            </a>
                        @elseif($isMaintenance || ($isHousekeeping && !$isAdmin))
                            <a href="{{ route('requests.index') }}" class="text-decoration-none text-nowrap" style="font-size:0.75rem; font-weight:600; color:#E6CA7A;">
                                {{ $isMaintenance ? 'Maintenance Queue' : 'Service Tickets' }} &rarr;
                            </a>
                            <a href="{{ route('dashboard') }}" class="text-decoration-none text-nowrap" style="font-size:0.75rem; font-weight:500; color:#94A3B8;">
                                Dashboard &rarr;
                            </a>
                        @else
                            <a href="{{ route('requests.index') }}" class="text-decoration-none text-nowrap" style="font-size:0.75rem; font-weight:600; color:#E6CA7A;">
                                All Requests &rarr;
                            </a>
                            <a href="{{ route('food.orders') }}" class="text-decoration-none text-nowrap" style="font-size:0.75rem; font-weight:500; color:#94A3B8;">
                                Kitchen Queue &rarr;
                            </a>
                        @endif
                    </div>
                </div>
            </div>

            <!-- User Quick Profile, Account Preferences & Sign Out Dropdown -->
            <div class="dropdown">
                <button class="topbar-user-btn topbar-profile-pill dropdown-toggle" type="button" id="topbarUserBtn" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false" title="Account Menu &amp; Saved Preferences ({{ $authUser->name }} · {{ $roleEmoji }})">
                    <span class="topbar-avatar-wrap">
                        <span class="topbar-user-avatar">{{ strtoupper(substr($authUser->name, 0, 1)) }}</span>
                        <span class="topbar-online-dot" title="On duty"></span>
                    </span>
                    <span class="d-none d-sm-flex flex-column text-start topbar-user-meta">
                        <span class="topbar-user-name">{{ $authUser->name }}</span>
                        <span class="topbar-user-role">{{ $roleEmoji }}</span>
                    </span>
                    <span class="topbar-profile-caret" aria-hidden="true">
                        <i class="bi bi-chevron-down"></i>
                    </span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-lg" style="background:#132034; border:1px solid rgba(201,168,76,0.35); border-radius:14px; min-width:262px; padding:0.55rem; margin-top:8px;">
                    <li class="px-3 py-2 border-bottom" style="border-color:rgba(201,168,76,0.2) !important;">
                        <div class="text-white font-weight-bold" style="font-size:0.85rem;">{{ $authUser->name }}</div>
                        <div class="text-warning" style="font-size:0.72rem; font-weight:600;">{{ $roleEmoji }}</div>
                        <div style="font-size:0.68rem; color:#8899AA;" class="text-truncate">{{ $authUser->email }}</div>
                    </li>

                    {{-- Saved Per-Account Preferences --}}
                    <li class="px-2.5 py-2.5 border-bottom" style="border-color:rgba(201,168,76,0.18) !important;" onclick="event.stopPropagation();">
                        <div class="d-flex align-items-center justify-content-between mb-2 px-0.5">
                            <span style="font-size:0.64rem; letter-spacing:0.14em; text-transform:uppercase; color:#94A3B8; font-weight:700;">
                                Account Preferences
                            </span>
                            <span id="accountPrefSavedBadge" class="badge bg-success d-none" style="font-size:0.6rem; padding:0.15rem 0.45rem !important;">
                                <i class="bi bi-check2"></i> Saved
                            </span>
                        </div>

                        {{-- Appearance (Dark / Light Mode) --}}
                        <button type="button" onclick="toggleGuestLinkTheme()" class="dropdown-item d-flex align-items-center justify-content-between rounded-2 px-2 py-2 mb-1" style="font-size:0.78rem; font-weight:600;">
                            <span class="d-inline-flex align-items-center gap-2">
                                <i class="bi bi-circle-half text-gold"></i>
                                <span>Appearance</span>
                            </span>
                            <span id="accountThemeModePill" class="badge badge-status-dept" style="font-size:0.66rem;">
                                {{ $__savedTheme === 'light' ? 'Light Mode' : 'Dark Mode' }}
                            </span>
                        </button>

                        @if(!($isRestaurant && !$isFrontDesk))
                        {{-- Madi AI Auto-Reply Bot (Saved to Account & Active Queue) --}}
                        <div class="d-flex align-items-center justify-content-between gap-2 rounded-2 px-2 py-2" style="background:rgba(255,255,255,0.04); border:1px solid rgba(201,168,76,0.2);">
                            <label for="accountBotToggle" class="m-0 flex-grow-1" style="cursor:pointer;">
                                <div class="d-flex align-items-center gap-1.5" style="font-size:0.8rem; font-weight:600; color:#F8FAFC;">
                                    <i class="bi bi-robot text-gold"></i>
                                    <span class="account-pref-title" style="color:#F8FAFC;">Madi AI Bot</span>
                                    <span id="accountBotStatusText" class="badge {{ $__savedBotEnabled ? 'badge-status-resolved' : 'badge-status-dept' }}" style="font-size:0.6rem; padding:0.15rem 0.45rem !important;">
                                        {{ $__savedBotEnabled ? 'ON' : 'OFF' }}
                                    </span>
                                </div>
                                <div style="font-size:0.68rem; color:#94A3B8; margin-top:2px;">
                                    Auto-reply in live guest chats
                                </div>
                            </label>
                            <div class="form-check form-switch m-0 p-0 d-inline-flex align-items-center flex-shrink-0">
                                <input class="form-check-input m-0" type="checkbox" role="switch" id="accountBotToggle"
                                       {{ $__savedBotEnabled ? 'checked' : '' }}
                                       onchange="toggleAccountBotPreference(this)"
                                       style="cursor:pointer; width:34px; height:18px;">
                            </div>
                        </div>
                        @endif
                    </li>

                    <li class="pt-1">
                        <form method="POST" action="{{ route('logout') }}" class="m-0 p-1">
                            @csrf
                            <button type="submit" class="dropdown-item text-danger d-flex align-items-center justify-content-between rounded-2 py-2" style="font-size:0.82rem; font-weight:600;">
                                <span><i class="bi bi-box-arrow-left me-2"></i>Sign Out</span>
                                <i class="bi bi-chevron-right" style="font-size:0.7rem;"></i>
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success-custom alert-dismissible fade show mx-3 mt-3 mb-0" role="alert">
        <i class="bi bi-check-circle me-2"></i> {{ session('success') }}
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
    </div>
    @endif
    @if(session('error'))
    <div class="alert alert-danger-custom alert-dismissible fade show mx-3 mt-3 mb-0" role="alert">
        <i class="bi bi-exclamation-triangle me-2"></i> {{ session('error') }}
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <div class="page-content fade-in">
        @yield('content')
    </div>

    <!-- Mobile Bottom Navigation Bar (Phone Only) -->
    <nav class="mobile-bottom-nav d-md-none">
        <a href="{{ $isAdmin ? route('admin.dashboard') : route('dashboard') }}" class="mobile-nav-item {{ request()->routeIs('dashboard') || request()->routeIs('admin.dashboard') ? 'active' : '' }}">
            <i class="bi bi-speedometer2"></i>
            <span>Dashboard</span>
        </a>
        @if($userRole !== 'restaurant')
        <a href="{{ route('requests.index') }}" class="mobile-nav-item {{ request()->routeIs('requests.*') ? 'active' : '' }}">
            <div class="position-relative d-inline-block">
                <i class="bi bi-ticket-perforated"></i>
                <span id="mobileRequestsBadge" class="mobile-nav-badge {{ $urgentReqs > 0 ? 'bg-danger' : 'bg-warning' }} {{ ($urgentReqs > 0 || $pendingReqs > 0) ? '' : 'd-none' }}">
                    {{ $urgentReqs > 0 ? $urgentReqs : $pendingReqs }}
                </span>
            </div>
            <span>Requests</span>
        </a>
        @endif
        @if(!in_array($userRole, ['maintenance', 'maintenance_supervisor', 'housekeeping'], true))
        <a href="{{ route('food.orders') }}" class="mobile-nav-item {{ request()->routeIs('food.orders*') ? 'active' : '' }}">
            <div class="position-relative d-inline-block">
                <i class="bi bi-fire"></i>
                <span id="mobileOrdersBadge" class="mobile-nav-badge bg-warning {{ $pendingOrders > 0 ? '' : 'd-none' }}">
                    {{ $pendingOrders }}
                </span>
            </div>
            <span>Dining</span>
        </a>
        @endif
        <a href="{{ url('/guest-demo') }}" target="_blank" class="mobile-nav-item guest-item" title="Open Room 201 Guest Portal">
            <i class="bi bi-phone"></i>
            <span>Guest App</span>
        </a>
        <button type="button" class="mobile-nav-item border-0 bg-transparent" onclick="toggleSidebar(true)">
            <i class="bi bi-list"></i>
            <span>Menu</span>
        </button>
    </nav>
</div>

<!-- Floating Real-time Notification Toast Container -->
<div id="staffNotificationToastContainer"></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Sidebar Toggle with Backdrop & Body Scroll Lock
function toggleSidebar(show) {
    const sidebar = document.getElementById('sidebar');
    const backdrop = document.getElementById('sidebarBackdrop');
    if (!sidebar || !backdrop) return;
    
    const isOpen = (show !== undefined) ? show : !sidebar.classList.contains('open');
    if (isOpen) {
        sidebar.classList.add('open');
        backdrop.classList.add('show');
        document.body.style.overflow = 'hidden';
    } else {
        sidebar.classList.remove('open');
        backdrop.classList.remove('show');
        document.body.style.overflow = '';
    }
}

// Auto-close sidebar on mobile navigation
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('#sidebar .nav-item, #sidebar .btn-signout').forEach(function(item) {
        item.addEventListener('click', function() {
            if (window.innerWidth <= 768) {
                toggleSidebar(false);
            }
        });
    });
});

// Real-time Digital Clock & Live Calendar Date
function updateClockAndCalendar() {
    const now = new Date();
    const timeEl = document.getElementById('clockTime');
    const timeMobileEl = document.getElementById('clockTimeMobile');
    const dateEl = document.getElementById('clockDate');
    
    // Format Time: e.g. 11:45:20 AM
    const timeStr = now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true });
    if (timeEl) timeEl.textContent = timeStr;
    if (timeMobileEl) timeMobileEl.textContent = timeStr;
    
    // Format Date: e.g. Tue, Nov 24
    if (dateEl) {
        dateEl.textContent = now.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric' });
    }
}
setInterval(updateClockAndCalendar, 1000);
updateClockAndCalendar();

// ── Global Helper for XSS-safe dynamic HTML rendering ──────────────
window.escapeHtml = window.escapeHtml || function(str) {
    if (str === null || str === undefined) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
};

// ── Shared Singleton Web Audio Context & Synthesized Concierge Chime ──
window.__getGuestLinkAudioCtx = function() {
    const AudioCtx = window.AudioContext || window.webkitAudioContext;
    if (!AudioCtx) return null;
    if (!window.__guestLinkAudioCtx || window.__guestLinkAudioCtx.state === 'closed') {
        window.__guestLinkAudioCtx = new AudioCtx();
    }
    if (window.__guestLinkAudioCtx.state === 'suspended') {
        window.__guestLinkAudioCtx.resume().catch(() => {});
    }
    return window.__guestLinkAudioCtx;
};

function playStaffAlertChime(isUrgent = false) {
    try {
        const ctx = window.__getGuestLinkAudioCtx();
        if (!ctx) return;

        const now = ctx.currentTime;
        
        if (isUrgent) {
            [784, 987.77, 1174.66].forEach((freq, idx) => {
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();
                osc.type = 'sine';
                osc.frequency.setValueAtTime(freq, now + idx * 0.12);
                gain.gain.setValueAtTime(0, now + idx * 0.12);
                gain.gain.linearRampToValueAtTime(0.25, now + idx * 0.12 + 0.02);
                gain.gain.exponentialRampToValueAtTime(0.001, now + idx * 0.12 + 0.35);
                osc.connect(gain);
                gain.connect(ctx.destination);
                osc.start(now + idx * 0.12);
                osc.stop(now + idx * 0.12 + 0.36);
            });
        } else {
            const osc1 = ctx.createOscillator();
            const osc2 = ctx.createOscillator();
            const gain1 = ctx.createGain();
            const gain2 = ctx.createGain();

            osc1.type = 'sine';
            osc1.frequency.setValueAtTime(587.33, now);
            gain1.gain.setValueAtTime(0.2, now);
            gain1.gain.exponentialRampToValueAtTime(0.001, now + 0.8);
            osc1.connect(gain1);
            gain1.connect(ctx.destination);

            osc2.type = 'sine';
            osc2.frequency.setValueAtTime(880.00, now + 0.15);
            gain2.gain.setValueAtTime(0.22, now + 0.15);
            gain2.gain.exponentialRampToValueAtTime(0.001, now + 1.2);
            osc2.connect(gain2);
            gain2.connect(ctx.destination);

            osc1.start(now);
            osc1.stop(now + 0.85);
            osc2.start(now + 0.15);
            osc2.stop(now + 1.25);
        }
    } catch (e) {
        // AudioContext blocked until first user interaction
    }
}

// ── Live Notification Toast Renderers (XSS-Safe + Light/Dark Ready) ──
function showNotificationToast(req) {
    const container = document.getElementById('staffNotificationToastContainer');
    if (!container) return;

    const prioLower = String(req.priority || 'medium').toLowerCase();
    const prioLabel = prioLower.charAt(0).toUpperCase() + prioLower.slice(1);
    const isUrgent = prioLower === 'urgent';
    const toast = document.createElement('div');
    toast.className = `staff-toast ${isUrgent ? 'urgent' : ''}`;

    const photoBadge = req.has_photo ? '<span class="badge st-badge-gold" style="background:rgba(201,162,74,0.16); color:#E8D08D; border:1px solid rgba(201,162,74,0.32); font-size:0.68rem; margin-right:4px;"><i class="bi bi-camera me-1"></i>Photo</span>' : '';
    const urgentBadge = isUrgent
        ? '<span class="badge bg-danger" style="font-size:0.7rem;"><i class="bi bi-exclamation-triangle me-1"></i>URGENT</span>'
        : `<span class="badge st-badge-gold" style="background:rgba(201,162,74,0.16); color:#E8D08D; border:1px solid rgba(201,162,74,0.32); font-size:0.68rem;">${escapeHtml(prioLabel)} Priority</span>`;

    toast.innerHTML = `
        <div class="d-flex align-items-center justify-content-between mb-2">
            <div class="d-flex align-items-center gap-2">
                <i class="bi ${req.type === 'maintenance' ? 'bi-wrench-adjustable' : 'bi-bell'}" style="font-size:1.05rem; color:#C9A24A;"></i>
                <div>
                    <span class="st-title" style="font-weight:700; color:#fff; font-size:0.92rem;">Room ${escapeHtml(req.room_number)}</span>
                    <span class="st-meta" style="font-size:0.75rem; color:#8899AA; margin-left:4px;">• ${escapeHtml(req.type_label)}</span>
                </div>
            </div>
            <div class="d-flex align-items-center gap-1">
                ${urgentBadge}
                <button type="button" class="st-close" aria-label="Dismiss notification" onclick="this.closest('.staff-toast').remove()" style="background:transparent; border:none; color:#94A3B8; width:24px; height:24px; border-radius:6px; display:inline-flex; align-items:center; justify-content:center; padding:0; margin-left:4px; line-height:1; text-decoration:none; cursor:pointer;"><i class="bi bi-x-lg" style="font-size:0.75rem;"></i></button>
            </div>
        </div>
        <div class="st-body" style="font-size:0.82rem; color:#CBD5E1; line-height:1.45; margin-bottom:0.65rem;">
            ${photoBadge}
            ${escapeHtml(req.description)}
        </div>
        <div class="d-flex align-items-center justify-content-between pt-2 border-top border-secondary border-opacity-25">
            <span class="st-meta" style="font-size:0.7rem; color:#8899AA;">
                <i class="bi bi-clock me-1"></i>${escapeHtml(req.created_at_human)}
            </span>
            <a href="${escapeHtml(req.view_url)}" class="btn btn-sm btn-gold py-1 px-3" style="font-size:0.75rem; border-radius:14px; text-decoration:none;">
                Inspect Ticket &rarr;
            </a>
        </div>
    `;

    container.appendChild(toast);

    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(60px)';
        setTimeout(() => toast.remove(), 350);
    }, 9000);
}

function showOrderToast(ord) {
    const container = document.getElementById('staffNotificationToastContainer');
    if (!container) return;

    const toast = document.createElement('div');
    toast.className = 'staff-toast';

    toast.innerHTML = `
        <div class="d-flex align-items-center justify-content-between mb-2">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-cup-hot" style="font-size:1.15rem; color:#C9A84C;"></i>
                <div>
                    <span class="st-title" style="font-weight:700; color:#fff; font-size:0.92rem;">Room ${escapeHtml(ord.room_number)}</span>
                    <span class="st-meta" style="font-size:0.75rem; color:#8899AA; margin-left:4px;">• Times Square Order</span>
                </div>
            </div>
            <div class="d-flex align-items-center gap-1">
                <span class="badge st-badge-gold" style="font-size:0.7rem; background:rgba(201,168,76,0.2); color:#E8D08D; border:1px solid rgba(201,168,76,0.35);">₱${escapeHtml(ord.total_amount)}</span>
                <button type="button" class="st-close" aria-label="Dismiss notification" onclick="this.closest('.staff-toast').remove()" style="background:transparent; border:none; color:#94A3B8; width:24px; height:24px; border-radius:6px; display:inline-flex; align-items:center; justify-content:center; padding:0; margin-left:4px; line-height:1; text-decoration:none; cursor:pointer;"><i class="bi bi-x-lg" style="font-size:0.75rem;"></i></button>
            </div>
        </div>
        <div class="st-body" style="font-size:0.82rem; color:#CBD5E1; line-height:1.45; margin-bottom:0.65rem;">
            New food order placed with ${escapeHtml(ord.items_count)} item(s).
        </div>
        <div class="d-flex align-items-center justify-content-between pt-2 border-top border-secondary border-opacity-25">
            <span class="st-meta" style="font-size:0.7rem; color:#8899AA;">
                <i class="bi bi-clock me-1"></i>${escapeHtml(ord.created_at_human)}
            </span>
            <a href="${escapeHtml(ord.view_url)}" class="btn btn-sm btn-gold py-1 px-3" style="font-size:0.75rem; border-radius:14px; text-decoration:none;">
                View KDS Order &rarr;
            </a>
        </div>
    `;

    container.appendChild(toast);

    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(60px)';
        setTimeout(() => toast.remove(), 350);
    }, 9000);
}

function showGuestMessageToast(msg) {
    const container = document.getElementById('staffNotificationToastContainer');
    if (!container) return;

    const toast = document.createElement('div');
    toast.className = 'staff-toast';
    toast.style.borderColor = 'rgba(201,168,76,0.7)';

    toast.innerHTML = `
        <div class="d-flex align-items-center justify-content-between mb-2">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-chat-dots" style="font-size:1.05rem; color:#C9A24A;"></i>
                <div>
                    <span class="st-title" style="font-weight:700; color:#E8D08D; font-size:0.92rem;">Room ${escapeHtml(msg.room_number || '201')}</span>
                    <span class="st-meta" style="font-size:0.75rem; color:#8899AA; margin-left:4px;">• Guest Message</span>
                </div>
            </div>
            <button type="button" class="st-close" aria-label="Dismiss notification" onclick="this.closest('.staff-toast').remove()" style="background:transparent; border:none; color:#94A3B8; width:24px; height:24px; border-radius:6px; display:inline-flex; align-items:center; justify-content:center; padding:0; margin-left:4px; line-height:1; text-decoration:none; cursor:pointer;"><i class="bi bi-x-lg" style="font-size:0.75rem;"></i></button>
        </div>
        <div class="st-msg-box" style="font-size:0.83rem; color:#F8FAFC; line-height:1.45; margin-bottom:0.65rem; background:rgba(0,0,0,0.25); padding:0.45rem 0.65rem; border-radius:8px; border-left:3px solid var(--gold);">
            "${escapeHtml(msg.message)}"
        </div>
        <div class="d-flex align-items-center justify-content-between pt-2 border-top border-secondary border-opacity-25">
            <span class="st-meta" style="font-size:0.7rem; color:#8899AA;">
                <code style="color:var(--gold); font-size:0.75rem;">${escapeHtml(msg.reference_number || '')}</code>
            </span>
            <a href="${escapeHtml(msg.view_url)}" class="btn btn-sm btn-gold py-1 px-3" style="font-size:0.75rem; border-radius:14px; text-decoration:none;">
                Reply to Guest &rarr;
            </a>
        </div>
    `;

    container.appendChild(toast);

    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(60px)';
        setTimeout(() => toast.remove(), 350);
    }, 9000);
}

// ── Live Table Row Prepender (if on /requests) ──────────────────────
function prependRequestTableRow(req) {
    const tableBody = document.querySelector('#requestsTable tbody');
    if (!tableBody) return;

    if (tableBody.querySelector(`[data-ref="${CSS.escape ? CSS.escape(req.reference_number) : req.reference_number}"]`)) return;

    const emptyRow = document.getElementById('reqEmptyRow');
    if (emptyRow) emptyRow.style.display = 'none';

    const prioLower = String(req.priority || 'medium').toLowerCase();
    const prioLabel = prioLower.charAt(0).toUpperCase() + prioLower.slice(1);
    const deptLower = String(req.department || 'front desk').toLowerCase();
    const typeLower = String(req.type || 'service').toLowerCase();
    const statusLower = String(req.status || 'pending').toLowerCase();

    const tr = document.createElement('tr');
    tr.className = 'clickable-request-row highlight-new-row';
    tr.setAttribute('data-ref', req.reference_number);
    tr.setAttribute('data-department', deptLower);
    tr.setAttribute('data-type', typeLower);
    tr.setAttribute('data-is-event', '0');
    tr.setAttribute('data-status', statusLower);
    tr.setAttribute('data-priority', prioLower);
    tr.addEventListener('click', () => { window.location = req.view_url; });
    tr.title = `Click row to inspect Request #${req.reference_number}`;

    const photoIcon = req.has_photo ? '<i class="bi bi-camera me-1" title="Guest attached photo evidence" style="color:#C9A24A; font-size:0.82rem;"></i>' : '';
    const urgentBadgeStyle = prioLower === 'urgent' ? 'background:#ef4444 !important;' : '';

    tr.innerHTML = `
        <td>
            <a href="${escapeHtml(req.view_url)}" style="text-decoration:none;" onclick="event.stopPropagation();">
                <code style="color:#C9A84C; font-size:0.78rem; font-weight:700;">${escapeHtml(req.reference_number)}</code>
            </a>
        </td>
        <td>
            <span class="room-badge-formal">
                Room ${escapeHtml(req.room_number)}
            </span>
        </td>
        <td>
            <span style="font-size:0.82rem; white-space:nowrap;">
                ${escapeHtml(req.type_label)}
            </span>
        </td>
        <td style="max-width:240px;">
            <div style="font-size:0.82rem; color:#fff; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                ${photoIcon}
                ${escapeHtml(req.description)}
            </div>
            <div style="font-size:0.72rem; color:#8899AA;">By: ${escapeHtml(req.guest_name)}</div>
        </td>
        <td>
            <span class="badge bg-${escapeHtml(req.status_badge)}" style="border-radius:20px; font-size:0.72rem; padding:0.35rem 0.65rem; text-transform:uppercase; letter-spacing:0.5px;">
                ${escapeHtml(statusLower.replace('_', ' '))}
            </span>
        </td>
        <td>
            <span class="badge bg-${escapeHtml(req.priority_badge)}" style="border-radius:6px; font-size:0.72rem; padding:0.3rem 0.6rem; ${urgentBadgeStyle}">
                ${escapeHtml(prioLabel)}
            </span>
        </td>
        <td>
            <span class="ai-badge" title="AI Assessed">
                <i class="bi bi-stars me-1"></i>Pending
            </span>
        </td>
        <td style="font-size:0.78rem; color:#8899AA;">
            ${escapeHtml(req.department)}
        </td>
        <td style="font-size:0.75rem; color:#8899AA; white-space:nowrap;">
            ${escapeHtml(req.created_at_human)}
        </td>
        <td style="text-align:right;" onclick="event.stopPropagation();">
            <a href="${escapeHtml(req.view_url)}" class="btn-ghost px-2 py-1 text-decoration-none" style="font-size:0.75rem; white-space:nowrap;">
                Inspect &rarr;
            </a>
        </td>
    `;

    tableBody.insertBefore(tr, tableBody.firstChild);
}

// ── Live Food Order Table Row Prepender (if on /food/orders) ───────
function prependFoodOrderTableRow(ord) {
    const tableBody = document.querySelector('#foodOrdersTable tbody');
    if (!tableBody) return;

    if (tableBody.querySelector(`[data-ref="${CSS.escape ? CSS.escape(ord.reference_number) : ord.reference_number}"]`)) return;

    const emptyRow = document.getElementById('emptyRow');
    if (emptyRow) emptyRow.style.display = 'none';

    const tr = document.createElement('tr');
    tr.className = 'clickable-order-row highlight-new-row';
    tr.setAttribute('data-ref', ord.reference_number);
    tr.addEventListener('click', () => { window.location = ord.view_url; });
    tr.title = `Click row to view Order #${ord.reference_number}`;

    tr.innerHTML = `
        <td>
            <a href="${escapeHtml(ord.view_url)}" style="text-decoration:none;" onclick="event.stopPropagation();">
                <code style="color:#C9A84C; font-size:0.78rem; font-weight:700;">${escapeHtml(ord.reference_number)}</code>
            </a>
        </td>
        <td>
            <span class="room-badge-formal">
                Room ${escapeHtml(ord.room_number)}
            </span>
        </td>
        <td class="d-none d-xxl-table-cell" style="font-size:0.82rem; color:#fff;">
            ${escapeHtml(ord.guest_name || 'Room Guest')}
        </td>
        <td style="max-width:240px;">
            <div style="font-size:0.82rem; color:#CBD5E1; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                ${escapeHtml(ord.items_count)} item(s) ordered
            </div>
        </td>
        <td>
            <span style="color:#E8D08D; font-weight:700; font-size:0.88rem;">
                ₱${escapeHtml(ord.total_amount)}
            </span>
        </td>
        <td>
            <span class="badge bg-${escapeHtml(ord.status_badge || 'warning')} order-status-badge" style="border-radius:20px; font-size:0.72rem; padding:0.35rem 0.65rem; text-transform:uppercase; letter-spacing:0.5px;">
                ${escapeHtml(ord.status)}
            </span>
        </td>
        <td style="font-size:0.75rem; color:#8899AA; white-space:nowrap;">
            ${escapeHtml(ord.created_at_human)}
        </td>
        <td style="text-align:right;" onclick="event.stopPropagation();">
            <a href="${escapeHtml(ord.view_url)}" class="btn-ghost px-2 py-1 text-decoration-none" style="font-size:0.75rem; white-space:nowrap;">
                Ticket &rarr;
            </a>
        </td>
    `;

    tableBody.insertBefore(tr, tableBody.firstChild);
}

// ── Live Dashboard Stream Table Row Updater (if on /dashboard) ────
function updateDashboardStreamRow(row, req) {
    if (!row || !req) return;
    row.setAttribute('data-status', (req.status || '').toLowerCase());
    if (req.priority) row.setAttribute('data-priority', req.priority.toLowerCase());
    if (req.type) row.setAttribute('data-type', (req.type || '').toLowerCase());
    const searchStr = `${req.reference_number} Room ${req.room_number} ${req.description || ''} ${req.guest_name || ''} ${req.type || ''} ${req.status || ''} ${req.priority || ''}`.toLowerCase();
    row.setAttribute('data-search', searchStr);

    // Update Room (index 1)
    if (row.cells[1] && req.room_number) {
        row.cells[1].innerHTML = `
            <span style="background:rgba(201,168,76,0.12); border:1px solid rgba(201,168,76,0.25); border-radius:6px; padding:0.2rem 0.5rem; font-size:0.75rem; font-weight:700; color:#E8D08D;">
                Room ${escapeHtml(req.room_number)}
            </span>
        `;
    }

    // Update Description (index 3)
    if (row.cells[3] && req.description) {
        const hasPhotoIcon = req.has_photo ? '<span title="Guest attached photo evidence" style="color:#E8D08D; font-size:0.8rem; margin-right:3px;">📷</span>' : '';
        row.cells[3].innerHTML = `${hasPhotoIcon}${escapeHtml(req.description)}`;
    }

    // Update Status Cell (index 4)
    if (row.cells[4]) {
        const hasPulse = ['pending', 'assigned', 'in_progress'].includes(req.status);
        row.cells[4].innerHTML = `
            <span class="badge bg-${req.status_badge || 'warning'} d-inline-flex align-items-center gap-1 highlight-new-row">
                ${hasPulse ? '<span class="pulse-dot" style="background:#fff;"></span>' : ''}
                ${escapeHtml((req.status || '').replace('_', ' '))}
            </span>
        `;
    }

    // Update Priority Cell (index 5)
    if (row.cells[5] && req.priority) {
        row.cells[5].innerHTML = `<span class="badge bg-${req.priority_badge || 'secondary'}">${escapeHtml(req.priority)}</span>`;
    }

    // Update Time Cell (index 6)
    if (row.cells[6] && (req.updated_at_human || req.created_at_human)) {
        row.cells[6].textContent = req.updated_at_human || req.created_at_human;
    }

    // Highlight row
    row.classList.remove('highlight-new-row');
    void row.offsetWidth; // trigger reflow
    row.classList.add('highlight-new-row');

    if (typeof liveFilterDashboardStream === 'function') {
        liveFilterDashboardStream();
    }
}

// ── Live Dashboard Stream Table Row Prepender (if on /dashboard) ────
function prependDashboardStreamRow(req) {
    const tableBody = document.querySelector('#dashboardStreamTable tbody');
    if (!tableBody) return;

    // If row already exists in table, update it in place!
    const existingRow = tableBody.querySelector(`tr[data-ref="${req.reference_number}"]`);
    if (existingRow) {
        updateDashboardStreamRow(existingRow, req);
        return;
    }

    const empty = document.getElementById('emptyDashboardStreamPlaceholder');
    if (empty) empty.remove();
    const noMatch = document.getElementById('streamNoMatchRow');
    if (noMatch) noMatch.style.display = 'none';

    const tr = document.createElement('tr');
    tr.className = 'dashboard-stream-row highlight-new-row';
    tr.setAttribute('data-ref', req.reference_number);
    const searchStr = `${req.reference_number} Room ${req.room_number} ${req.description || ''} ${req.guest_name || ''} ${req.type || ''} ${req.status || ''} ${req.priority || ''}`.toLowerCase();
    tr.setAttribute('data-search', searchStr);
    tr.setAttribute('data-type', (req.type || '').toLowerCase());
    tr.setAttribute('data-status', (req.status || '').toLowerCase());
    tr.setAttribute('data-priority', (req.priority || '').toLowerCase());
    tr.style.cursor = 'pointer';
    tr.style.transition = 'all 0.15s ease';
    tr.setAttribute('onclick', `window.location='${req.view_url}'`);

    const hasPhotoIcon = req.has_photo ? '<i class="bi bi-camera me-1" title="Guest attached photo evidence" style="color:#E8D08D;"></i>' : '';
    const aiIcon = req.is_ai_triaged ? '<span style="font-size:0.72rem; color:var(--gold);" title="AI Triaged"><i class="bi bi-stars"></i></span>' : '';
    const prioLower = (req.priority || '').toLowerCase();
    const prioBadge = prioLower === 'urgent'
        ? '<span class="badge badge-status-urgent">Urgent</span>'
        : (prioLower === 'high'
            ? '<span class="badge badge-status-high">High</span>'
            : (req.priority ? `<span class="badge bg-${req.priority_badge || 'secondary'}">${escapeHtml(req.priority)}</span>` : '<span style="color:#8899AA;">—</span>'));
    const deptLabel = req.department || (req.type === 'maintenance' ? 'Maintenance' : 'Front Desk');

    tr.innerHTML = `
        <td style="white-space:nowrap;">
            <div class="d-inline-flex align-items-center gap-1 text-nowrap">
                <code style="color:var(--gold); font-size:0.78rem; font-weight:600;">${escapeHtml(req.reference_number)}</code>
                ${aiIcon}
            </div>
        </td>
        <td style="white-space:nowrap;">
            <span class="room-badge-formal">
                Room ${escapeHtml(req.room_number || '201')}
            </span>
        </td>
        <td style="white-space:nowrap;">
            <div class="d-flex flex-column align-items-start gap-1">
                <div class="d-inline-flex align-items-center gap-1.5 text-nowrap">
                    <i class="bi ${req.type === 'maintenance' ? 'bi-tools' : 'bi-bell'} text-gold" style="font-size:0.8rem;"></i>
                    <span style="font-size:0.8rem; font-weight:600; color:#F8FAFC;">${escapeHtml(req.type ? req.type.charAt(0).toUpperCase() + req.type.slice(1) : 'Service')}</span>
                </div>
                <span class="badge badge-status-dept" style="font-size:0.65rem;">${escapeHtml(deptLabel)}</span>
            </div>
        </td>
        <td style="max-width:180px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; font-size:0.8rem; color:#E2E8F0;">
            ${hasPhotoIcon}${escapeHtml(req.description || '')}
        </td>
        <td style="white-space:nowrap;">
            <span class="badge bg-${req.status_badge || 'warning'}">
                ${escapeHtml((req.status || 'pending').replace('_', ' '))}
            </span>
        </td>
        <td style="white-space:nowrap;">
            ${prioBadge}
        </td>
        <td style="color:#8899AA; font-size:0.75rem; white-space:nowrap;">
            <i class="bi bi-clock me-1"></i>${escapeHtml(req.created_at_human || 'Just now')}
        </td>
        <td style="white-space:nowrap;">
            <a href="${req.view_url}" class="action-link-formal">
                Inspect &rarr;
            </a>
        </td>
    `;

    tableBody.insertBefore(tr, tableBody.firstChild);

    if (typeof liveFilterDashboardStream === 'function') {
        liveFilterDashboardStream();
    }
}

// ── Live Dashboard Food Order Prepender (if on /dashboard) ─────────
function prependDashboardOrderCard(ord) {
    const list = document.getElementById('dashboardRecentOrdersList');
    if (!list) return;

    if (list.querySelector(`[data-ref="${ord.reference_number}"]`)) return;

    const empty = document.getElementById('emptyDashboardOrdersPlaceholder');
    if (empty) empty.remove();

    const card = document.createElement('div');
    card.className = 'p-3 mb-2 rounded-3 border transition-all dashboard-order-card highlight-new-row';
    card.setAttribute('data-ref', ord.reference_number);
    card.style.background = 'rgba(255,255,255,0.03)';
    card.style.borderColor = 'rgba(201,168,76,0.18)';
    card.style.cursor = 'pointer';
    card.setAttribute('onclick', `window.location='${ord.view_url}'`);

    card.innerHTML = `
        <div class="d-flex justify-content-between align-items-center gap-2 mb-1">
            <div class="d-flex align-items-center gap-1.5 min-w-0">
                <span class="room-badge-formal">Room ${escapeHtml(ord.room_number || '201')}</span>
                <span class="text-truncate" style="color:#8899AA; font-size:0.72rem;">(${escapeHtml(ord.guest_name || 'Guest')})</span>
            </div>
            <span class="badge bg-${ord.status_badge || 'warning'} order-status-badge flex-shrink-0">${escapeHtml(ord.status)}</span>
        </div>
        <div class="d-flex justify-content-between align-items-center gap-2 mt-2">
            <code class="text-truncate" style="color:#C9A84C; font-size:0.72rem;">${escapeHtml(ord.reference_number)}</code>
            <span class="text-nowrap flex-shrink-0" style="font-size:0.82rem; font-weight:700; color:#F7F2E7;">₱${ord.total_amount}</span>
        </div>
        <div class="d-flex justify-content-between align-items-center mt-2 pt-1" style="border-top:1px dashed rgba(255,255,255,0.08); font-size:0.7rem; color:#8899AA;">
            <span>${ord.items_count} item(s)</span>
            <span>${escapeHtml(ord.created_at_human || 'Just now')}</span>
        </div>
    `;

    list.insertBefore(card, list.firstChild);
}

// ── Topbar Notification Center ─────────────────────────────────────
const NOTIF_LIST_KEY = 'topbarNotifications_u{{ auth()->id() }}_{{ $userRole }}';
const NOTIF_COUNT_KEY = 'unreadNotifCount_u{{ auth()->id() }}_{{ $userRole }}';
let topbarNotifications = JSON.parse(sessionStorage.getItem(NOTIF_LIST_KEY) || '[]');
let unreadNotifCount = parseInt(sessionStorage.getItem(NOTIF_COUNT_KEY) || '0');
let currentNotifFilter = 'all';

function updateTopbarNotificationBadge() {
    const badge = document.getElementById('topbarNotifBadge');
    const pill = document.getElementById('topbarNotifUnreadPill');
    if (badge) {
        if (unreadNotifCount > 0) {
            badge.textContent = unreadNotifCount > 9 ? '9+' : unreadNotifCount;
            badge.classList.remove('d-none');
        } else {
            badge.classList.add('d-none');
        }
    }
    if (pill) {
        if (unreadNotifCount > 0) {
            pill.textContent = `${unreadNotifCount} new`;
            pill.classList.remove('d-none');
        } else {
            pill.classList.add('d-none');
        }
    }
    sessionStorage.setItem(NOTIF_COUNT_KEY, unreadNotifCount);
}

function addNotificationToDropdown(item) {
    if (!item) return;
    unreadNotifCount++;
    item.id = item.id || ('notif_' + Date.now() + '_' + Math.floor(Math.random() * 1000));
    // Avoid immediate duplicate
    if (topbarNotifications.length > 0 && topbarNotifications[0].title === item.title && topbarNotifications[0].description === item.description) {
        return;
    }
    topbarNotifications.unshift(item);
    if (topbarNotifications.length > 30) topbarNotifications.pop();
    sessionStorage.setItem(NOTIF_LIST_KEY, JSON.stringify(topbarNotifications));
    renderTopbarNotifications();
    updateTopbarNotificationBadge();
}

function filterTopbarNotifications(filter, btn) {
    currentNotifFilter = filter;
    document.querySelectorAll('.notif-filter-tab').forEach(tab => tab.classList.remove('active'));
    if (btn) btn.classList.add('active');
    renderTopbarNotifications();
}

function markAllNotificationsRead(e) {
    if (e) {
        e.preventDefault();
        e.stopPropagation();
    }
    unreadNotifCount = 0;
    topbarNotifications.forEach(n => n.unread = false);
    sessionStorage.setItem(NOTIF_LIST_KEY, JSON.stringify(topbarNotifications));
    renderTopbarNotifications();
    updateTopbarNotificationBadge();
}

function renderTopbarNotifications() {
    const list = document.getElementById('topbarNotifList');
    if (!list) return;

    let filtered = topbarNotifications;
    if (currentNotifFilter === 'requests') {
        filtered = topbarNotifications.filter(n => ['service', 'maintenance'].includes(n.type));
    } else if (currentNotifFilter === 'orders') {
        filtered = topbarNotifications.filter(n => n.type === 'order');
    } else if (currentNotifFilter === 'messages') {
        filtered = topbarNotifications.filter(n => n.type === 'message');
    }

    if (filtered.length === 0) {
        list.innerHTML = `
            <div id="topbarNotifEmpty" class="text-center py-4 px-3" style="color:#7D8696; font-size:0.78rem;">
                <i class="bi bi-bell-slash d-block mb-2" style="font-size:1.35rem; opacity:0.55;"></i>
                No ${currentNotifFilter === 'all' ? '' : currentNotifFilter} notifications yet.<br>New operational activity will appear here live.
            </div>
        `;
        return;
    }

    list.innerHTML = '';
    filtered.forEach(notif => {
        const a = document.createElement('a');
        a.href = notif.url || '#';
        a.className = `topbar-notif-item ${notif.unread ? 'unread' : ''}`;
        a.onclick = () => {
            notif.unread = false;
            sessionStorage.setItem(NOTIF_LIST_KEY, JSON.stringify(topbarNotifications));
            unreadNotifCount = topbarNotifications.filter(n => n.unread).length;
            updateTopbarNotificationBadge();
        };

        let iconClass = 'bi-bell';
        let iconBg = 'rgba(201,162,74,0.11)';
        let iconBorder = 'rgba(201,162,74,0.28)';
        let iconColor = '#E6CA7A';
        if (notif.type === 'maintenance') {
            iconClass = 'bi-tools';
            iconBg = 'rgba(56,189,248,0.10)';
            iconBorder = 'rgba(56,189,248,0.26)';
            iconColor = '#7DD3FC';
        } else if (notif.type === 'order') {
            iconClass = 'bi-cup-hot';
            iconBg = 'rgba(245,158,11,0.10)';
            iconBorder = 'rgba(245,158,11,0.28)';
            iconColor = '#FDE68A';
        } else if (notif.type === 'message') {
            iconClass = 'bi-chat-dots';
            iconBg = 'rgba(34,197,94,0.10)';
            iconBorder = 'rgba(34,197,94,0.26)';
            iconColor = '#86EFAC';
        }

        // Strip redundant emojis or duplicate icons from backend title strings
        const cleanTitle = String(notif.title || '')
            .replace(/[\u{1F300}-\u{1FAFF}\u{2600}-\u{27BF}\uFE0F]/gu, '')
            .replace(/\s*[•·]\s*/g, ' · ')
            .replace(/\s{2,}/g, ' ')
            .trim();

        a.innerHTML = `
            <div class="d-flex align-items-start gap-2.5">
                <span class="notif-icon-box" style="background:${iconBg}; border:1px solid ${iconBorder}; color:${iconColor};">
                    <i class="bi ${iconClass}"></i>
                </span>
                <div class="flex-grow-1" style="min-width:0;">
                    <div class="d-flex align-items-center justify-content-between gap-2 mb-1">
                        <span style="font-size:0.8rem; font-weight:600; color:#F6F1E7;" class="text-truncate">${escapeHtml(cleanTitle)}</span>
                        ${notif.priority ? `<span class="badge bg-${notif.priority_badge || 'secondary'} flex-shrink-0" style="font-size:0.62rem; padding:0.16rem 0.48rem !important;">${escapeHtml(notif.priority)}</span>` : ''}
                    </div>
                    <div style="font-size:0.75rem; color:#A8B1C0; line-height:1.4; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                        ${escapeHtml(notif.description || '')}
                    </div>
                    <div class="d-flex align-items-center justify-content-between mt-1.5" style="font-size:0.69rem; color:#6E788A;">
                        <span>${escapeHtml(notif.time || 'Just now')}</span>
                        <span style="color:#E6CA7A; font-weight:600;">Inspect &rarr;</span>
                    </div>
                </div>
            </div>
        `;
        list.appendChild(a);
    });
}

function clearAllNotifications(e) {
    if (e) {
        e.preventDefault();
        e.stopPropagation();
    }
    unreadNotifCount = 0;
    topbarNotifications = [];
    sessionStorage.removeItem(NOTIF_LIST_KEY);
    sessionStorage.removeItem(NOTIF_COUNT_KEY);
    renderTopbarNotifications();
    updateTopbarNotificationBadge();
}

document.addEventListener('DOMContentLoaded', () => {
    renderTopbarNotifications();
    updateTopbarNotificationBadge();
    const btn = document.getElementById('topbarNotifBtn');
    if (btn) {
        btn.addEventListener('show.bs.dropdown', () => {
            // Optional: keep badge until marked read or clear
        });
    }
});

// ── Badge Updater with Pulse Animation ─────────────────────────────
function updateBadge(id, count) {
    const el = document.getElementById(id);
    if (!el) return;

    if (count > 0) {
        const oldCount = parseInt(el.textContent) || 0;
        el.textContent = count;
        el.classList.remove('d-none');
        if (count > oldCount) {
            el.classList.remove('badge-pulse');
            void el.offsetWidth; // trigger reflow
            el.classList.add('badge-pulse');
        }
    } else {
        el.classList.add('d-none');
    }
}

// ── Live Background Polling Engine ─────────────────────────────────
let lastSeenRequestId = {{ \App\Models\ServiceRequest::max('id') ?? 0 }};
let lastSeenOrderId   = {{ \App\Models\FoodOrder::max('id') ?? 0 }};
let isInitialStaffPoll = true;
let isStaffPollInFlight = false;
let staffPollSignature = null;

function pollStaffNotifications(isManual = false) {
    if (document.hidden && !isManual) return;
    if (isStaffPollInFlight && !isManual) return;

    const manualBtn = document.getElementById('dashboardManualRefreshBtn');
    if (isManual && manualBtn) {
        manualBtn.classList.add('loading-spin');
        const icon = manualBtn.querySelector('i');
        if (icon) icon.classList.add('spin-icon');
    }

    isStaffPollInFlight = true;

    const isInitialParam = isInitialStaffPoll ? '&is_initial=1' : '';
    const lastCheckParam = staffPollSignature ? `&last_check=${encodeURIComponent(staffPollSignature)}` : '';

    fetch(`{{ route('notifications.poll') }}?last_request_id=${lastSeenRequestId}&last_order_id=${lastSeenOrderId}${isInitialParam}${lastCheckParam}`, {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(res => res.json())
    .then(data => {
        isStaffPollInFlight = false;
        if (isManual && manualBtn) {
            manualBtn.classList.remove('loading-spin');
            const icon = manualBtn.querySelector('i');
            if (icon) icon.classList.remove('spin-icon');
        }

        if (!data || !data.success) return;

        if (data.signature) {
            staffPollSignature = data.signature;
        }

        // Fast path: if database state hasn't changed, skip DOM re-render
        if (data.changed === false) {
            return;
        }

        // Populate initial notification alerts if storage is currently empty
        if (isInitialStaffPoll && data.initial_alerts && data.initial_alerts.length > 0 && topbarNotifications.length === 0) {
            topbarNotifications = data.initial_alerts;
            unreadNotifCount = topbarNotifications.filter(n => n.unread).length;
            sessionStorage.setItem(NOTIF_LIST_KEY, JSON.stringify(topbarNotifications));
            renderTopbarNotifications();
            updateTopbarNotificationBadge();
        }
        isInitialStaffPoll = false;

        // 1. Update Real-time Badge Counts & Dashboard KPIs
        if (data.counts) {
            updateBadge('sidebarAllRequestsBadge', data.counts.pending_requests);
            updateBadge('sidebarOrdersBadge', data.counts.pending_orders);
            updateBadge('sidebarKitchenOrdersBadge', data.counts.pending_orders);
            updateBadge('sidebarMaintBadge', data.counts.maint_active);
            updateBadge('mobileOrdersBadge', data.counts.pending_orders);

            // Dashboard KPI cards live updates (if on dashboard)
            const kpiPending = document.getElementById('kpiPendingCount');
            if (kpiPending && data.counts.pending_requests_24h !== undefined) {
                kpiPending.textContent = data.counts.pending_requests_24h;
            }
            const kpiInProgress = document.getElementById('kpiInProgressCount');
            if (kpiInProgress && data.counts.in_progress_requests_24h !== undefined) {
                kpiInProgress.textContent = data.counts.in_progress_requests_24h;
            }
            const kpiResolved = document.getElementById('kpiResolvedTodayCount');
            if (kpiResolved && data.counts.resolved_today_24h !== undefined) {
                kpiResolved.textContent = data.counts.resolved_today_24h;
            }
            const kpiOrders = document.getElementById('kpiActiveOrdersCount');
            if (kpiOrders && data.counts.pending_orders !== undefined) {
                kpiOrders.textContent = data.counts.pending_orders;
            }
            const kpiUrgent = document.getElementById('kpiUrgentCount');
            if (kpiUrgent && data.counts.urgent_requests_24h !== undefined) {
                kpiUrgent.textContent = data.counts.urgent_requests_24h;
            }
            const kpiAi = document.getElementById('kpiAiTriagedCount');
            if (kpiAi && data.counts.ai_triaged_24h !== undefined) {
                kpiAi.textContent = data.counts.ai_triaged_24h;
            }

            // Dashboard Toolbar header counters
            const btnTotal24h = document.getElementById('btnTotal24hCount');
            if (btnTotal24h && data.counts.total_requests_24h !== undefined) {
                btnTotal24h.textContent = data.counts.total_requests_24h;
            }
            const btnUrgent = document.getElementById('btnUrgentCount');
            if (btnUrgent && data.counts.urgent_requests_24h !== undefined) {
                btnUrgent.textContent = data.counts.urgent_requests_24h;
            }
            const btnKitchen = document.getElementById('btnKitchenQueueCount');
            if (btnKitchen && data.counts.pending_orders !== undefined) {
                btnKitchen.textContent = data.counts.pending_orders;
            }

            // Sidebar Urgent and Mobile Request badges
            const urgentEl = document.getElementById('sidebarUrgentBadge');
            if (urgentEl) {
                if (data.counts.urgent_requests > 0) {
                    urgentEl.textContent = `${data.counts.urgent_requests} Urgent`;
                    urgentEl.classList.remove('d-none');
                } else {
                    urgentEl.classList.add('d-none');
                }
            }

            const mobileReqEl = document.getElementById('mobileRequestsBadge');
            if (mobileReqEl) {
                const totalReqs = data.counts.urgent_requests > 0 ? data.counts.urgent_requests : data.counts.pending_requests;
                if (totalReqs > 0) {
                    mobileReqEl.textContent = totalReqs;
                    mobileReqEl.className = `mobile-nav-badge ${data.counts.urgent_requests > 0 ? 'bg-danger' : 'bg-warning'}`;
                    mobileReqEl.classList.remove('d-none');
                } else {
                    mobileReqEl.classList.add('d-none');
                }
            }
        }

        // 2. Handle New Guest Requests
        if (data.new_requests && data.new_requests.length > 0) {
            let hasUrgent = false;
            data.new_requests.forEach(req => {
                if (req.priority === 'Urgent') hasUrgent = true;
                showNotificationToast(req);
                prependRequestTableRow(req);
                prependDashboardStreamRow(req);
                addNotificationToDropdown({
                    type: req.type,
                    title: `Room ${req.room_number} • ${req.type === 'maintenance' ? '🔧 Maintenance' : '🛎️ Service'}`,
                    description: req.description,
                    priority: req.priority,
                    priority_badge: req.priority_badge,
                    time: req.created_at_human || 'Just now',
                    url: req.view_url,
                    unread: true
                });
            });

            playStaffAlertChime(hasUrgent);
        }
        if (data.latest_request_id !== undefined && data.latest_request_id > lastSeenRequestId) {
            lastSeenRequestId = data.latest_request_id;
        }

        // 3. Handle New Food Orders
        if (data.new_orders && data.new_orders.length > 0) {
            data.new_orders.forEach(ord => {
                showOrderToast(ord);
                prependFoodOrderTableRow(ord);
                prependDashboardOrderCard(ord);
                addNotificationToDropdown({
                    type: 'order',
                    title: `Room ${ord.room_number} • 🍽️ Food Order`,
                    description: `${ord.items_count} item(s) ordered (₱${ord.total_amount})`,
                    priority: null,
                    time: ord.created_at_human || 'Just now',
                    url: ord.view_url,
                    unread: true
                });
            });
            playStaffAlertChime(false);
        }
        if (data.latest_order_id !== undefined && data.latest_order_id > lastSeenOrderId) {
            lastSeenOrderId = data.latest_order_id;
        }

        // 4. Handle Live Status & Ticket Updates (e.g. In Progress, Resolved, Closed, Assigned)
        if (data.updated_requests && data.updated_requests.length > 0) {
            data.updated_requests.forEach(req => {
                // Update row in requestsTable (on /requests)
                const row = document.querySelector(`#requestsTable tr[data-ref="${req.reference_number}"]`);
                if (row) {
                    const statusBadge = row.querySelector('.badge[class*="bg-"]');
                    if (statusBadge && statusBadge.textContent.trim().toLowerCase() !== req.status.replace('_', ' ').toLowerCase()) {
                        statusBadge.textContent = req.status.replace('_', ' ');
                        statusBadge.className = `badge bg-${req.status_badge} highlight-new-row`;
                    }
                }

                // Update or prepend row in dashboardStreamTable (on /dashboard)
                const dashRow = document.querySelector(`#dashboardStreamTable tr[data-ref="${req.reference_number}"]`);
                if (dashRow) {
                    const oldStatus = dashRow.getAttribute('data-status');
                    updateDashboardStreamRow(dashRow, req);
                    if (oldStatus && oldStatus !== req.status.toLowerCase()) {
                        addNotificationToDropdown({
                            type: req.type || 'service',
                            title: `Room ${req.room_number} • Status Update`,
                            description: `Request #${req.reference_number} changed to ${req.status_label || req.status}`,
                            priority: req.priority,
                            priority_badge: req.priority_badge,
                            time: req.updated_at_human || 'Just now',
                            url: req.view_url,
                            unread: true
                        });
                    }
                } else {
                    // Prepend newly active or updated request to dashboard stream
                    prependDashboardStreamRow(req);
                }
            });
        }

        // 5. Handle Live Status Updates for Food Orders (e.g. Cooking, Ready, Delivered)
        if (data.updated_orders && data.updated_orders.length > 0) {
            data.updated_orders.forEach(ord => {
                const row = document.querySelector(`#foodOrdersTable tr[data-ref="${ord.reference_number}"]`);
                if (row) {
                    const badge = row.querySelector('.order-status-badge');
                    if (badge && badge.textContent.trim().toLowerCase() !== ord.status.toLowerCase()) {
                        badge.textContent = ord.status;
                        badge.className = `badge bg-${ord.status_badge} order-status-badge highlight-new-row`;
                    }
                }

                const dashCard = document.querySelector(`#dashboardRecentOrdersList .dashboard-order-card[data-ref="${ord.reference_number}"]`);
                if (dashCard) {
                    const badge = dashCard.querySelector('.order-status-badge');
                    if (badge && badge.textContent.trim().toLowerCase() !== ord.status.toLowerCase()) {
                        badge.textContent = ord.status;
                        badge.className = `badge bg-${ord.status_badge} order-status-badge highlight-new-row`;
                    }
                }
            });
        }

        // 6. Handle Live In-Ticket Guest Messages (Live alert toast & notification dropdown)
        if (data.recent_guest_messages && data.recent_guest_messages.length > 0) {
            if (!window.seenGuestMessageAlerts) window.seenGuestMessageAlerts = new Set();
            data.recent_guest_messages.forEach(msg => {
                if (!window.seenGuestMessageAlerts.has(msg.id)) {
                    window.seenGuestMessageAlerts.add(msg.id);
                    if (!window.location.href.includes(`/requests/${msg.request_id}`)) {
                        showGuestMessageToast(msg);
                        playStaffAlertChime(false);
                        addNotificationToDropdown({
                            type: 'message',
                            title: `Room ${msg.room_number} • 💬 Guest Message`,
                            description: msg.message,
                            priority: null,
                            time: 'Just now',
                            url: msg.view_url,
                            unread: true
                        });
                    }
                }
            });
        }
    })
    .catch(err => {
        isStaffPollInFlight = false;
        if (isManual && manualBtn) {
            manualBtn.classList.remove('loading-spin');
            const icon = manualBtn.querySelector('i');
            if (icon) icon.classList.remove('spin-icon');
        }
    });
}

// Persist Per-Account Configuration to Database (Theme, Sidebar, Madi AI Bot)
let __mpPrefBadgeTimer = null;
window.saveAccountPreferences = function(payload) {
    return fetch('{{ route("account.preferences.update") }}', {
        method: 'POST',
        keepalive: true,
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify(payload)
    })
    .then(r => r.json())
    .then(data => {
        if (data && data.success) {
            const savedBadge = document.getElementById('accountPrefSavedBadge');
            if (savedBadge) {
                savedBadge.classList.remove('d-none');
                clearTimeout(__mpPrefBadgeTimer);
                __mpPrefBadgeTimer = setTimeout(() => savedBadge.classList.add('d-none'), 2200);
            }
        }
        return data;
    })
    .catch(() => null);
};

window.syncAccountBotUI = function(enabled) {
    const chk = document.getElementById('accountBotToggle');
    const st = document.getElementById('accountBotStatusText');
    if (chk) chk.checked = !!enabled;
    if (st) {
        st.textContent = enabled ? 'ON' : 'OFF';
        st.className = 'badge ' + (enabled ? 'badge-status-resolved' : 'badge-status-dept');
    }
    const ticketChk = document.getElementById('botAutoReplyToggle');
    const ticketBadge = document.getElementById('botStatusBadge');
    const ticketTxt = document.getElementById('botStatusText');
    if (ticketChk) ticketChk.checked = !!enabled;
    if (ticketBadge && ticketTxt) {
        ticketBadge.className = 'badge ' + (enabled ? 'badge-status-resolved' : 'badge-status-dept');
        ticketTxt.textContent = enabled ? 'Madi AI: ON' : 'Madi AI: OFF';
    }
};

window.toggleAccountBotPreference = function(checkbox) {
    if (!checkbox) return;
    const enabled = !!checkbox.checked;
    checkbox.disabled = true;
    window.syncAccountBotUI(enabled);
    window.saveAccountPreferences({ bot_auto_reply_enabled: enabled }).then(data => {
        checkbox.disabled = false;
        if (data && data.success && data.preferences) {
            window.syncAccountBotUI(data.preferences.bot_auto_reply_enabled);
        } else {
            window.syncAccountBotUI(!enabled);
        }
    });
};

window.toggleSidebarCollapse = function() {
    const collapsed = document.documentElement.classList.toggle('sidebar-collapsed');
    try { localStorage.setItem('guestlink_sidebar_collapsed', collapsed ? '1' : '0'); } catch (e) {}
    window.saveAccountPreferences({ sidebar_collapsed: collapsed });
};

window.manualRefreshStream = function(btn) {
    pollStaffNotifications(true);
};

// Global Light / Dark Mode Switcher with Directional Sunrise / Twilight Animation
let __mpStaffActiveVt = null;
let __mpStaffThemeFallbackTimer = null;
window.toggleGuestLinkTheme = function() {
    const root = document.documentElement;
    const btn = document.getElementById('btnStaffThemeToggle');
    const next = root.getAttribute('data-theme') === 'light' ? 'dark' : 'light';
    const goingDark = next === 'dark';

    const applyTheme = () => {
        root.setAttribute('data-theme', next);
        try { localStorage.setItem('guestlink_theme', next); } catch (e) {}
        const lbl = document.getElementById('staffThemeLabel');
        if (lbl) lbl.textContent = next === 'light' ? 'Dark' : 'Light';
        const pill = document.getElementById('accountThemeModePill');
        if (pill) pill.textContent = next === 'light' ? 'Light Mode' : 'Dark Mode';
        if (typeof window.saveAccountPreferences === 'function') {
            window.saveAccountPreferences({ theme_preference: next });
        }
    };

    if (btn) {
        btn.classList.remove('is-popping');
        void btn.offsetWidth;
        btn.classList.add('is-popping');
    }

    const reducedMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (!reducedMotion && typeof document.startViewTransition === 'function') {
        if (__mpStaffActiveVt && typeof __mpStaffActiveVt.skipTransition === 'function') {
            try { __mpStaffActiveVt.skipTransition(); } catch (e) {}
        }
        const rect = btn ? btn.getBoundingClientRect() : null;
        const cx = rect ? rect.left + rect.width / 2 : window.innerWidth - 180;
        const cy = rect ? rect.top + rect.height / 2 : 34;
        const maxR = Math.ceil(Math.hypot(
            Math.max(cx, window.innerWidth - cx),
            Math.max(cy, window.innerHeight - cy)
        ));

        root.classList.toggle('theme-to-dark', goingDark);
        const vt = document.startViewTransition(() => applyTheme());
        __mpStaffActiveVt = vt;
        vt.ready.then(() => {
            if (__mpStaffActiveVt !== vt) return;
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
            if (__mpStaffActiveVt === vt) {
                root.classList.remove('theme-to-dark');
                __mpStaffActiveVt = null;
            }
        });
        return;
    }

    root.classList.add('theme-switching-fallback');
    applyTheme();
    clearTimeout(__mpStaffThemeFallbackTimer);
    __mpStaffThemeFallbackTimer = setTimeout(() => root.classList.remove('theme-switching-fallback'), 480);
};
(function syncStaffThemeLabel() {
    const lbl = document.getElementById('staffThemeLabel');
    if (lbl) lbl.textContent = document.documentElement.getAttribute('data-theme') === 'light' ? 'Dark' : 'Light';
})();

// Start live polling every 8 seconds (pauses automatically when tab is hidden; instant sync on return)
setInterval(() => {
    if (!document.hidden) pollStaffNotifications();
}, 8000);
document.addEventListener('visibilitychange', () => {
    if (!document.hidden) pollStaffNotifications();
});
</script>
{{-- Madison Park emoji-to-icon pass (delete this line to roll back) --}}
<script src="{{ asset('js/madison-icons.js') }}?v={{ @filemtime(public_path('js/madison-icons.js')) }}" defer></script>
@stack('scripts')
</body>
</html>
