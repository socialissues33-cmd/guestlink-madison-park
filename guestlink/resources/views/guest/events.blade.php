{{-- File: guestlink/resources/views/guest/events.blade.php --}}
@extends('layouts.guest')

@section('title', 'Function Halls & Events | Madison Park Hotel')

@section('content')
<div class="mp-shell pb-28 antialiased">

    {{-- Sticky Frosted Header --}}
    @include('guest.partials.header', ['subtitle' => 'Function Halls'])

    <div class="px-4 pt-4 space-y-5">

        {{-- Editorial Hero Banner --}}
        <section class="mp-page-hero min-h-[168px] flex flex-col justify-between p-5"
                 style="background-image: linear-gradient(180deg, rgba(8,12,20,.28) 0%, rgba(8,12,20,.82) 64%, #080C14 100%), url('{{ asset("images/hotel/madison-event-plaza.jpg") }}'); background-position: center 35%; background-size: cover;">
            <span class="mp-photo-badge inline-flex items-center gap-1.5 self-start px-2.5 py-1 rounded-full bg-[#080C14]/75 backdrop-blur-md border border-[color:var(--mp-line)] text-[11px] font-medium text-[#F4DC8B]">
                <i class="bi bi-bank"></i> Premier Event Spaces
            </span>
            <div class="pt-6">
                <h1 class="font-serif text-[1.35rem] font-semibold text-[#F6F1E7] leading-tight">
                    Function Halls &amp; Banquets
                </h1>
                <p class="text-xs text-[#C8C2B5] leading-relaxed mt-1">
                    Plan a wedding, seminar, or celebration directly from your suite.
                </p>
            </div>
        </section>

        {{-- Venue Selection Tabs --}}
        <div>
            <div class="mp-section-head">
                <h2>Select Venue</h2>
                <span class="mp-meta">3 Spaces</span>
            </div>

            <div class="grid grid-cols-3 gap-2">
                <button type="button" onclick="switchVenue('FH-01')" id="tabBtn-FH-01"
                        class="p-2.5 rounded-2xl border text-left transition-all cursor-pointer {{ ($prefillVenue ?? 'FH-01') === 'FH-01' ? 'bg-[rgba(201,162,74,0.1)] border-[color:var(--mp-line-strong)]' : 'bg-[#0E1523] border-[color:var(--mp-hairline)]' }}">
                    <i class="bi bi-bank text-[color:var(--mp-gold-bright)] text-sm block mb-1"></i>
                    <div class="font-serif font-semibold text-xs text-[#F6F1E7] leading-tight">Event Plaza</div>
                    <div class="text-[11px] text-[color:var(--mp-gold-bright)] font-medium mt-0.5 mp-num">150–250 Pax</div>
                    <div class="text-[10px] text-[#8C94A4] mt-0.5 truncate">FH-01 Outdoor</div>
                </button>

                <button type="button" onclick="switchVenue('FH-ROOM')" id="tabBtn-FH-ROOM"
                        class="p-2.5 rounded-2xl border text-left transition-all cursor-pointer {{ ($prefillVenue ?? '') === 'FH-ROOM' ? 'bg-[rgba(201,162,74,0.1)] border-[color:var(--mp-line-strong)]' : 'bg-[#0E1523] border-[color:var(--mp-hairline)]' }}">
                    <i class="bi bi-door-open text-[color:var(--mp-gold-bright)] text-sm block mb-1"></i>
                    <div class="font-serif font-semibold text-xs text-[#F6F1E7] leading-tight">Function Hall</div>
                    <div class="text-[11px] text-[color:var(--mp-gold-bright)] font-medium mt-0.5 mp-num">50+ Pax / Rm</div>
                    <div class="text-[10px] text-[#8C94A4] mt-0.5 truncate">1st Flr · AB–IJ</div>
                </button>

                <button type="button" onclick="switchVenue('DJP-01')" id="tabBtn-DJP-01"
                        class="p-2.5 rounded-2xl border text-left transition-all cursor-pointer {{ ($prefillVenue ?? '') === 'DJP-01' ? 'bg-[rgba(201,162,74,0.1)] border-[color:var(--mp-line-strong)]' : 'bg-[#0E1523] border-[color:var(--mp-hairline)]' }}">
                    <i class="bi bi-briefcase text-[color:var(--mp-gold-bright)] text-sm block mb-1"></i>
                    <div class="font-serif font-semibold text-xs text-[#F6F1E7] leading-tight">DJP Hall</div>
                    <div class="text-[11px] text-[color:var(--mp-gold-bright)] font-medium mt-0.5 mp-num">40–80 Pax</div>
                    <div class="text-[10px] text-[#8C94A4] mt-0.5 truncate">2nd Flr · AV</div>
                </button>
            </div>
        </div>

        {{-- Venue Card Detail: FH-01 --}}
        <div id="venueCard-FH-01" class="{{ ($prefillVenue ?? 'FH-01') === 'FH-01' ? '' : 'hidden' }} mp-surface overflow-hidden">
            <div class="h-36 relative overflow-hidden">
                <img src="{{ asset('images/hotel/madison-event-plaza.jpg') }}" alt="FH-01 Event Plaza" class="w-full h-full object-cover">
                <div class="absolute inset-0 bg-gradient-to-t from-[#0E1523] via-transparent to-transparent"></div>
                <span class="mp-photo-badge absolute bottom-2.5 left-3.5 px-2.5 py-0.5 rounded-md bg-[#080C14]/85 backdrop-blur-sm border border-[color:var(--mp-line)] text-[#F4DC8B] text-[11px] font-semibold">
                    Ground Level &middot; Event Plaza
                </span>
            </div>
            <div class="p-4 space-y-3">
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <h2 class="font-serif font-semibold text-[0.98rem] text-[#F6F1E7]">FH-01 Main Function Hall (Event Plaza)</h2>
                        <span class="text-[11.5px] text-[#8C94A4]">Weddings, Debuts, Outdoor Banquets &amp; Gatherings</span>
                    </div>
                    <span class="mp-status-pill shrink-0 !text-[10px]">250 Pax</span>
                </div>
                <p class="text-xs text-[#C8C2B5] leading-relaxed">
                    Expansive open-air event plaza featuring a covered stage, signature gazebo, canopy tents, evening lighting, and full surround audio.
                </p>
                <div class="pt-3 border-t border-[color:var(--mp-hairline)]">
                    <span class="mp-label">Inclusions</span>
                    <div class="grid grid-cols-2 gap-1.5 text-[11.5px] text-[#C8C2B5]">
                        <div class="flex items-center gap-1.5"><i class="bi bi-check2 text-[color:var(--mp-gold)]"></i> Covered Stage</div>
                        <div class="flex items-center gap-1.5"><i class="bi bi-check2 text-[color:var(--mp-gold)]"></i> Wireless Audio</div>
                        <div class="flex items-center gap-1.5"><i class="bi bi-check2 text-[color:var(--mp-gold)]"></i> Canopy Tents &amp; Gazebo</div>
                        <div class="flex items-center gap-1.5"><i class="bi bi-check2 text-[color:var(--mp-gold)]"></i> Guest Wi-Fi</div>
                        <div class="flex items-center gap-1.5"><i class="bi bi-check2 text-[color:var(--mp-gold)]"></i> Banquet Tables</div>
                        <div class="flex items-center gap-1.5"><i class="bi bi-check2 text-[color:var(--mp-gold)]"></i> Event Lighting</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Venue Card Detail: 1st Floor Indoor Function Hall Rooms (AB, CD, E, F, G, H, IJ) --}}
        <div id="venueCard-FH-ROOM" class="{{ ($prefillVenue ?? '') === 'FH-ROOM' ? '' : 'hidden' }} mp-surface overflow-hidden">
            <div class="h-40 relative overflow-hidden">
                <img src="{{ asset('images/hotel/madison-function-hall-indoor.jpg') }}" alt="1st Floor Indoor Function Hall Room" class="w-full h-full object-cover">
                <div class="absolute inset-0 bg-gradient-to-t from-[#0E1523] via-transparent to-transparent"></div>
                <span class="mp-photo-badge absolute bottom-2.5 left-3.5 px-2.5 py-0.5 rounded-md bg-[#080C14]/85 backdrop-blur-sm border border-[color:var(--mp-line)] text-[#F4DC8B] text-[11px] font-semibold">
                    1st Floor &middot; Indoor Function Hall
                </span>
            </div>
            <div class="p-4 space-y-3">
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <h2 class="font-serif font-semibold text-[0.98rem] text-[#F6F1E7]">1st Floor Function Hall Rooms</h2>
                        <span class="text-[11.5px] text-[#8C94A4]">Seminars, Corporate Meetings, Trainings &amp; Private Parties</span>
                    </div>
                    <span class="mp-status-pill shrink-0 !text-[10px]">50+ Pax</span>
                </div>
                <p class="text-xs text-[#C8C2B5] leading-relaxed">
                    Clean, comfortable indoor function hall rooms on the 1st Floor fitting 50+ guests per room—choose combined rooms (<strong class="text-[#F6F1E7]">AB</strong>, <strong class="text-[#F6F1E7]">CD</strong>, <strong class="text-[#F6F1E7]">IJ</strong>) or individual hall rooms (<strong class="text-[#F6F1E7]">E</strong>, <strong class="text-[#F6F1E7]">F</strong>, <strong class="text-[#F6F1E7]">G</strong>, <strong class="text-[#F6F1E7]">H</strong>).
                </p>

                {{-- Interactive 1-Tap Function Hall Room Selector --}}
                <div class="pt-2.5 border-t border-[color:var(--mp-hairline)] space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="mp-label !mb-0">Choose 1st Floor Hall Room</span>
                        <span class="text-[10.5px] text-[color:var(--mp-gold-bright)]">Tap to select</span>
                    </div>
                    <div class="flex flex-wrap gap-1.5">
                        @foreach(['AB' => 'Room AB', 'CD' => 'Room CD', 'E' => 'Room E', 'F' => 'Room F', 'G' => 'Room G', 'H' => 'Room H', 'IJ' => 'Room IJ'] as $rmCode => $rmLabel)
                        <button type="button"
                                data-hall-room="{{ $rmCode }}"
                                onclick="selectHallRoom('{{ $rmCode }}')"
                                class="hall-room-chip mp-chip !py-1.5 !px-3 !text-xs {{ $rmCode === 'AB' ? 'is-active' : '' }}">
                            <span>{{ $rmLabel }}</span>
                        </button>
                        @endforeach
                    </div>
                </div>

                <div class="pt-2.5 border-t border-[color:var(--mp-hairline)]">
                    <span class="mp-label">Inclusions</span>
                    <div class="grid grid-cols-2 gap-1.5 text-[11.5px] text-[#C8C2B5]">
                        <div class="flex items-center gap-1.5"><i class="bi bi-check2 text-[color:var(--mp-gold)]"></i> Air-Conditioned Hall</div>
                        <div class="flex items-center gap-1.5"><i class="bi bi-check2 text-[color:var(--mp-gold)]"></i> Podium &amp; Screen</div>
                        <div class="flex items-center gap-1.5"><i class="bi bi-check2 text-[color:var(--mp-gold)]"></i> Dressed Tables &amp; Chairs</div>
                        <div class="flex items-center gap-1.5"><i class="bi bi-check2 text-[color:var(--mp-gold)]"></i> Sound &amp; Wireless Mics</div>
                        <div class="flex items-center gap-1.5"><i class="bi bi-check2 text-[color:var(--mp-gold)]"></i> High-Speed Wi-Fi</div>
                        <div class="flex items-center gap-1.5"><i class="bi bi-check2 text-[color:var(--mp-gold)]"></i> 50+ Guest Capacity</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Venue Card Detail: DJP-01 --}}
        <div id="venueCard-DJP-01" class="{{ ($prefillVenue ?? '') === 'DJP-01' ? '' : 'hidden' }} mp-surface overflow-hidden">
            <div class="h-40 relative overflow-hidden">
                <img src="{{ asset('images/hotel/madison-function-hall-indoor.jpg') }}" alt="DJP Events Hall" class="w-full h-full object-cover">
                <div class="absolute inset-0 bg-gradient-to-t from-[#0E1523] via-transparent to-transparent"></div>
                <span class="mp-photo-badge absolute bottom-2.5 left-3.5 px-2.5 py-0.5 rounded-md bg-[#080C14]/85 backdrop-blur-sm border border-[color:var(--mp-line)] text-[#F4DC8B] text-[11px] font-semibold">
                    Floor 2 &middot; Executive Suite
                </span>
            </div>
            <div class="p-4 space-y-3">
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <h2 class="font-serif font-semibold text-[0.98rem] text-[#F6F1E7]">DJP Events &amp; Conference Hall</h2>
                        <span class="text-[11.5px] text-[#8C94A4]">Seminars, Board Meetings &amp; Workshops</span>
                    </div>
                    <span class="mp-status-pill shrink-0 !text-[10px]">80 Pax</span>
                </div>
                <p class="text-xs text-[#C8C2B5] leading-relaxed">
                    Dedicated 100 Mbps fiber internet, 75-inch interactive display, modular boardroom seating, and private foyer.
                </p>
                <div class="pt-3 border-t border-[color:var(--mp-hairline)]">
                    <span class="mp-label">Inclusions</span>
                    <div class="grid grid-cols-2 gap-1.5 text-[11.5px] text-[#C8C2B5]">
                        <div class="flex items-center gap-1.5"><i class="bi bi-check2 text-[color:var(--mp-gold)]"></i> 100 Mbps Fiber</div>
                        <div class="flex items-center gap-1.5"><i class="bi bi-check2 text-[color:var(--mp-gold)]"></i> 75" Display</div>
                        <div class="flex items-center gap-1.5"><i class="bi bi-check2 text-[color:var(--mp-gold)]"></i> Wireless Mics</div>
                        <div class="flex items-center gap-1.5"><i class="bi bi-check2 text-[color:var(--mp-gold)]"></i> Flipcharts</div>
                        <div class="flex items-center gap-1.5"><i class="bi bi-check2 text-[color:var(--mp-gold)]"></i> Modular Tables</div>
                        <div class="flex items-center gap-1.5"><i class="bi bi-check2 text-[color:var(--mp-gold)]"></i> Coffee Foyer</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Interactive Banquet Catering Package Selector --}}
        <section class="mp-surface p-4 space-y-3">
            <div class="flex items-center justify-between gap-2">
                <div>
                    <h3 class="font-serif font-semibold text-sm text-[#F6F1E7]">Times Square Catering</h3>
                    <p class="text-[11px] text-[#8C94A4]">Tap a package to select it for your inquiry</p>
                </div>
                <span id="cateringEstimateBadge" class="text-[11px] px-2 py-0.5 rounded-full bg-[rgba(201,162,74,0.12)] border border-[color:var(--mp-line)] text-[color:var(--mp-gold-bright)] font-medium mp-num whitespace-nowrap">
                    Est. ₱44,000
                </span>
            </div>
            <div class="grid grid-cols-3 gap-2">
                <button type="button"
                        data-catering-card="Cocktails & Heavy Merienda (₱350/pax)"
                        data-catering-price="350"
                        onclick="selectCateringPackage(this.dataset.cateringCard)"
                        class="catering-pkg-btn p-2.5 rounded-xl bg-[#080C14]/85 border border-[color:var(--mp-hairline)] text-center cursor-pointer transition-all hover:border-[color:var(--mp-line)]">
                    <div class="pkg-title text-[11px] text-[#98A0AE]">Cocktails</div>
                    <div class="pkg-price font-serif font-semibold text-sm text-[#F6F1E7] mt-0.5 mp-num">₱350</div>
                    <div class="pkg-desc text-[11px] text-[#7D8696] mt-1 leading-tight">Merienda &amp; canapes</div>
                </button>
                <button type="button"
                        data-catering-card="Executive Seminar Package (₱450/pax)"
                        data-catering-price="450"
                        onclick="selectCateringPackage(this.dataset.cateringCard)"
                        class="catering-pkg-btn p-2.5 rounded-xl bg-[#080C14]/85 border border-[color:var(--mp-hairline)] text-center cursor-pointer transition-all hover:border-[color:var(--mp-line)]">
                    <div class="pkg-title text-[11px] text-[#98A0AE]">Corporate</div>
                    <div class="pkg-price font-serif font-semibold text-sm text-[#F6F1E7] mt-0.5 mp-num">₱450</div>
                    <div class="pkg-desc text-[11px] text-[#7D8696] mt-1 leading-tight">3 mains, salad &amp; coffee</div>
                </button>
                <button type="button"
                        data-catering-card="Grand Banquet Buffet (₱550/pax)"
                        data-catering-price="550"
                        onclick="selectCateringPackage(this.dataset.cateringCard)"
                        class="catering-pkg-btn p-2.5 rounded-xl bg-[rgba(201,162,74,0.12)] border border-[color:var(--mp-line-strong)] text-center cursor-pointer transition-all">
                    <div class="pkg-title text-[11px] text-[color:var(--mp-gold-bright)] font-medium">Grand Buffet</div>
                    <div class="pkg-price font-serif font-semibold text-sm text-[color:var(--mp-gold-bright)] mt-0.5 mp-num">₱550</div>
                    <div class="pkg-desc text-[11px] text-[#C8C2B5] mt-1 leading-tight">4 mains &amp; drinks</div>
                </button>
            </div>
        </section>

        {{-- Reservation Inquiry Form --}}
        <section class="mp-surface p-5">
            <div class="flex items-center gap-3 mb-4 pb-3.5 border-b border-[color:var(--mp-hairline)]">
                <span class="mp-icon !w-9 !h-9 !text-sm"><i class="bi bi-calendar3"></i></span>
                <div>
                    <h3 class="font-serif font-semibold text-sm text-[#F6F1E7]">Event Inquiry &amp; Tentative Hold</h3>
                    <p class="text-[11.5px] text-[#8C94A4]">Log preferred date · Finalize in person at Front Office</p>
                </div>
            </div>

            <form action="{{ route('guest.events.store', $token) }}" method="POST" class="space-y-4">
                @csrf
                <input type="hidden" name="venue" id="formVenueInput" value="{{ old('venue', $prefillVenue ?? 'FH-01') }}">
                <input type="hidden" name="hall_room" id="formHallRoomInput" value="{{ old('hall_room', 'AB') }}">

                @if(session('error') || $errors->any())
                <div class="mp-note is-danger text-xs text-rose-200">
                    <i class="bi bi-exclamation-circle text-rose-400 text-base shrink-0 mt-0.5"></i>
                    <div>
                        <div class="font-semibold mb-1">{{ session('error') ?? 'Please check the following:' }}</div>
                        @if($errors->any())
                        <ul class="list-disc list-inside space-y-0.5 text-[11.5px] text-rose-300/90">
                            @foreach($errors->all() as $err)
                                <li>{{ $err }}</li>
                            @endforeach
                        </ul>
                        @endif
                    </div>
                </div>
                @endif

                {{-- Selected Venue Indicator (Auto-Syncs with Hall Switcher Above) --}}
                <div class="px-3 py-2.5 rounded-xl bg-[rgba(201,162,74,0.08)] border border-[color:var(--mp-line)] flex items-center justify-between gap-2">
                    <div class="flex items-center gap-2 min-w-0">
                        <i class="bi bi-building text-[color:var(--mp-gold-bright)] text-xs shrink-0"></i>
                        <div class="min-w-0">
                            <div class="text-[10px] uppercase tracking-wider text-[#98A0AE] font-semibold">Selected Venue</div>
                            @php $initVenue = old('venue', $prefillVenue ?? 'FH-01'); @endphp
                            <div id="selectedVenueSummary" class="text-xs font-semibold text-[#F6F1E7] truncate">
                                {{ $initVenue === 'DJP-01'
                                    ? 'DJP Events Hall (DJP-01 · 50–80 pax)'
                                    : ($initVenue === 'FH-ROOM'
                                        ? '1st Floor Function Hall — Room ' . old('hall_room', 'AB') . ' (50+ pax)'
                                        : 'Event Plaza · Main Function Hall (FH-01 · 150–250 pax)') }}
                            </div>
                        </div>
                    </div>
                    <span class="text-[10px] px-2 py-0.5 rounded-full bg-white/[0.06] text-[color:var(--mp-gold-bright)] whitespace-nowrap shrink-0">Tentative Hold</span>
                </div>

                {{-- Section 1: Event Details --}}
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="mp-label">Event Type <span class="text-rose-400">*</span></label>
                        <select name="event_type" required class="mp-input appearance-none !py-2.5 !text-xs">
                            <option value="Wedding Reception">Wedding Reception</option>
                            <option value="Birthday / Debut">Birthday / Debut</option>
                            <option value="Corporate Seminar / Meeting" selected>Corporate Seminar</option>
                            <option value="Conference / Convention">Conference</option>
                            <option value="Banquet & Gala Dinner">Banquet &amp; Dinner</option>
                            <option value="Private Gathering">Private Gathering</option>
                        </select>
                    </div>
                    <div>
                        <label class="mp-label">Attendees <span class="text-rose-400">*</span></label>
                        <input type="number" id="guestCountInput" name="guest_count" min="10" max="300" value="80" required
                               oninput="updateCateringEstimate()"
                               placeholder="100" class="mp-input !py-2.5 !text-xs mp-num">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="mp-label">Target Event Date <span class="text-rose-400">*</span></label>
                        <input type="date" name="event_date" required min="{{ date('Y-m-d') }}" value="{{ date('Y-m-d', strtotime('+3 days')) }}"
                               class="mp-input !py-2.5 !text-xs">
                    </div>
                    <div>
                        <label class="mp-label">Event Time Slot <span class="text-rose-400">*</span></label>
                        <select name="time_slot" required class="mp-input appearance-none !py-2.5 !text-xs">
                            <option value="Morning (8:00 AM – 12:00 PM)">Morning (8AM–12PM)</option>
                            <option value="Afternoon (1:00 PM – 5:00 PM)">Afternoon (1PM–5PM)</option>
                            <option value="Evening (6:00 PM – 10:00 PM)" selected>Evening (6PM–10PM)</option>
                            <option value="Whole Day (8:00 AM – 5:00 PM)">Whole Day (8AM–5PM)</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="mp-label">Seating Layout</label>
                        <select name="seating_layout" class="mp-input appearance-none !py-2.5 !text-xs">
                            <option value="Banquet Rounds">Banquet Rounds</option>
                            <option value="Theater Style">Theater Style</option>
                            <option value="Classroom / Seminar" selected>Classroom / Seminar</option>
                            <option value="U-Shape / Boardroom">U-Shape / Boardroom</option>
                            <option value="Cocktail Standing">Cocktail Standing</option>
                        </select>
                    </div>
                    <div>
                        <label class="mp-label">Catering Package</label>
                        <select id="cateringPackageSelect" name="catering_package" onchange="syncCateringCards(this.value)" class="mp-input appearance-none !py-2.5 !text-xs">
                            <option value="Grand Banquet Buffet (₱550/pax)">Grand Buffet (₱550)</option>
                            <option value="Executive Seminar Package (₱450/pax)">Corporate (₱450)</option>
                            <option value="Cocktails & Heavy Merienda (₱350/pax)">Cocktails (₱350)</option>
                            <option value="No Catering / Venue Only">Venue Only</option>
                        </select>
                    </div>
                </div>

                {{-- Section 2: Front Office In-Person Appointment --}}
                <div class="p-3.5 rounded-xl bg-[#080C14]/85 border border-[color:var(--mp-line)] space-y-3">
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-[11px] font-semibold uppercase tracking-wider text-[color:var(--mp-gold-bright)] flex items-center gap-1.5">
                            <i class="bi bi-calendar2-check"></i> Front Office Appointment (Lobby)
                        </span>
                        <span class="text-[10px] text-[#8C94A4]">Ground Floor</span>
                    </div>
                    <div class="grid grid-cols-2 gap-2.5">
                        <div>
                            <label class="mp-label">Visit Date</label>
                            <input type="date" name="appointment_date" min="{{ date('Y-m-d') }}" value="{{ date('Y-m-d') }}"
                                   class="mp-input !py-2 !text-xs">
                        </div>
                        <div>
                            <label class="mp-label">Preferred Time</label>
                            <select name="appointment_time" class="mp-input appearance-none !py-2 !text-xs">
                                <option value="Walk-in Today / Now" selected>Walk-in Today</option>
                                <option value="9:00 AM – 11:00 AM">9:00 AM – 11:00 AM</option>
                                <option value="1:00 PM – 3:00 PM">1:00 PM – 3:00 PM</option>
                                <option value="3:00 PM – 5:00 PM">3:00 PM – 5:00 PM</option>
                                <option value="6:00 PM – 8:00 PM">6:00 PM – 8:00 PM</option>
                            </select>
                        </div>
                    </div>
                    <label class="flex items-center gap-2 text-xs text-[#C8C2B5] cursor-pointer pt-0.5">
                        <input type="checkbox" name="wants_ocular" value="1" checked
                               class="rounded border-white/20 bg-[#0E1523] text-amber-500 focus:ring-amber-500/40">
                        <span>Include function hall ocular viewing during my Front Office visit</span>
                    </label>
                </div>

                {{-- Section 3: Contact & Host Information --}}
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="mp-label">Contact Person <span class="text-rose-400">*</span></label>
                        <input type="text" name="guest_name" value="{{ old('guest_name') }}" required
                               placeholder="e.g. Juan dela Cruz" class="mp-input !py-2.5 !text-xs">
                    </div>
                    <div>
                        <label class="mp-label">Mobile Number</label>
                        <input type="tel" name="contact_number" value="{{ old('contact_number') }}"
                               placeholder="0917-xxx-xxxx" class="mp-input !py-2.5 !text-xs">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="mp-label">Company / Host</label>
                        <input type="text" name="organization" value="{{ old('organization') }}"
                               placeholder="Optional org / family" class="mp-input !py-2.5 !text-xs">
                    </div>
                    <div>
                        <label class="mp-label">Email (Quotation)</label>
                        <input type="email" name="contact_email" value="{{ old('contact_email') }}"
                               placeholder="Optional email" class="mp-input !py-2.5 !text-xs">
                    </div>
                </div>

                <div>
                    <label class="mp-label">AV, Dietary &amp; Setup Notes</label>
                    <textarea name="special_requests" rows="2" maxlength="1000"
                              placeholder="Stage backdrop, microphones, projector, dietary notes..."
                              class="mp-input !py-2.5 !text-xs resize-none">{{ $prefillDesc ?? '' }}</textarea>
                </div>

                <div class="mp-note">
                    <i class="bi bi-building-check text-[color:var(--mp-gold)] text-sm mt-0.5 shrink-0"></i>
                    <p class="text-[11.5px] text-[#C8C2B5] leading-relaxed">
                        <strong class="text-[#F6F1E7]">Front Desk Appointment Required:</strong> This form logs a <strong class="text-[color:var(--mp-gold-bright)]">tentative date hold</strong>. Please visit the <strong>Front Office Desk (Ground Floor Lobby)</strong> to arrange an in-person appointment for hall viewing, contract, and confirmation.
                    </p>
                </div>

                <button type="submit" class="mp-btn w-full">
                    <i class="bi bi-calendar-check"></i>
                    <span>Request Tentative Hold &amp; FO Appointment</span>
                </button>
            </form>
        </section>

    </div>

    {{-- Bottom Navigation Bar --}}
    @include('guest.partials.bottom-nav', ['active' => ''])

</div>

<script>
let selectedHallRoom = 'AB';

function switchVenue(code) {
    document.getElementById('formVenueInput').value = code;

    const venues = ['FH-01', 'FH-ROOM', 'DJP-01'];
    const activeCls = "p-2.5 rounded-2xl border text-left transition-all cursor-pointer bg-[rgba(201,162,74,0.1)] border-[color:var(--mp-line-strong)]";
    const idleCls   = "p-2.5 rounded-2xl border text-left transition-all cursor-pointer bg-[#0E1523] border-[color:var(--mp-hairline)]";

    venues.forEach(v => {
        const card = document.getElementById('venueCard-' + v);
        const btn  = document.getElementById('tabBtn-' + v);
        if (card) card.classList.toggle('hidden', v !== code);
        if (btn) btn.className = (v === code) ? activeCls : idleCls;
    });

    updateSelectedVenueSummary(code);
}

function selectHallRoom(rmCode) {
    selectedHallRoom = rmCode;
    const input = document.getElementById('formHallRoomInput');
    if (input) input.value = rmCode;

    document.querySelectorAll('.hall-room-chip').forEach(btn => {
        btn.classList.toggle('is-active', btn.dataset.hallRoom === rmCode);
    });

    if (document.getElementById('formVenueInput')?.value !== 'FH-ROOM') {
        switchVenue('FH-ROOM');
    } else {
        updateSelectedVenueSummary('FH-ROOM');
    }
}

function updateSelectedVenueSummary(code) {
    const summaryEl = document.getElementById('selectedVenueSummary');
    if (!summaryEl) return;
    if (code === 'FH-ROOM') {
        summaryEl.textContent = `1st Floor Function Hall · Room ${selectedHallRoom} (50+ pax)`;
    } else if (code === 'DJP-01') {
        summaryEl.textContent = 'DJP Events Hall (DJP-01 · 40–80 pax)';
    } else {
        summaryEl.textContent = 'Event Plaza · Main Function Hall (FH-01 · 150–250 pax)';
    }
}

function selectCateringPackage(pkgValue) {
    const sel = document.getElementById('cateringPackageSelect');
    if (sel) {
        sel.value = pkgValue;
    }
    syncCateringCards(pkgValue);
}

function syncCateringCards(pkgValue) {
    const activeCardCls = "catering-pkg-btn p-2.5 rounded-xl bg-[rgba(201,162,74,0.12)] border border-[color:var(--mp-line-strong)] text-center cursor-pointer transition-all";
    const idleCardCls   = "catering-pkg-btn p-2.5 rounded-xl bg-[#080C14]/85 border border-[color:var(--mp-hairline)] text-center cursor-pointer transition-all hover:border-[color:var(--mp-line)]";

    document.querySelectorAll('.catering-pkg-btn').forEach(btn => {
        const isSelected = btn.dataset.cateringCard === pkgValue;
        btn.className = isSelected ? activeCardCls : idleCardCls;
        const t = btn.querySelector('.pkg-title');
        const p = btn.querySelector('.pkg-price');
        const d = btn.querySelector('.pkg-desc');
        if (t) t.className = isSelected ? "pkg-title text-[11px] text-[color:var(--mp-gold-bright)] font-medium" : "pkg-title text-[11px] text-[#98A0AE]";
        if (p) p.className = isSelected ? "pkg-price font-serif font-semibold text-sm text-[color:var(--mp-gold-bright)] mt-0.5 mp-num" : "pkg-price font-serif font-semibold text-sm text-[#F6F1E7] mt-0.5 mp-num";
        if (d) d.className = isSelected ? "pkg-desc text-[11px] text-[#C8C2B5] mt-1 leading-tight" : "pkg-desc text-[11px] text-[#7D8696] mt-1 leading-tight";
    });

    updateCateringEstimate();
}

function updateCateringEstimate() {
    const sel = document.getElementById('cateringPackageSelect');
    const qtyEl = document.getElementById('guestCountInput');
    const badge = document.getElementById('cateringEstimateBadge');
    if (!sel || !badge) return;

    const pax = Math.max(0, parseInt(qtyEl ? qtyEl.value : '80', 10) || 0);
    const priceMap = {
        'Cocktails & Heavy Merienda (₱350/pax)': 350,
        'Executive Seminar Package (₱450/pax)': 450,
        'Grand Banquet Buffet (₱550/pax)': 550,
        'No Catering / Venue Only': 0
    };
    const rate = priceMap[sel.value] ?? 0;
    if (rate > 0 && pax > 0) {
        badge.textContent = 'Est. ₱' + (rate * pax).toLocaleString();
    } else {
        badge.textContent = 'Venue Only';
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const sel = document.getElementById('cateringPackageSelect');
    if (sel) syncCateringCards(sel.value);
});
</script>
@endsection
