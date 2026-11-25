{{-- File: guestlink/resources/views/guest/partials/ai-concierge-drawer.blade.php --}}
@php
    $conciergeToken = $token ?? ($room->qr_token ?? 'SMFnULzAzhY8LcBx0BHy0IGSuRybuqwf');
    $roomNum = $room->room_number ?? '201';
@endphp

{{-- Slide-up Full Screen / Drawer Modal for Mobile (Triggered from Header & contextual actions) --}}
<div id="aiConciergeBackdrop" style="z-index:9999;" class="fixed inset-0 z-50 bg-black/75 backdrop-blur-sm hidden opacity-0 transition-opacity duration-300" onclick="closeAiConciergeDrawer(event)">
    <div id="aiConciergeDrawer" class="fixed inset-x-0 bottom-0 max-w-md mx-auto h-[88dvh] bg-[#080C14] border-t border-[color:var(--mp-line-strong)] rounded-t-[24px] shadow-[0_-24px_60px_rgba(0,0,0,0.85)] flex flex-col transform translate-y-full transition-transform duration-300 ease-out overflow-hidden" onclick="event.stopPropagation()">

        {{-- Drawer Drag Handle & Top Header --}}
        <div class="px-5 pt-3 pb-3.5 border-b border-[color:var(--mp-hairline)] bg-[#0D1320]/95 backdrop-blur-xl shrink-0">
            <div class="w-10 h-1 bg-white/15 rounded-full mx-auto mb-3"></div>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="relative w-10 h-10 rounded-xl bg-[rgba(201,162,74,0.1)] border border-[color:var(--mp-line-strong)] flex items-center justify-center text-base text-[color:var(--mp-gold-bright)]">
                        <i class="bi bi-stars"></i>
                        <span class="absolute -bottom-0.5 -right-0.5 w-2.5 h-2.5 rounded-full bg-emerald-400 border-2 border-[#0D1320]"></span>
                    </div>
                    <div>
                        <span class="font-serif font-semibold text-[0.95rem] text-[#F6F1E7] tracking-wide block leading-tight">
                            Madi &middot; AI Concierge
                        </span>
                        <span class="text-[11px] text-[#8C94A4] block mt-0.5">
                            Room {{ $roomNum }} &middot; Madison Park 24/7 AI
                        </span>
                    </div>
                </div>
                <div class="flex items-center gap-1.5">
                    <button type="button" onclick="resetAiConciergeChat()" title="Restart chat session" aria-label="Restart chat"
                            class="w-8 h-8 rounded-full bg-white/[0.04] border border-[color:var(--mp-hairline-md)] text-[#A8A397] hover:text-[color:var(--mp-gold-bright)] hover:border-[color:var(--mp-line-strong)] flex items-center justify-center text-xs cursor-pointer transition-colors">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </button>
                    <button type="button" onclick="closeAiConciergeDrawer()" title="Close concierge" aria-label="Close concierge"
                            class="w-8 h-8 rounded-full bg-white/[0.04] border border-[color:var(--mp-hairline-md)] text-[#A8A397] hover:text-white hover:border-white/25 flex items-center justify-center text-xs cursor-pointer transition-colors">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
            </div>
        </div>

        {{-- Scrollable Conversation Feed --}}
        <div id="aiChatFeed" class="flex-1 overflow-y-auto px-4 py-4 space-y-4 no-scrollbar">

            {{-- Clean, Conversational Greeting from Madi --}}
            <div id="aiWelcomeBubble" class="flex items-start gap-2.5">
                <div class="w-7 h-7 rounded-lg bg-[rgba(201,162,74,0.1)] border border-[color:var(--mp-line)] flex items-center justify-center text-xs text-[color:var(--mp-gold-bright)] shrink-0 mt-0.5">
                    <i class="bi bi-stars"></i>
                </div>
                <div class="max-w-[86%] rounded-2xl rounded-tl-sm p-3.5 bg-[#0E1523] border border-[color:var(--mp-hairline-md)] text-[13px] leading-relaxed text-[#E7E2D7]">
                    <p class="font-serif font-semibold text-[color:var(--mp-gold-bright)] text-sm mb-1">
                        Good day! I am Madi, your Madison Park AI Concierge.
                    </p>
                    <p class="text-[#B9B3A3] text-xs leading-relaxed">
                        How may I assist you in <strong class="text-[#F6F1E7]">Room {{ $roomNum }}</strong>? Ask about dining, events, Wi-Fi, speak via mic, or attach a photo of anything needing attention.
                    </p>
                </div>
            </div>

            {{-- Compact Quick Chips (Auto-hides on first message or tap) --}}
            <div id="aiQuickChipsContainer" class="pt-0.5 transition-all duration-300">
                <span class="mp-label mb-2">Suggested Inquiries</span>
                <div class="flex flex-wrap gap-1.5">
                    <button type="button" onclick="sendAiConciergePrompt('Do you have available function halls? How much are the rates and how do I reserve?')" class="mp-chip">
                        <i class="bi bi-bank"></i> Function Halls
                    </button>
                    <button type="button" onclick="sendAiConciergePrompt('How much is the food menu pricing and what dishes do you recommend?')" class="mp-chip">
                        <i class="bi bi-cup-hot"></i> Dining &amp; Pricing
                    </button>
                    <button type="button" onclick="sendAiConciergePrompt('Can you bring extra towels and water refills to Room {{ $roomNum }}?')" class="mp-chip">
                        <i class="bi bi-bell"></i> Extra Towels
                    </button>
                    <button type="button" onclick="sendAiConciergePrompt('My aircon is not cold, can you send maintenance to check?')" class="mp-chip">
                        <i class="bi bi-snow"></i> AC / Repair
                    </button>
                    <button type="button" onclick="sendAiConciergePrompt('What is the guest Wi-Fi network and password?')" class="mp-chip">
                        <i class="bi bi-wifi"></i> Wi-Fi Access
                    </button>
                </div>
            </div>

            {{-- Dynamic Message Bubbles will be injected here --}}
            <div id="aiDynamicMessages" class="space-y-3.5"></div>

            {{-- Live Loading Indicator --}}
            <div id="aiLoadingIndicator" class="hidden flex items-start gap-2.5 pt-1">
                <div class="w-7 h-7 rounded-lg bg-[rgba(201,162,74,0.1)] border border-[color:var(--mp-line)] flex items-center justify-center text-xs text-[color:var(--mp-gold-bright)] shrink-0 mt-0.5">
                    <i class="bi bi-stars"></i>
                </div>
                <div class="rounded-2xl rounded-tl-sm px-3.5 py-2.5 bg-[#0E1523] border border-[color:var(--mp-line)] text-xs text-[color:var(--mp-gold-bright)] flex items-center gap-2">
                    <span class="inline-block w-3.5 h-3.5 border-2 border-[color:var(--mp-gold)] border-t-transparent rounded-full animate-spin"></span>
                    <span id="aiLoadingText">Checking hotel details...</span>
                </div>
            </div>

        </div>

        {{-- Bottom Input Console & Actions --}}
        <div class="px-4 pt-3 pb-[max(0.85rem,env(safe-area-inset-bottom))] border-t border-[color:var(--mp-hairline)] bg-[#0D1320]/95 backdrop-blur-xl shrink-0">

            {{-- Hidden File Input for Image Upload / Camera --}}
            <input type="file" id="aiChatPhotoInput" accept="image/*" class="hidden" onchange="handleAiPhotoSelected(this)">

            {{-- Photo Attachment Preview Bar (Shown when photo selected) --}}
            <div id="aiPhotoPreviewBar" class="hidden mb-2.5 px-3 py-2 rounded-xl bg-[#080C14] border border-[color:var(--mp-line)] flex items-center justify-between">
                <div class="flex items-center gap-2.5 overflow-hidden">
                    <img id="aiPhotoPreviewImg" src="" alt="Attached photo" class="w-10 h-10 object-cover rounded-lg border border-[color:var(--mp-line-strong)] shrink-0">
                    <div class="truncate text-[11px]">
                        <span class="text-[#F6F1E7] font-medium block truncate" id="aiPhotoPreviewName">photo.jpg</span>
                        <span class="text-emerald-400 text-[11px] flex items-center gap-1">
                            <i class="bi bi-check-circle-fill"></i> Ready for AI inspection
                        </span>
                    </div>
                </div>
                <button type="button" onclick="clearAiAttachedPhoto()" title="Remove photo" aria-label="Remove photo"
                        class="w-7 h-7 rounded-full bg-white/5 hover:bg-rose-500/20 text-[#8C94A4] hover:text-rose-200 flex items-center justify-center text-xs transition-colors cursor-pointer">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>

            {{-- Live Voice Recording Indicator Bar --}}
            <div id="aiVoiceStatusBar" class="hidden mb-2.5 px-3 py-2 rounded-xl bg-rose-950/60 border border-rose-500/35 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <span class="relative flex h-2.5 w-2.5">
                        <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-rose-500"></span>
                    </span>
                    <div class="text-xs">
                        <span class="font-semibold text-rose-200 block" id="aiVoiceStatusTitle">Listening...</span>
                        <span class="text-[11px] text-rose-300/80 block truncate max-w-[200px]" id="aiVoiceLiveTranscript">Speak into microphone...</span>
                    </div>
                </div>
                <button type="button" onclick="stopSpeechRecognition(true)"
                        class="px-3 py-1 rounded-lg bg-rose-600 hover:bg-rose-500 text-white font-semibold text-xs transition-colors cursor-pointer">
                    Done
                </button>
            </div>

            {{-- Input Bar Form --}}
            <form id="aiConciergeForm" onsubmit="handleSendAiMessage(event)">
                <div class="flex items-center gap-2">
                    <button type="button" id="btnAttachPhoto" onclick="document.getElementById('aiChatPhotoInput').click()" title="Attach photo" aria-label="Attach photo"
                            class="w-10 h-10 rounded-xl bg-[#080C14] hover:bg-white/[0.06] border border-[color:var(--mp-hairline-md)] text-[color:var(--mp-gold-bright)] flex items-center justify-center text-sm transition-colors shrink-0 cursor-pointer">
                        <i class="bi bi-camera" id="photoBtnIcon"></i>
                    </button>

                    <div class="relative flex-1 flex items-center bg-[#080C14] border border-[color:var(--mp-hairline-md)] focus-within:border-[color:var(--mp-gold)] rounded-xl transition-colors">
                        <textarea id="aiConciergeInput" rows="1" maxlength="1000"
                                  placeholder="Ask Madi anything..."
                                  class="w-full pl-3.5 pr-2 py-2.5 bg-transparent text-[#F6F1E7] text-xs focus:outline-none placeholder:text-[#647082] resize-none leading-relaxed"
                                  onkeydown="if(event.key === 'Enter' && !event.shiftKey) { event.preventDefault(); handleSendAiMessage(event); }"></textarea>

                        <button type="button" id="btnVoiceInput" onclick="toggleSpeechRecognition()" title="Voice input" aria-label="Voice input"
                                class="w-8 h-8 mr-1 rounded-lg text-[#A8A397] hover:text-[color:var(--mp-gold-bright)] hover:bg-white/5 flex items-center justify-center text-sm transition-colors shrink-0 cursor-pointer">
                            <i class="bi bi-mic" id="voiceMicIcon"></i>
                        </button>
                    </div>

                    <button type="submit" id="btnSubmitAiConcierge" title="Send message" aria-label="Send message"
                            class="w-10 h-10 rounded-xl flex items-center justify-center text-sm font-bold shrink-0 cursor-pointer transition-all"
                            style="background:var(--mp-metal);color:#140F04;">
                        <i class="bi bi-arrow-up text-base"></i>
                    </button>
                </div>
            </form>
        </div>

    </div>
</div>

{{-- Concierge Drawer Client-Side Engine --}}
<script>
const aiConciergeToken = "{{ $conciergeToken }}";
const conciergeAskEndpoint = "{{ route('guest.concierge.ask', $conciergeToken) }}";
const conciergeTicketEndpoint = "{{ route('guest.concierge.ticket', $conciergeToken) }}";
const serviceFormRoute = "{{ route('guest.service', $conciergeToken) }}";
const maintenanceFormRoute = "{{ route('guest.maintenance', $conciergeToken) }}";
const foodMenuRoute = "{{ route('guest.food', $conciergeToken) }}";
const eventsRoute = "{{ route('guest.events', $conciergeToken) }}";
const trackRoute = "{{ route('guest.track', $conciergeToken) }}";

let aiConversationHistory = [];
let speechRecognizer = null;
let isListening = false;
let attachedPhotoFile = null;
let attachedPhotoDataUrl = null;

function openAiConciergeDrawer() {
    const backdrop = document.getElementById('aiConciergeBackdrop');
    const drawer = document.getElementById('aiConciergeDrawer');
    if (!backdrop || !drawer) return;

    backdrop.classList.remove('hidden');
    setTimeout(() => {
        backdrop.classList.remove('opacity-0');
        drawer.classList.remove('translate-y-full');
    }, 10);

    setTimeout(() => {
        document.getElementById('aiConciergeInput')?.focus();
    }, 300);
}

function closeAiConciergeDrawer(event) {
    if (event && event.target && event.target.id !== 'aiConciergeBackdrop' && !event.target.closest('button')) {
        return;
    }
    const backdrop = document.getElementById('aiConciergeBackdrop');
    const drawer = document.getElementById('aiConciergeDrawer');
    if (!backdrop || !drawer) return;

    if (isListening && speechRecognizer) {
        stopSpeechRecognition(false);
    }

    drawer.classList.add('translate-y-full');
    backdrop.classList.add('opacity-0');
    setTimeout(() => {
        backdrop.classList.add('hidden');
    }, 300);
}

function resetAiConciergeChat() {
    aiConversationHistory = [];
    const container = document.getElementById('aiDynamicMessages');
    if (container) container.innerHTML = '';
    
    // Restore quick chips
    const chipsContainer = document.getElementById('aiQuickChipsContainer');
    if (chipsContainer) chipsContainer.classList.remove('hidden');

    clearAiAttachedPhoto();
    
    const input = document.getElementById('aiConciergeInput');
    if (input) {
        input.value = '';
        input.focus();
    }
}

function sendAiConciergePrompt(question) {
    const input = document.getElementById('aiConciergeInput');
    if (input) {
        input.value = question;
        handleSendAiMessage(new Event('submit'));
    }
}

// ── Photo Attachment Handlers ─────────────────────────────────────
function handleAiPhotoSelected(fileInput) {
    if (!fileInput.files || fileInput.files.length === 0) return;

    const file = fileInput.files[0];
    if (!file.type.startsWith('image/')) {
        alert("Please select a valid image file (JPG, PNG, WEBP).");
        return;
    }

    attachedPhotoFile = file;

    const reader = new FileReader();
    reader.onload = function(e) {
        attachedPhotoDataUrl = e.target.result;
        
        const previewBar = document.getElementById('aiPhotoPreviewBar');
        const previewImg = document.getElementById('aiPhotoPreviewImg');
        const previewName = document.getElementById('aiPhotoPreviewName');
        const photoIcon = document.getElementById('photoBtnIcon');

        if (previewBar && previewImg && previewName) {
            previewImg.src = attachedPhotoDataUrl;
            previewName.textContent = file.name || 'Room photo';
            previewBar.classList.remove('hidden');
        }

        if (photoIcon) {
            photoIcon.className = 'bi bi-check2-circle text-emerald-400 text-xs font-bold';
        }
    };
    reader.readAsDataURL(file);
}

function clearAiAttachedPhoto() {
    attachedPhotoFile = null;
    attachedPhotoDataUrl = null;

    const fileInput = document.getElementById('aiChatPhotoInput');
    if (fileInput) fileInput.value = '';

    const previewBar = document.getElementById('aiPhotoPreviewBar');
    if (previewBar) previewBar.classList.add('hidden');

    const photoIcon = document.getElementById('photoBtnIcon');
    if (photoIcon) {
        photoIcon.className = 'bi bi-camera-fill text-xs';
    }
}

// ── Speech-to-Text Microphone Recording ───────────────────────────
function toggleSpeechRecognition() {
    if (isListening) {
        stopSpeechRecognition(true);
        return;
    }

    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
    if (!SpeechRecognition) {
        alert("Speech recognition is not supported in this browser. Please use Chrome, Edge, or Safari, or type your message.");
        return;
    }

    const micBtn = document.getElementById('btnVoiceInput');
    const micIcon = document.getElementById('voiceMicIcon');
    const input = document.getElementById('aiConciergeInput');
    const voiceBar = document.getElementById('aiVoiceStatusBar');
    const liveTranscript = document.getElementById('aiVoiceLiveTranscript');

    try {
        speechRecognizer = new SpeechRecognition();
        speechRecognizer.lang = 'en-US';
        speechRecognizer.continuous = true;
        speechRecognizer.interimResults = true;

        speechRecognizer.onstart = function() {
            isListening = true;
            if (micBtn) {
                micBtn.classList.remove('text-[#A8A397]');
                micBtn.classList.add('bg-rose-600', 'text-white');
            }
            if (micIcon) {
                micIcon.className = 'bi bi-record-circle-fill text-white text-xs';
            }
            if (voiceBar) {
                voiceBar.classList.remove('hidden');
            }
            if (liveTranscript) {
                liveTranscript.textContent = "Listening... Speak now";
            }
        };

        speechRecognizer.onresult = function(event) {
            let interimTranscript = '';
            let finalTranscript = '';

            for (let i = event.resultIndex; i < event.results.length; ++i) {
                if (event.results[i].isFinal) {
                    finalTranscript += event.results[i][0].transcript;
                } else {
                    interimTranscript += event.results[i][0].transcript;
                }
            }

            if (liveTranscript) {
                liveTranscript.textContent = finalTranscript || interimTranscript || "Listening...";
            }

            if (finalTranscript) {
                input.value = (input.value ? input.value + ' ' : '') + finalTranscript.trim();
                input.focus();
            }
        };

        speechRecognizer.onerror = function(event) {
            console.warn('Speech recognition error:', event.error);
            stopSpeechRecognition(false);
        };

        speechRecognizer.onend = function() {
            stopSpeechRecognition(false);
        };

        speechRecognizer.start();
    } catch (err) {
        console.error('Speech recognition start failed:', err);
        stopSpeechRecognition(false);
    }
}

function stopSpeechRecognition(keepText = true) {
    isListening = false;
    if (speechRecognizer) {
        try { speechRecognizer.stop(); } catch(e) {}
        speechRecognizer = null;
    }

    const micBtn = document.getElementById('btnVoiceInput');
    const micIcon = document.getElementById('voiceMicIcon');
    const voiceBar = document.getElementById('aiVoiceStatusBar');

    if (micBtn) {
        micBtn.classList.remove('bg-rose-600', 'text-white');
        micBtn.classList.add('text-[#A8A397]');
    }
    if (micIcon) {
        micIcon.className = 'bi bi-mic text-sm';
    }
    if (voiceBar) {
        voiceBar.classList.add('hidden');
    }
}

// ── Markdown Formatter ────────────────────────────────────────────
function formatMarkdownText(text) {
    if (!text) return '';
    let html = text
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        // Headings
        .replace(/^#### (.*$)/gim, '<div class="font-bold text-amber-200 mt-2 mb-1">$1</div>')
        .replace(/^### (.*$)/gim, '<div class="font-serif font-bold text-amber-300 text-sm mt-2.5 mb-1.5 pb-0.5 border-b border-amber-500/20">$1</div>')
        .replace(/^## (.*$)/gim, '<div class="font-serif font-bold text-amber-300 text-sm mt-3 mb-1.5">$1</div>')
        // Bold and Italics
        .replace(/\*\*(.*?)\*\*/g, '<strong class="text-amber-200 font-semibold">$1</strong>')
        .replace(/\*(.*?)\*/g, '<em class="text-stone-300">$1</em>')
        // Bullet points
        .replace(/^\s*[-•]\s+(.*$)/gim, '<div class="flex items-start gap-1.5 my-1 ml-1"><span class="text-amber-400 mt-0.5 leading-none">•</span><span>$1</span></div>')
        .replace(/^\s*\d+\.\s+(.*$)/gim, '<div class="flex items-start gap-1.5 my-1 ml-1"><span class="text-amber-400 font-bold">›</span><span>$1</span></div>')
        // Linebreaks
        .replace(/\n\n/g, '<div class="h-2"></div>')
        .replace(/\n/g, '<br>');

    return html;
}

// ── Send Message Handler ──────────────────────────────────────────
function handleSendAiMessage(event) {
    if (event) event.preventDefault();
    const input = document.getElementById('aiConciergeInput');
    const question = input ? input.value.trim() : '';

    if (!question && !attachedPhotoFile && !attachedPhotoDataUrl) {
        return;
    }

    if (isListening) {
        stopSpeechRecognition(true);
    }

    // Hide starter chips once user starts conversation
    const chipsContainer = document.getElementById('aiQuickChipsContainer');
    if (chipsContainer) {
        chipsContainer.classList.add('hidden');
    }

    const currentPhotoDataUrl = attachedPhotoDataUrl;
    const currentPhotoFile = attachedPhotoFile;

    // Reset input
    if (input) {
        input.value = '';
        input.style.height = 'auto';
    }
    clearAiAttachedPhoto();

    // 1. Render User Question Bubble (with image preview if attached)
    const container = document.getElementById('aiDynamicMessages');
    const userBubble = document.createElement('div');
    userBubble.className = 'flex justify-end';

    let photoHtml = '';
    if (currentPhotoDataUrl) {
        photoHtml = `
            <div class="mb-2 rounded-xl overflow-hidden border border-black/30 shadow-md">
                <img src="${currentPhotoDataUrl}" alt="Guest attachment" class="w-full max-h-48 object-cover">
            </div>
        `;
    }

    const displayText = question || (currentPhotoDataUrl ? 'Please inspect this photo from Room {{ $roomNum }}.' : '');

    userBubble.innerHTML = `
        <div class="max-w-[85%] rounded-2xl rounded-tr-sm px-3.5 py-2.5 text-[#140F04] font-medium text-xs leading-relaxed shadow-sm" style="background:var(--mp-metal);">
            ${photoHtml}
            <div>${displayText.replace(/</g, '&lt;').replace(/>/g, '&gt;')}</div>
        </div>
    `;
    container.appendChild(userBubble);

    // Scroll to bottom
    const feed = document.getElementById('aiChatFeed');
    feed.scrollTop = feed.scrollHeight;

    // Show loading
    const loading = document.getElementById('aiLoadingIndicator');
    const loadingText = document.getElementById('aiLoadingText');
    if (loadingText) {
        loadingText.textContent = currentPhotoDataUrl ? "Madi is analyzing your photo with AI Vision..." : "Madi is reviewing hotel records & preparing assistance...";
    }
    loading.classList.remove('hidden');
    feed.scrollTop = feed.scrollHeight;

    aiConversationHistory.push({ role: 'user', content: displayText });

    // 2. Build Request (FormData for file/image support)
    const formData = new FormData();
    formData.append('question', displayText);
    formData.append('history', JSON.stringify(aiConversationHistory));

    if (currentPhotoFile) {
        formData.append('photo', currentPhotoFile);
    } else if (currentPhotoDataUrl) {
        formData.append('photo_data', currentPhotoDataUrl);
    }

    fetch(conciergeAskEndpoint, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        },
        body: formData
    })
    .then(res => res.json())
    .then(res => {
        loading.classList.add('hidden');
        if (!res || !res.success || !res.data) {
            renderAiErrorBubble("Our concierge service is temporarily busy. Please call Front Desk Ext. 0 or ask again in a moment.");
            return;
        }

        const data = res.data;
        aiConversationHistory.push({ role: 'assistant', content: data.reply });

        // Render AI Response Bubble
        const aiBubble = document.createElement('div');
        aiBubble.className = 'flex items-start gap-2.5';

        const pType = data.prefill_type || '';
        const aType = data.action_type || '';
        const ticket = data.prefill_ticket || {};
        const createdTicket = data.created_ticket || null;
        const prefillDesc = encodeURIComponent(ticket.description || '');
        const prefillCat = encodeURIComponent(ticket.category || '');

        let actionButtonHtml = '';
        const makeActionCard = (href, icon, label) => `
            <div class="mt-3 pt-2.5 border-t border-white/10">
                <a href="${href}" class="mp-btn w-full !min-h-[38px] !py-2 !text-xs">
                    <i class="bi ${icon}"></i> ${label}
                </a>
            </div>
        `;

        const makeInstantTicketAction = (spec, btnLabel, fallbackHref, fallbackLabel, showCallExt = false) => {
            const payloadAttr = escapeHtml(JSON.stringify(spec));
            return `
                <div class="mt-3 pt-2.5 border-t border-white/10 space-y-2 js-instant-ticket-wrap">
                    <button type="button" data-ticket-spec="${payloadAttr}" onclick="createTicketDirectlyFromMadi(this)"
                            class="mp-btn w-full !min-h-[38px] !py-2 !text-xs">
                        <i class="bi bi-send-check"></i> <span>${escapeHtml(btnLabel)}</span>
                    </button>
                    <div class="grid ${showCallExt ? 'grid-cols-2' : 'grid-cols-1'} gap-2">
                        ${showCallExt ? `<a href="tel:0" class="mp-btn-ghost !min-h-[34px] !py-1.5 !px-2.5 !text-[11px]"><i class="bi bi-telephone"></i> Call Ext. 0</a>` : ''}
                        <a href="${fallbackHref}" class="mp-btn-ghost !min-h-[34px] !py-1.5 !px-2.5 !text-[11px]">
                            <i class="bi bi-sliders"></i> ${escapeHtml(fallbackLabel)}
                        </a>
                    </div>
                </div>
            `;
        };

        if (createdTicket && createdTicket.reference_number) {
            actionButtonHtml = `
                <div class="mt-3 pt-2.5 border-t border-emerald-500/25 space-y-2">
                    <div class="flex items-center justify-between gap-2 px-2.5 py-2 rounded-xl bg-emerald-500/10 border border-emerald-500/30">
                        <div class="flex items-center gap-2 min-w-0">
                            <i class="bi bi-check-circle-fill text-emerald-400 text-sm shrink-0"></i>
                            <div class="min-w-0">
                                <span class="block text-[11px] font-bold text-emerald-300 truncate">${escapeHtml(createdTicket.reference_number)} &middot; ${escapeHtml(createdTicket.department)} Notified</span>
                                <span class="block text-[10px] text-[#A8A397]">Live alert sent to hotel staff console</span>
                            </div>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <a href="${escapeHtml(createdTicket.track_url)}" class="mp-btn !min-h-[36px] !py-1.5 !px-2.5 !text-xs">
                            <i class="bi bi-chat-dots"></i> Live Chat
                        </a>
                        <a href="tel:0" class="mp-btn-ghost !min-h-[36px] !py-1.5 !px-2.5 !text-xs">
                            <i class="bi bi-telephone"></i> Call Ext. 0
                        </a>
                    </div>
                </div>
            `;
        } else if (aType === 'reserve_hall' || (pType === 'service' && ticket.category && ticket.category.toLowerCase().includes('event'))) {
            actionButtonHtml = makeActionCard(`${eventsRoute}?desc=${prefillDesc}`, 'bi-calendar3', data.action_label || 'View Function Halls & Reserve');
        } else if (aType === 'request_housekeeping' || (pType === 'service' && ticket.type !== 'maintenance')) {
            actionButtonHtml = makeInstantTicketAction(
                { type: 'service', department: 'Housekeeping', category: ticket.category || 'Extra Towels', description: ticket.description || displayText, human_handoff: false },
                'Create Housekeeping Ticket Now',
                `${serviceFormRoute}?category=${prefillCat || 'housekeeping'}&desc=${prefillDesc}`,
                'Customize in Form'
            );
        } else if (aType === 'request_maintenance' || pType === 'maintenance') {
            actionButtonHtml = makeInstantTicketAction(
                { type: 'maintenance', department: 'Maintenance', category: ticket.category || 'Air Conditioning', description: ticket.description || displayText, human_handoff: false },
                'Create Maintenance Ticket Now',
                `${maintenanceFormRoute}?category=${prefillCat}&desc=${prefillDesc}`,
                'Attach Photo / Form'
            );
        } else if (aType === 'order_food' || pType === 'food') {
            actionButtonHtml = makeActionCard(foodMenuRoute, 'bi-cup-hot', data.action_label || 'Browse Dining Menu & Order');
        } else if (aType === 'track_status' || pType === 'track') {
            actionButtonHtml = makeActionCard(trackRoute, 'bi-clock-history', data.action_label || 'View Live Request Tracker');
        } else if (aType === 'call_front_desk' || pType === 'front_desk') {
            actionButtonHtml = makeInstantTicketAction(
                { type: 'service', department: 'Front Desk', category: 'Front Desk Concierge', description: ticket.description || displayText || 'Guest requested to speak with Front Desk.', human_handoff: true },
                'Create Ticket & Notify Front Desk Now',
                `${serviceFormRoute}?category=front desk&desc=${prefillDesc || encodeURIComponent('Front Desk assistance requested.')}`,
                'Open Form',
                true
            );
        }

        let dynamicChipsHtml = '';
        if (data.quick_prompts && data.quick_prompts.length > 0) {
            dynamicChipsHtml = `
                <div class="mt-3 pt-2.5 border-t border-white/10">
                    <span class="block text-[10px] uppercase tracking-wider font-semibold text-[#8C94A4] mb-1.5">Follow up</span>
                    <div class="flex flex-wrap gap-1.5">
                        ${data.quick_prompts.map(qp => `
                            <button type="button" data-qp="${escapeHtml(qp)}" onclick="sendAiConciergePrompt(this.dataset.qp)"
                                    class="mp-chip !text-[11px] !py-1 !px-2.5 text-left">
                                ${escapeHtml(qp)}
                            </button>
                        `).join('')}
                    </div>
                </div>
            `;
        }

        aiBubble.innerHTML = `
            <div class="w-7 h-7 rounded-lg bg-[rgba(201,162,74,0.1)] border border-[color:var(--mp-line)] flex items-center justify-center text-xs text-[color:var(--mp-gold-bright)] shrink-0 mt-0.5">
                <i class="bi bi-stars"></i>
            </div>
            <div class="ai-reply-bubble max-w-[86%] rounded-2xl rounded-tl-sm p-3.5 bg-[#0E1523] border border-[color:var(--mp-hairline-md)] text-xs leading-relaxed text-[#E7E2D7]">
                <div>${formatMarkdownText(data.reply)}</div>
                ${actionButtonHtml}
                ${dynamicChipsHtml}
            </div>
        `;

        container.appendChild(aiBubble);
        feed.scrollTop = feed.scrollHeight;
    })
    .catch(err => {
        loading.classList.add('hidden');
        renderAiErrorBubble("Unable to connect to AI concierge service. Please verify your connection or call Ext. 0.");
    });
}

function createTicketDirectlyFromMadi(btn) {
    if (!btn || btn.disabled) return;
    let spec = {};
    try {
        spec = JSON.parse(btn.dataset.ticketSpec || '{}');
    } catch (e) {
        return;
    }

    const originalHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="inline-block w-3.5 h-3.5 border-2 border-current border-t-transparent rounded-full animate-spin"></span> <span>Creating ticket &amp; notifying staff...</span>';

    fetch(conciergeTicketEndpoint, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        },
        body: JSON.stringify(spec)
    })
    .then(res => res.json())
    .then(res => {
        if (!res || !res.success || !res.ticket) {
            btn.disabled = false;
            btn.innerHTML = originalHtml;
            renderAiErrorBubble(res?.message || 'Could not create ticket right now. Please try again or call Ext. 0.');
            return;
        }
        const t = res.ticket;
        const wrap = btn.closest('.js-instant-ticket-wrap');
        if (wrap) {
            wrap.innerHTML = `
                <div class="flex items-center justify-between gap-2 px-2.5 py-2 rounded-xl bg-emerald-500/10 border border-emerald-500/30">
                    <div class="flex items-center gap-2 min-w-0">
                        <i class="bi bi-check-circle-fill text-emerald-400 text-sm shrink-0"></i>
                        <div class="min-w-0">
                            <span class="block text-[11px] font-bold text-emerald-300 truncate">${escapeHtml(t.reference_number)} &middot; ${escapeHtml(t.department)} Notified</span>
                            <span class="block text-[10px] text-[#A8A397]">Live alert sent to hotel staff console</span>
                        </div>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <a href="${escapeHtml(t.track_url)}" class="mp-btn !min-h-[36px] !py-1.5 !px-2.5 !text-xs">
                        <i class="bi bi-chat-dots"></i> Live Chat
                    </a>
                    <a href="tel:0" class="mp-btn-ghost !min-h-[36px] !py-1.5 !px-2.5 !text-xs">
                        <i class="bi bi-telephone"></i> Call Ext. 0
                    </a>
                </div>
            `;
        }
        const feed = document.getElementById('aiChatFeed');
        if (feed) feed.scrollTop = feed.scrollHeight;
    })
    .catch(() => {
        btn.disabled = false;
        btn.innerHTML = originalHtml;
        renderAiErrorBubble('Connection error while creating ticket. Please retry or dial Ext. 0.');
    });
}

function renderAiErrorBubble(errorMsg) {
    const container = document.getElementById('aiDynamicMessages');
    const errBubble = document.createElement('div');
    errBubble.className = 'flex items-start gap-2.5';
    errBubble.innerHTML = `
        <div class="w-7 h-7 rounded-lg bg-rose-500/20 border border-rose-500/40 flex items-center justify-center text-xs shrink-0 mt-0.5 text-rose-400">
            <i class="bi bi-exclamation-triangle-fill"></i>
        </div>
        <div class="ai-error-bubble max-w-[85%] rounded-2xl rounded-tl-sm p-3 bg-rose-950/30 border border-rose-500/30 text-rose-200 text-xs">
            ${escapeHtml(errorMsg)}
        </div>
    `;
    container.appendChild(errBubble);
    const feed = document.getElementById('aiChatFeed');
    feed.scrollTop = feed.scrollHeight;
}
</script>
