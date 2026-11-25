<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="description" content="GuestLink – Madison Park Hotel Digital Concierge">
    <title>@yield('title', 'Guest Services') – Madison Park Hotel</title>
    <script>(function(){try{var t=localStorage.getItem('guestlink_theme');if(t==='light'||t==='dark'){document.documentElement.setAttribute('data-theme',t);}}catch(e){}})();</script>
    
    {{-- Fast CDN Preconnects --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link rel="preconnect" href="https://cdn.tailwindcss.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,600;0,700;1,600&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    {{-- Icons --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="icon" type="image/png" href="{{ asset('images/hotel/madison-logo.png') }}">

    {{-- Tailwind CSS Play Engine with Madison Park 5-Star Luxury Theme --}}
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        slate: {
                            800: '#1e293b',
                            900: '#0f172a',
                            950: '#020617',
                        },
                        amber: {
                            200: '#fde68a',
                            300: '#fcd34d',
                            400: '#fbbf24',
                            500: '#f59e0b',
                            600: '#d97706',
                        },
                        gold: {
                            DEFAULT: '#C9A84C',
                            light: '#E0C06E',
                            pale: 'rgba(201,168,76,0.15)',
                        }
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                        serif: ['"Playfair Display"', 'serif'],
                    }
                }
            }
        };
    </script>

    <style>
        :root {
            --sat: env(safe-area-inset-top, 0px);
            --sab: env(safe-area-inset-bottom, 0px);
        }
        * {
            box-sizing: border-box;
            -webkit-tap-highlight-color: transparent;
        }
        body {
            font-family: 'Inter', sans-serif;
            background-color: #05080F;
            color: #F6F1E7;
            margin: 0;
            padding: 0;
            overflow-x: clip;
            -webkit-font-smoothing: antialiased;
        }
        a {
            text-decoration: none !important;
        }
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }

        /* Floating Toast Styles */
        .guest-live-toast {
            pointer-events: auto;
            background: rgba(14, 21, 35, 0.96);
            border: 1px solid rgba(201, 162, 74, 0.38);
            border-radius: 14px;
            padding: 0.85rem 1rem;
            box-shadow: 0 20px 44px rgba(0, 0, 0, 0.72), inset 0 1px 0 rgba(255, 255, 255, 0.06);
            backdrop-filter: blur(18px);
            -webkit-backdrop-filter: blur(18px);
            display: flex;
            align-items: center;
            gap: 0.75rem;
            transform: translateY(-20px);
            opacity: 0;
            transition: all 0.35s cubic-bezier(0.22, 1, 0.36, 1);
        }
        .guest-live-toast.resolved {
            border-color: rgba(34, 197, 94, 0.45);
        }
        .guest-live-toast.show {
            transform: translateY(0);
            opacity: 1;
        }
    </style>
    {{-- Madison Park brand layer (remove this line to roll back the theme) --}}
    <link rel="stylesheet" href="{{ asset('css/madison-theme.css') }}?v={{ @filemtime(public_path('css/madison-theme.css')) }}">
    @stack('styles')
</head>
<body class="bg-[#05080F] text-[#F6F1E7] min-h-dvh flex flex-col selection:bg-amber-500/30">

    {{-- Main Mobile App Viewport --}}
    <main class="w-full flex-1">
        @yield('content')
    </main>

    {{-- Madison AI Concierge Floating Assistant & Interactive Drawer --}}
    @include('guest.partials.ai-concierge-drawer')

    {{-- Floating Live Status Alert Banner --}}
    <div id="guestToastContainer" style="position:fixed; top:max(1rem, env(safe-area-inset-top, 1rem)); left:50%; transform:translateX(-50%); z-index:99999; width:min(92%, 440px); pointer-events:none; display:flex; flex-direction:column; gap:10px;"></div>

    {{-- Audio Chime Synthesizer & Real-Time Polling Engine --}}
    <script>
    window.escapeHtml = window.escapeHtml || function(str) {
        if (str === null || str === undefined) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    };

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

    // ── Web Audio API 5-Star Bell Chime (Singleton Context) ─────────
    function playGuestAlertChime(isResolved) {
        try {
            const ctx = window.__getGuestLinkAudioCtx();
            if (!ctx) return;
            const now = ctx.currentTime;

            if (isResolved) {
                [523.25, 659.25, 783.99, 1046.50].forEach((freq, idx) => {
                    const osc = ctx.createOscillator();
                    const gain = ctx.createGain();
                    osc.type = 'sine';
                    osc.frequency.setValueAtTime(freq, now + (idx * 0.12));
                    gain.gain.setValueAtTime(0.2, now + (idx * 0.12));
                    gain.gain.exponentialRampToValueAtTime(0.001, now + (idx * 0.12) + 0.6);
                    osc.connect(gain);
                    gain.connect(ctx.destination);
                    osc.start(now + (idx * 0.12));
                    osc.stop(now + (idx * 0.12) + 0.65);
                });
            } else {
                [587.33, 880.00].forEach((freq, idx) => {
                    const osc = ctx.createOscillator();
                    const gain = ctx.createGain();
                    osc.type = 'sine';
                    osc.frequency.setValueAtTime(freq, now + (idx * 0.14));
                    gain.gain.setValueAtTime(0.18, now + (idx * 0.14));
                    gain.gain.exponentialRampToValueAtTime(0.001, now + (idx * 0.14) + 0.5);
                    osc.connect(gain);
                    gain.connect(ctx.destination);
                    osc.start(now + (idx * 0.14));
                    osc.stop(now + (idx * 0.14) + 0.55);
                });
            }
        } catch (e) {}
    }

    // ── Show Luxury Floating Guest Toast (XSS-Safe + Light/Dark Ready) ──
    let seenGuestNotifIds = new Set();

    function showGuestToast(notif) {
        if (!notif || !notif.id || seenGuestNotifIds.has(notif.id)) return;
        seenGuestNotifIds.add(notif.id);

        const container = document.getElementById('guestToastContainer');
        if (!container) return;

        const toast = document.createElement('div');
        toast.className = `guest-live-toast ${notif.is_resolved ? 'resolved' : ''}`;

        const iconClass = notif.is_resolved ? 'bi-check-circle' : (notif.type === 'order' ? 'bi-cup-hot' : 'bi-bell');
        const iconColor = notif.is_resolved ? '#86efac' : '#E6CB7E';

        toast.innerHTML = `
            <div style="display:flex; flex-direction:column; gap:0.5rem; width:100%; min-width:0;">
                <div style="display:flex; align-items:center; gap:0.6rem; min-width:0;">
                    <div class="mp-icon" style="width:32px;height:32px;font-size:0.9rem;color:${iconColor};flex-shrink:0;"><i class="bi ${iconClass}"></i></div>
                    <span class="glt-title" style="font-weight:700; font-size:0.82rem; color:#fff; flex:1 1 auto; min-width:0; line-height:1.25;">
                        ${escapeHtml(notif.title)}
                    </span>
                    <span class="glt-badge" style="padding:0.15rem 0.5rem; border-radius:9999px; background:${notif.is_resolved ? 'rgba(34,197,94,0.18)' : 'rgba(201,168,76,0.18)'}; color:${notif.is_resolved ? '#86efac' : '#E8D08D'}; border:1px solid ${notif.is_resolved ? 'rgba(34,197,94,0.4)' : 'rgba(201,168,76,0.4)'}; font-size:0.64rem; font-weight:700; white-space:nowrap; flex-shrink:0; line-height:1.2;">
                        Live
                    </span>
                    <button type="button" aria-label="Dismiss notification" onclick="this.closest('.guest-live-toast')?.remove()" style="background:transparent;border:0;color:#94A3B8;padding:0.15rem;line-height:1;cursor:pointer;flex-shrink:0;font-size:0.85rem;">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
                <div style="display:flex; align-items:flex-end; justify-content:space-between; gap:0.65rem; padding-left:2.6rem;">
                    <div class="glt-msg" style="font-size:0.75rem; color:#CBD5E1; line-height:1.38; flex:1 1 auto; min-width:0;">
                        ${escapeHtml(notif.message)}
                    </div>
                    <a href="${escapeHtml(notif.url)}" class="glt-btn" style="padding:0.32rem 0.65rem; font-size:0.72rem; font-weight:600; white-space:nowrap; flex-shrink:0; border-radius:8px; background:rgba(255,255,255,0.1); color:#fff; border:1px solid rgba(255,255,255,0.2); text-decoration:none;">
                        View &rarr;
                    </a>
                </div>
            </div>
        `;

        container.appendChild(toast);
        setTimeout(() => toast.classList.add('show'), 30);

        playGuestAlertChime(notif.is_resolved);

        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 400);
        }, 8000);
    }

    // ── Real-Time Polling Engine for Guest Portal (Every 3.5 Seconds) ────
    @php
        $activeNavToken = $token ?? (isset($room) ? $room->qr_token : 'SMFnULzAzhY8LcBx0BHy0IGSuRybuqwf');
    @endphp
    const layoutPollTrackRef = new URLSearchParams(window.location.search).get('ref') || '';
    let previousTrackedStatus = null;
    let guestPortalLastUpdated = null;
    let isGuestPollInFlight = false;

    function pollGuestPortal() {
        if (document.hidden) return;
        if (window.disableLayoutPolling) return;
        if (isGuestPollInFlight) return;

        isGuestPollInFlight = true;

        let pollUrl = `{{ route('guest.poll', $activeNavToken) }}?ref=${encodeURIComponent(layoutPollTrackRef)}`;
        if (guestPortalLastUpdated) {
            pollUrl += `&last_updated=${encodeURIComponent(guestPortalLastUpdated)}`;
        }

        fetch(pollUrl, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(res => res.json())
        .then(data => {
            isGuestPollInFlight = false;
            if (!data || !data.success) return;

            if (data.changed === false) return;

            if (data.last_updated) {
                guestPortalLastUpdated = data.last_updated;
            }

            window.dispatchEvent(new CustomEvent('guestStatusUpdated', { detail: data }));

            if (data.live_notification) {
                showGuestToast(data.live_notification);
            }

            if (data.tracked_item) {
                const newStatus = data.tracked_item.status;
                if (previousTrackedStatus && previousTrackedStatus !== newStatus) {
                    showGuestToast({
                        id: `status_${data.tracked_item.reference_number}_${newStatus}`,
                        type: data.tracked_item.type,
                        reference_number: data.tracked_item.reference_number,
                        status: newStatus,
                        status_label: data.tracked_item.status_label,
                        title: data.tracked_item.is_resolved ? 'Status: Resolved' : `Status: ${data.tracked_item.status_label}`,
                        message: `Your ${data.tracked_item.type === 'order' ? 'food order' : 'request'} is now ${data.tracked_item.status_label}.`,
                        url: window.location.href,
                        is_resolved: data.tracked_item.is_resolved,
                    });
                }
                previousTrackedStatus = newStatus;
            }
        })
        .catch(err => {
            isGuestPollInFlight = false;
        });
    }

    let __mpGuestActiveVt = null;
    let __mpGuestThemeFallbackTimer = null;
    window.toggleGuestLinkTheme = function() {
        const root = document.documentElement;
        const btn = document.getElementById('btnGuestThemeToggle');
        const next = root.getAttribute('data-theme') === 'light' ? 'dark' : 'light';
        const goingDark = next === 'dark';

        const applyTheme = () => {
            root.setAttribute('data-theme', next);
            try { localStorage.setItem('guestlink_theme', next); } catch (e) {}
        };

        if (btn) {
            btn.classList.remove('is-popping');
            void btn.offsetWidth;
            btn.classList.add('is-popping');
        }

        const reducedMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        if (!reducedMotion && typeof document.startViewTransition === 'function') {
            if (__mpGuestActiveVt && typeof __mpGuestActiveVt.skipTransition === 'function') {
                try { __mpGuestActiveVt.skipTransition(); } catch (e) {}
            }
            const rect = btn ? btn.getBoundingClientRect() : null;
            const cx = rect ? rect.left + rect.width / 2 : window.innerWidth - 52;
            const cy = rect ? rect.top + rect.height / 2 : 30;
            const maxR = Math.ceil(Math.hypot(
                Math.max(cx, window.innerWidth - cx),
                Math.max(cy, window.innerHeight - cy)
            ));

            root.classList.toggle('theme-to-dark', goingDark);
            const vt = document.startViewTransition(() => applyTheme());
            __mpGuestActiveVt = vt;
            vt.ready.then(() => {
                if (__mpGuestActiveVt !== vt) return;
                const clipPath = goingDark
                    ? [`circle(${maxR}px at ${cx}px ${cy}px)`, `circle(0px at ${cx}px ${cy}px)`]
                    : [`circle(0px at ${cx}px ${cy}px)`, `circle(${maxR}px at ${cx}px ${cy}px)`];
                document.documentElement.animate(
                    { clipPath },
                    {
                        duration: 450,
                        easing: 'cubic-bezier(0.25, 0.85, 0.3, 1)',
                        fill: 'forwards',
                        pseudoElement: goingDark ? '::view-transition-old(root)' : '::view-transition-new(root)'
                    }
                );
            }).catch(() => {});
            vt.finished.finally(() => {
                if (__mpGuestActiveVt === vt) {
                    root.classList.remove('theme-to-dark');
                    __mpGuestActiveVt = null;
                }
            });
            return;
        }

        root.classList.add('theme-switching-fallback');
        applyTheme();
        clearTimeout(__mpGuestThemeFallbackTimer);
        __mpGuestThemeFallbackTimer = setTimeout(() => root.classList.remove('theme-switching-fallback'), 480);
    };

    setInterval(pollGuestPortal, 8000);
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) pollGuestPortal();
    });
    </script>
    {{-- Madison Park emoji-to-icon pass (delete this line to roll back) --}}
<script src="{{ asset('js/madison-icons.js') }}?v={{ @filemtime(public_path('js/madison-icons.js')) }}" defer></script>
@stack('scripts')
</body>
</html>
