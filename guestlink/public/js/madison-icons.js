/* ==========================================================================
   MADISON PARK – EMOJI → LINE-ICON PASS  (v1.0)
   Replaces emoji with Bootstrap Icons so the UI reads as a hotel brand,
   not a chat app. Works on dynamic content too (polling, drawers, toasts).

   ROLLBACK: delete the <script src=".../madison-icons.js"> tag in
             layouts/guest.blade.php and layouts/app.blade.php.
   Opt-out for an element: add  data-keep-emoji  to it (or a parent).
   Unmapped emoji are left untouched on purpose (e.g. admin-chosen icons).
   ========================================================================== */
(function () {
    'use strict';

    var MAP = {
        '🛎': 'bi-bell', '🍽': 'bi-cup-hot', '🍳': 'bi-egg-fried', '👨‍🍳': 'bi-person-badge',
        '🔧': 'bi-wrench-adjustable', '🛠': 'bi-tools', '🤖': 'bi-stars', '✨': 'bi-stars',
        '🚨': 'bi-exclamation-triangle', '⚠': 'bi-exclamation-triangle', '✅': 'bi-check-circle',
        '📋': 'bi-clipboard-check', '📩': 'bi-envelope', '📝': 'bi-pencil-square', '👤': 'bi-person',
        '⚡': 'bi-lightning-charge', '💬': 'bi-chat-dots', '🚪': 'bi-door-open', '🙏': 'bi-heart',
        '⏱': 'bi-stopwatch', '⏳': 'bi-hourglass-split', '🕒': 'bi-clock', '🔍': 'bi-search',
        '📭': 'bi-inbox', '📷': 'bi-camera', '📸': 'bi-camera', '🏢': 'bi-building', '🏨': 'bi-building',
        '🏷': 'bi-tag', '🔒': 'bi-lock', '🔓': 'bi-unlock', '🚀': 'bi-send', '📱': 'bi-phone',
        '📅': 'bi-calendar3', '📦': 'bi-archive', '🔄': 'bi-arrow-repeat', '🔕': 'bi-bell-slash',
        '👑': 'bi-award', '🏛': 'bi-bank', '🧹': 'bi-brush', '💧': 'bi-droplet', '🧴': 'bi-droplet-half',
        '🛏': 'bi-moon-stars', '🛌': 'bi-moon-stars', '🗑': 'bi-trash', '📶': 'bi-wifi', '🍎': 'bi-apple',
        '❄': 'bi-snow', '❌': 'bi-x-circle', '🚰': 'bi-droplet', '🚿': 'bi-droplet', '🚫': 'bi-slash-circle',
        '📺': 'bi-tv', '🔌': 'bi-plug', '💡': 'bi-lightbulb', '🌟': 'bi-star', '💼': 'bi-briefcase', '🍴': 'bi-cup-hot',
        /* Request Categories & Hotel Amenities */
        '🔑': 'bi-key', '🗝': 'bi-key', '🪑': 'bi-lamp', '🛋': 'bi-lamp',
        '📡': 'bi-wifi', '👕': 'bi-basket2', '🧺': 'bi-basket2',
        '⏰': 'bi-alarm', '⏲': 'bi-alarm', '🕰': 'bi-clock', '⌚': 'bi-watch',
        '❓': 'bi-question-circle', '❔': 'bi-question-circle', '❗': 'bi-exclamation-circle',
        '🛁': 'bi-layers', '🧻': 'bi-layers', '🧼': 'bi-droplet-half',
        '☎': 'bi-telephone', '📞': 'bi-telephone', '☕': 'bi-cup-hot', '🍷': 'bi-cup-straw',
        '🚗': 'bi-car-front', '🚕': 'bi-car-front', '🏊': 'bi-water', '🔥': 'bi-fire',
        '🌡': 'bi-thermometer-half', '💨': 'bi-wind', '🔊': 'bi-volume-up', '🔇': 'bi-volume-mute',
        '🎁': 'bi-gift', '🎂': 'bi-cake2', '🎉': 'bi-stars', '💊': 'bi-capsule', '🛡': 'bi-shield-check',
        '🔩': 'bi-nut', '🪟': 'bi-window', '🍲': 'bi-cup-hot', '🍨': 'bi-cup-straw', '🎪': 'bi-stars', '😊': 'bi-emoji-smile'
    };
    var COLORS = { '🟢': '#4ADE80', '🟡': '#FACC15', '🟠': '#FB923C', '🔴': '#F87171' };

    var EMOJI_RE = /(\p{Extended_Pictographic}(?:\uFE0F|\u200D\p{Extended_Pictographic}\uFE0F?)*)/gu;
    var SKIP = { SCRIPT: 1, STYLE: 1, TEXTAREA: 1, INPUT: 1, TITLE: 1, NOSCRIPT: 1, CODE: 1, SVG: 1, svg: 1, PATH: 1, path: 1, G: 1, g: 1, RECT: 1, rect: 1 };

    function keyOf(s) { return s.replace(/\uFE0F/g, ''); }

    function makeIcon(sym, hasTextAfter) {
        var k = keyOf(sym), i = document.createElement('i');
        if (COLORS[k]) { i.className = 'bi bi-circle-fill mp-i'; i.style.color = COLORS[k]; i.style.fontSize = '.7em'; }
        else if (MAP[k]) { i.className = 'bi ' + MAP[k] + ' mp-i'; }
        else { return null; }
        i.setAttribute('aria-hidden', 'true');
        if (hasTextAfter) i.style.marginRight = '.35em';
        return i;
    }

    function processText(node) {
        try {
            var text = node.nodeValue;
            if (!text || !EMOJI_RE.test(text)) { EMOJI_RE.lastIndex = 0; return; }
            EMOJI_RE.lastIndex = 0;

            var parent = node.parentNode;
            if (!parent || SKIP[parent.nodeName] || (parent.closest && (parent.closest('[data-keep-emoji]') || parent.closest('svg')))) return;
            if (parent.id && parent.id.includes('Count')) return;

            // <option> cannot contain elements: strip mapped emoji only
            if (parent.nodeName === 'OPTION') {
                node.nodeValue = text.replace(EMOJI_RE, function (m) {
                    var k = keyOf(m); return (MAP[k] || COLORS[k]) ? '' : m;
                }).replace(/^\s+/, '');
                return;
            }

            var frag = document.createDocumentFragment(), last = 0, m, changed = false;
            while ((m = EMOJI_RE.exec(text))) {
                var rest = text.slice(m.index + m[0].length);
                var icon = makeIcon(m[0], /^\s*\S/.test(rest));
                if (!icon) continue;
                frag.appendChild(document.createTextNode(text.slice(last, m.index)));
                frag.appendChild(icon);
                last = m.index + m[0].length;
                // swallow a single space after the icon (margin handles spacing)
                if (text.charAt(last) === ' ') last++;
                changed = true;
            }
            EMOJI_RE.lastIndex = 0;
            if (!changed) return;
            frag.appendChild(document.createTextNode(text.slice(last)));
            if (node.parentNode === parent) {
                parent.replaceChild(frag, node);
            }
        } catch (e) {
            // Silently suppress DOM mutation racing or detaching errors
        }
    }

    function walk(root) {
        if (!root) return;
        try {
            if (root.nodeType === 3) { processText(root); return; }
            if (root.nodeType !== 1 || SKIP[root.nodeName] || (root.closest && root.closest('svg'))) return;
            var w = document.createTreeWalker(root, NodeFilter.SHOW_TEXT, {
                acceptNode: function (n) {
                    var p = n.parentNode;
                    return (p && !SKIP[p.nodeName] && !(p.closest && p.closest('svg'))) ? NodeFilter.FILTER_ACCEPT : NodeFilter.FILTER_REJECT;
                }
            });
            var list = [], n;
            while ((n = w.nextNode())) list.push(n);
            list.forEach(processText);
        } catch (e) {
            // Defensive error handling
        }
    }

    function start() {
        try {
            walk(document.body);
            new MutationObserver(function (muts) {
                muts.forEach(function (mu) {
                    try {
                        if (mu.type === 'characterData') processText(mu.target);
                        if (mu.addedNodes) mu.addedNodes.forEach(walk);
                    } catch (err) {}
                });
            }).observe(document.body, { childList: true, subtree: true, characterData: true });
        } catch (e) {}
    }

    if (!window.PCRE_UNSUPPORTED && typeof document !== 'undefined') {
        try { new RegExp('\\p{Extended_Pictographic}', 'u'); }
        catch (e) { return; }   // very old browser: leave emoji as they are
        if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start);
        else start();
    }
})();
