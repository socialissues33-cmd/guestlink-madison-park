{{-- File: guestlink/resources/views/guest/maintenance.blade.php --}}
@extends('layouts.guest')
@section('title', 'Room Repairs')

@push('styles')
<style>
    @keyframes shimmerAnim {
        0% { background-position: -200% 0; }
        100% { background-position: 200% 0; }
    }
    .shimmer-bg {
        background: linear-gradient(90deg, rgba(201,162,74,0.08) 0%, rgba(201,162,74,0.26) 50%, rgba(201,162,74,0.08) 100%);
        background-size: 200% 100%;
        animation: shimmerAnim 2s infinite linear;
    }
    .field-auto-highlight {
        border-color: var(--mp-gold-bright) !important;
        box-shadow: 0 0 0 3px rgba(201, 162, 74, 0.22) !important;
        transition: all 0.3s ease;
    }
    /* Subtle architectural camera viewfinder brackets */
    .mp-viewfinder {
        position: relative;
        background: rgba(7, 11, 19, 0.72);
        border: 1px dashed rgba(201, 162, 74, 0.35);
        border-radius: 16px;
        transition: border-color .25s ease, background .25s ease;
    }
    .mp-viewfinder:hover, .mp-viewfinder.is-drag {
        border-color: var(--mp-gold-bright);
        background: rgba(201, 162, 74, 0.05);
    }
</style>
@endpush

@section('content')
<div class="mp-shell pb-28 antialiased">

    {{-- 1. Shared Sticky Frosted Header --}}
    @include('guest.partials.header', ['subtitle' => 'Room Repairs'])

    <div class="px-4 pt-4 space-y-5">

        {{-- Editorial Hero Banner --}}
        <section class="mp-page-hero p-5 min-h-[126px] flex flex-col justify-end"
                 style="background-image: linear-gradient(135deg, rgba(8,12,20,.92) 0%, rgba(8,12,20,.72) 100%), url('{{ asset('images/hotel/madison-facade-driveway.jpg') }}'); background-position: center; background-size: cover;">
            <p class="mp-eyebrow">Room {{ $room->room_number }} &middot; Engineering &amp; HVAC</p>
            <h1 class="font-serif text-[1.35rem] font-semibold text-[#F6F1E7] leading-tight mt-1">
                Maintenance Request
            </h1>
            <p class="text-xs text-[#C8C2B5] mt-1">
                Snap a photo &mdash; Gemini AI inspects the issue and dispatches the right technician.
            </p>
        </section>

        {{-- Error Notification Banner --}}
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

        {{-- Main Maintenance Form Card --}}
        <div class="mp-surface p-5">
            <form method="POST" action="{{ route('guest.maintenance.store', $token) }}" enctype="multipart/form-data" id="maintenanceForm" class="space-y-5">
                @csrf

                {{-- Photo Evidence First (Visual-First UX) --}}
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label class="mp-label !mb-0 flex items-center gap-1.5">
                            <span>Photo Evidence</span>
                            <span class="normal-case tracking-normal text-[11px] font-medium text-[color:var(--mp-gold)]">(Recommended)</span>
                        </label>
                        <span class="text-[11px] text-[#98A0AE] inline-flex items-center gap-1">
                            <i class="bi bi-stars text-[color:var(--mp-gold)]"></i> AI Vision
                        </span>
                    </div>

                    <input type="file" id="cameraInput" accept="image/*" capture="environment" class="hidden">
                    <input type="file" id="galleryInput" name="photo" accept="image/*" class="hidden">

                    {{-- Interactive Viewfinder Dropzone --}}
                    <div id="dropzoneArea" class="mp-viewfinder p-5 text-center cursor-pointer">
                        <div class="flex flex-col items-center space-y-2.5">
                            <span class="mp-icon !w-12 !h-12 !rounded-full text-lg">
                                <i class="bi bi-camera"></i>
                            </span>
                            <div>
                                <div class="text-[13px] font-medium text-[#F6F1E7]">Capture or upload a photo</div>
                                <p class="text-[11.5px] text-[#7D8696] mt-0.5">Auto-detects leaks, AC faults &amp; fixture damage</p>
                            </div>

                            <div id="photoActionRow" class="grid grid-cols-2 gap-2 w-full pt-2">
                                <button type="button" id="btnCaptureCamera"
                                        class="mp-btn !min-h-[40px] !py-2 !px-3 !text-xs">
                                    <i class="bi bi-camera-fill"></i> Take Photo
                                </button>
                                <button type="button" id="btnUploadGallery"
                                        class="mp-btn-ghost !min-h-[40px] !py-2 !px-3 !text-xs">
                                    <i class="bi bi-image"></i> Gallery
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Live Photo Preview Container --}}
                    <div id="photoPreviewContainer" class="hidden mt-3 rounded-2xl border border-[color:var(--mp-line)] bg-[#080C14] p-3 relative">
                        <div class="relative rounded-xl overflow-hidden max-h-56 bg-black flex items-center justify-center border border-[color:var(--mp-hairline)]">
                            <img id="photoPreviewImg" src="" alt="Captured problem preview" class="w-full max-h-56 object-cover">
                            <button type="button" id="btnRemovePhoto" aria-label="Remove Photo" title="Remove Photo"
                                    class="absolute top-2.5 right-2.5 w-8 h-8 rounded-full bg-[#080C14]/85 backdrop-blur-md border border-white/15 text-[#E7E2D7] hover:bg-rose-600 hover:border-rose-500 hover:text-white flex items-center justify-center text-xs transition-colors cursor-pointer">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </div>

                        <div class="flex items-center justify-between mt-3 pt-2.5 border-t border-[color:var(--mp-hairline)] text-xs">
                            <div class="flex items-center gap-1.5 text-[#C8C2B5] min-w-0">
                                <i class="bi bi-check-circle-fill text-emerald-400 shrink-0"></i>
                                <span id="photoFileName" class="truncate max-w-[170px] font-mono text-[11px]">photo.jpg</span>
                            </div>
                            <button type="button" id="btnReanalyze" class="mp-chip is-gold !py-1 !px-2.5 !text-[11px]">
                                <i class="bi bi-arrow-repeat"></i> Re-Scan
                            </button>
                        </div>

                        <div class="grid grid-cols-2 gap-2 mt-2.5">
                            <button type="button" id="btnRetakePhotoAction" class="mp-btn-ghost !min-h-[36px] !py-1.5 !text-xs">
                                <i class="bi bi-camera"></i> Retake
                            </button>
                            <button type="button" id="btnBrowseReplaceAction" class="mp-btn-ghost !min-h-[36px] !py-1.5 !text-xs">
                                <i class="bi bi-image"></i> Replace
                            </button>
                        </div>
                    </div>

                    {{-- Animated AI Diagnostic Banner --}}
                    <div id="aiScanBanner" class="hidden mt-3 rounded-2xl border border-[color:var(--mp-line-strong)] bg-[rgba(201,162,74,0.06)] p-4 space-y-2.5">
                        <div class="flex items-center justify-between gap-2">
                            <div class="flex items-center gap-2 min-w-0">
                                <span class="inline-block w-3.5 h-3.5 border-2 border-[color:var(--mp-gold)] border-t-transparent rounded-full animate-spin shrink-0" id="aiScanSpinner"></span>
                                <span id="aiScanIcon" class="hidden text-[color:var(--mp-gold-bright)]"><i class="bi bi-stars"></i></span>
                                <span id="aiScanTitle" class="text-xs font-semibold text-[#F6F1E7] truncate">
                                    Inspecting photo with Gemini AI...
                                </span>
                            </div>
                            <span id="aiScanBadge" class="hidden mp-status-pill !py-0.5 !px-2 !text-[10px]">
                                Ready
                            </span>
                        </div>

                        <div id="aiScanShimmer" class="space-y-2">
                            <div class="h-2 rounded-full shimmer-bg w-full"></div>
                            <div class="h-2 rounded-full shimmer-bg w-3/4"></div>
                            <p class="text-[11.5px] text-[#98A0AE] pt-0.5">
                                Identifying fixture type, severity &amp; recommended department...
                            </p>
                        </div>

                        <div id="aiScanResult" class="hidden pt-1 text-xs space-y-2"></div>
                    </div>
                </div>

                {{-- Common Maintenance Quick Chips --}}
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label class="mp-label !mb-0">Common Issues</label>
                        <span class="text-[11px] text-[#7D8696]">Tap to prefill</span>
                    </div>
                    <div class="flex flex-wrap gap-1.5">
                        <button type="button" class="quick-chip mp-chip"
                                data-category="air" data-text="Air conditioning is blowing warm air and not cooling properly despite thermostat set to low.">
                            <i class="bi bi-snow"></i> AC Not Cooling
                        </button>
                        <button type="button" class="quick-chip mp-chip"
                                data-category="plumb" data-text="Shower heater has no hot water. Water remains cold after running for minutes.">
                            <i class="bi bi-thermometer-low"></i> No Hot Water
                        </button>
                        <button type="button" class="quick-chip mp-chip"
                                data-category="plumb" data-text="Active water leak under bathroom sink/faucet causing water pooling on floor.">
                            <i class="bi bi-droplet"></i> Water Leak
                        </button>
                        <button type="button" class="quick-chip mp-chip"
                                data-category="electr" data-text="Main bathroom light fixture is flickering and making buzzing sound.">
                            <i class="bi bi-lightbulb"></i> Flickering Light
                        </button>
                        <button type="button" class="quick-chip mp-chip"
                                data-category="internet" data-text="Television cannot turn on or remote control is unresponsive.">
                            <i class="bi bi-tv"></i> TV / Remote
                        </button>
                        <button type="button" class="quick-chip mp-chip"
                                data-category="door" data-text="Electronic door lock is flashing red warning light and difficult to unlock.">
                            <i class="bi bi-key"></i> Door Lock
                        </button>
                        <button type="button" class="quick-chip mp-chip"
                                data-category="electr" data-text="Bedside electrical power outlet is not supplying power to devices.">
                            <i class="bi bi-plug"></i> Dead Outlet
                        </button>
                    </div>
                </div>

                {{-- Category & Guest Name --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="categorySelect" class="mp-label">Category</label>
                        <select name="category_id" id="categorySelect" class="mp-input appearance-none">
                            <option value="">— Select —</option>
                            @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" data-name="{{ strtolower($cat->name) }}" {{ old('category_id') == $cat->id ? 'selected' : '' }}>
                                {{ $cat->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="guestNameInput" class="mp-label">Your Name (Optional)</label>
                        <input type="text" id="guestNameInput" name="guest_name" value="{{ old('guest_name') }}"
                               placeholder="e.g. Maria Clara" class="mp-input">
                    </div>
                </div>

                {{-- Problem Description --}}
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label for="descTextarea" class="mp-label !mb-0">
                            Problem Details <span class="text-rose-400" id="descRequiredAsterisk">*</span>
                        </label>
                        <span id="charCounter" class="text-[11px] text-[#7D8696] mp-num">0 / 1000</span>
                    </div>
                    <textarea name="description" id="descTextarea" rows="3" required minlength="5" maxlength="1000"
                              placeholder="Describe what needs repair (or attach a photo above to auto-fill)..."
                              class="mp-input resize-none">{{ old('description', request('desc')) }}</textarea>
                    @error('description')
                        <div class="text-xs text-rose-400 mt-1.5">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Action Buttons --}}
                <div class="grid grid-cols-3 gap-2.5 pt-1">
                    <a href="{{ route('guest.landing', $token) }}" class="mp-btn-ghost col-span-1">
                        <i class="bi bi-arrow-left"></i> Back
                    </a>
                    <button type="submit" id="submitBtn" class="mp-btn col-span-2">
                        <i class="bi bi-send"></i>
                        <span>Submit Report</span>
                    </button>
                </div>
            </form>
        </div>

        {{-- Restrained Emergency Notice --}}
        <div class="mp-note is-danger">
            <i class="bi bi-exclamation-triangle text-rose-400 text-sm mt-0.5 shrink-0"></i>
            <p class="text-xs text-[#B9B3A3] leading-relaxed">
                <strong class="text-rose-300 font-semibold">Urgent hazard?</strong> For flooding, gas odor, or electrical sparks, dial Front Desk at <a href="tel:0" class="text-[color:var(--mp-gold-bright)] font-semibold underline">Ext. 0</a> immediately.
            </p>
        </div>

    </div>

    {{-- 2. Fixed Bottom Navigation Bar --}}
    @include('guest.partials.bottom-nav', ['active' => 'maintenance'])

</div>

@push('scripts')
<script>
const textarea = document.getElementById('descTextarea');
const counter = document.getElementById('charCounter');
const catSelect = document.getElementById('categorySelect');
const cameraInput = document.getElementById('cameraInput');
const galleryInput = document.getElementById('galleryInput');
const btnCaptureCamera = document.getElementById('btnCaptureCamera');
const btnUploadGallery = document.getElementById('btnUploadGallery');
const dropzoneArea = document.getElementById('dropzoneArea');
const photoPreviewContainer = document.getElementById('photoPreviewContainer');
const photoPreviewImg = document.getElementById('photoPreviewImg');
const photoFileName = document.getElementById('photoFileName');
const btnRemovePhoto = document.getElementById('btnRemovePhoto');
const btnReanalyze = document.getElementById('btnReanalyze');

const aiScanBanner = document.getElementById('aiScanBanner');
const aiScanSpinner = document.getElementById('aiScanSpinner');
const aiScanIcon = document.getElementById('aiScanIcon');
const aiScanTitle = document.getElementById('aiScanTitle');
const aiScanBadge = document.getElementById('aiScanBadge');
const aiScanShimmer = document.getElementById('aiScanShimmer');
const aiScanResult = document.getElementById('aiScanResult');

let currentAttachedFile = null;

function updateCount() {
    const len = textarea.value.length;
    counter.textContent = `${len} / 1000`;
    counter.className = len > 0 && len < 5
        ? 'text-[11px] text-rose-400 mp-num'
        : 'text-[11px] text-[#7D8696] mp-num';
}

textarea.addEventListener('input', updateCount);
updateCount();

document.querySelectorAll('.quick-chip').forEach(chip => {
    chip.addEventListener('click', function() {
        const text = this.getAttribute('data-text');
        const catKey = this.getAttribute('data-category');

        if (!textarea.value.trim() || textarea.value.length < 5) {
            textarea.value = text;
        } else if (!textarea.value.includes(text)) {
            textarea.value = textarea.value.trim() + ' ' + text;
        }

        if (catSelect && catKey) {
            for (let i = 0; i < catSelect.options.length; i++) {
                const optText = (catSelect.options[i].getAttribute('data-name') || catSelect.options[i].text).toLowerCase();
                if (optText.includes(catKey)) {
                    catSelect.selectedIndex = i;
                    break;
                }
            }
        }

        this.classList.add('is-active');
        setTimeout(() => this.classList.remove('is-active'), 450);

        updateCount();
        textarea.focus();
    });
});

btnCaptureCamera.addEventListener('click', (e) => { e.stopPropagation(); cameraInput.click(); });
btnUploadGallery.addEventListener('click', (e) => { e.stopPropagation(); galleryInput.click(); });
dropzoneArea.addEventListener('click', () => cameraInput.click());

const btnRetakePhotoAction = document.getElementById('btnRetakePhotoAction');
const btnBrowseReplaceAction = document.getElementById('btnBrowseReplaceAction');
if (btnRetakePhotoAction) btnRetakePhotoAction.addEventListener('click', () => cameraInput.click());
if (btnBrowseReplaceAction) btnBrowseReplaceAction.addEventListener('click', () => galleryInput.click());

dropzoneArea.addEventListener('dragover', (e) => { e.preventDefault(); dropzoneArea.classList.add('is-drag'); });
dropzoneArea.addEventListener('dragleave', () => { dropzoneArea.classList.remove('is-drag'); });
dropzoneArea.addEventListener('drop', (e) => {
    e.preventDefault();
    dropzoneArea.classList.remove('is-drag');
    if (e.dataTransfer.files && e.dataTransfer.files[0]) {
        handlePhotoSelected(e.dataTransfer.files[0], galleryInput);
    }
});

function compressImageBeforeUpload(file, maxWidth = 1200, quality = 0.82) {
    return new Promise((resolve) => {
        if (!file || !file.type || !file.type.startsWith('image/') || file.size < 250 * 1024) {
            resolve(file);
            return;
        }
        const img = new Image();
        const objectUrl = URL.createObjectURL(file);
        img.onload = () => {
            URL.revokeObjectURL(objectUrl);
            let width = img.width;
            let height = img.height;
            if (width > maxWidth || height > maxWidth) {
                if (width > height) {
                    height = Math.round((height * maxWidth) / width);
                    width = maxWidth;
                } else {
                    width = Math.round((width * maxWidth) / height);
                    height = maxWidth;
                }
            }
            const canvas = document.createElement('canvas');
            canvas.width = width;
            canvas.height = height;
            const ctx = canvas.getContext('2d');
            ctx.drawImage(img, 0, 0, width, height);
            canvas.toBlob((blob) => {
                if (blob && blob.size < file.size) {
                    const compressedFile = new File([blob], file.name.replace(/\.[^/.]+$/, "") + ".jpg", {
                        type: 'image/jpeg',
                        lastModified: Date.now()
                    });
                    resolve(compressedFile);
                } else {
                    resolve(file);
                }
            }, 'image/jpeg', quality);
        };
        img.onerror = () => { URL.revokeObjectURL(objectUrl); resolve(file); };
        img.src = objectUrl;
    });
}

async function handlePhotoSelected(file, sourceInput) {
    if (!file) return;

    currentAttachedFile = file;
    photoFileName.textContent = `${file.name || 'photo.jpg'} (${(file.size / 1024).toFixed(0)} KB)`;

    const reader = new FileReader();
    reader.onload = function(e) {
        photoPreviewImg.src = e.target.result;
        photoPreviewContainer.classList.remove('hidden');
        dropzoneArea.classList.add('hidden');
    };
    reader.readAsDataURL(file);

    const optimized = await compressImageBeforeUpload(file);
    currentAttachedFile = optimized;

    try {
        const dt = new DataTransfer();
        dt.items.add(optimized);
        sourceInput.files = dt.files;
    } catch (e) {}

    if (sourceInput === cameraInput) {
        cameraInput.name = 'photo';
        galleryInput.removeAttribute('name');
    } else {
        galleryInput.name = 'photo';
        cameraInput.removeAttribute('name');
    }

    const descArea = document.getElementById('descTextarea');
    if (descArea) {
        descArea.removeAttribute('required');
        descArea.removeAttribute('minlength');
        const asterisk = document.getElementById('descRequiredAsterisk');
        if (asterisk) asterisk.classList.add('hidden');
    }

    analyzePhotoWithAI(optimized);
}

cameraInput.addEventListener('change', function() {
    if (this.files && this.files[0]) handlePhotoSelected(this.files[0], cameraInput);
});
galleryInput.addEventListener('change', function() {
    if (this.files && this.files[0]) handlePhotoSelected(this.files[0], galleryInput);
});

btnRemovePhoto.addEventListener('click', function(e) {
    e.stopPropagation();
    currentAttachedFile = null;
    cameraInput.value = '';
    galleryInput.value = '';
    galleryInput.name = 'photo';
    cameraInput.removeAttribute('name');
    photoPreviewImg.src = '';
    photoPreviewContainer.classList.add('hidden');
    dropzoneArea.classList.remove('hidden');
    aiScanBanner.classList.add('hidden');

    const descArea = document.getElementById('descTextarea');
    if (descArea) {
        descArea.setAttribute('required', 'required');
        descArea.setAttribute('minlength', '5');
        const asterisk = document.getElementById('descRequiredAsterisk');
        if (asterisk) asterisk.classList.remove('hidden');
    }
});

btnReanalyze.addEventListener('click', function(e) {
    e.stopPropagation();
    if (currentAttachedFile) analyzePhotoWithAI(currentAttachedFile);
});

async function analyzePhotoWithAI(file) {
    aiScanBanner.classList.remove('hidden');
    aiScanSpinner.classList.remove('hidden');
    aiScanIcon.classList.add('hidden');
    aiScanBadge.classList.add('hidden');
    aiScanShimmer.classList.remove('hidden');
    aiScanResult.classList.add('hidden');
    aiScanTitle.textContent = 'Inspecting photo with Gemini AI...';

    const optimizedFile = await compressImageBeforeUpload(file);
    const formData = new FormData();
    formData.append('photo', optimizedFile);
    formData.append('filename', optimizedFile.name || file.name || '');
    formData.append('notes', textarea.value || '');
    formData.append('_token', '{{ csrf_token() }}');

    fetch('{{ route("guest.maintenance.analyze", $token) }}', {
        method: 'POST',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData,
    })
    .then(res => {
        if (!res.ok) throw new Error('HTTP ' + res.status);
        return res.json();
    })
    .then(data => {
        aiScanSpinner.classList.add('hidden');
        aiScanIcon.classList.remove('hidden');
        aiScanBadge.classList.remove('hidden');
        aiScanShimmer.classList.add('hidden');
        aiScanResult.classList.remove('hidden');
        aiScanTitle.textContent = 'Visual Diagnostic Complete';

        const priorityColor = (data.priority || '').toLowerCase() === 'urgent'
            ? 'bg-rose-500/15 text-rose-300 border-rose-500/35'
            : ((data.priority || '').toLowerCase() === 'high'
                ? 'bg-[rgba(201,162,74,0.15)] text-[#E6CA7A] border-[rgba(201,162,74,0.4)]'
                : 'bg-sky-500/15 text-sky-300 border-sky-500/30');

        aiScanResult.innerHTML = `
            <div class="font-serif font-semibold text-sm text-[#F6F1E7]">${escapeHtml(data.issue_detected || 'Defect Detected')}</div>
            <div class="flex items-center gap-1.5 flex-wrap pt-0.5">
                <span class="px-2 py-0.5 rounded-md text-[11px] font-semibold uppercase border ${priorityColor}">
                    ${escapeHtml(data.priority || 'High')} Priority
                </span>
                <span class="px-2 py-0.5 rounded-md text-[11px] font-medium bg-white/[0.04] text-[#E6CA7A] border border-white/10">
                    ${escapeHtml(data.department || 'Maintenance')}
                </span>
                <span class="px-2 py-0.5 rounded-md text-[11px] font-medium bg-white/[0.04] text-[#C8C2B5] border border-white/10">
                    ${escapeHtml(data.category || 'Plumbing')}
                </span>
            </div>
            <div class="text-xs text-[#98A0AE] leading-relaxed pt-1">
                ${escapeHtml(data.reason || 'Visual evidence analyzed by Madison Park facilities AI.')}
            </div>
        `;

        if (data.category && catSelect) {
            let matched = false;
            const searchCat = data.category.toLowerCase();
            for (let i = 1; i < catSelect.options.length; i++) {
                const optVal = catSelect.options[i].value;
                const optText = (catSelect.options[i].getAttribute('data-name') || catSelect.options[i].text).toLowerCase();
                if ((data.category_id && optVal == data.category_id) || optText.includes(searchCat) || searchCat.includes(optText)) {
                    catSelect.selectedIndex = i;
                    matched = true;
                    break;
                }
            }
            if (!matched) {
                const arrangedOpt = document.createElement('option');
                arrangedOpt.value = data.category_id || data.category;
                arrangedOpt.setAttribute('data-name', searchCat);
                arrangedOpt.textContent = `${data.category} (AI Classified)`;
                catSelect.appendChild(arrangedOpt);
                arrangedOpt.selected = true;
            }
            catSelect.classList.add('field-auto-highlight');
            setTimeout(() => catSelect.classList.remove('field-auto-highlight'), 1800);
        }

        if (data.suggested_description) {
            const currentVal = textarea.value.trim();
            const isGeneric = !currentVal || currentVal.length < 15 || currentVal.includes('Photo captured of maintenance problem');
            if (isGeneric) {
                textarea.value = data.suggested_description;
            } else if (!currentVal.includes(data.suggested_description)) {
                textarea.value = currentVal + "\n\n[AI Diagnostic Inspection]: " + data.suggested_description;
            }
            updateCount();
            textarea.classList.add('field-auto-highlight');
            setTimeout(() => textarea.classList.remove('field-auto-highlight'), 1800);
        }
    })
    .catch(() => {
        aiScanSpinner.classList.add('hidden');
        aiScanIcon.classList.remove('hidden');
        aiScanShimmer.classList.add('hidden');
        aiScanResult.classList.remove('hidden');
        aiScanTitle.textContent = 'Photo Attached';
        aiScanResult.innerHTML = '<span class="text-[#C8C2B5]">Photo evidence attached. Engineering staff will inspect upon submission.</span>';
    });
}

window.addEventListener('DOMContentLoaded', () => {
    updateCount();
    const urlParams = new URLSearchParams(window.location.search);
    const catParam = urlParams.get('category');
    if (catParam && catSelect) {
        for (let i = 0; i < catSelect.options.length; i++) {
            const optText = (catSelect.options[i].getAttribute('data-name') || catSelect.options[i].text).toLowerCase();
            if (optText.includes(catParam.toLowerCase())) {
                catSelect.selectedIndex = i;
                break;
            }
        }
    }
});

document.getElementById('maintenanceForm').addEventListener('submit', function() {
    const btn = document.getElementById('submitBtn');
    btn.innerHTML = '<span class="inline-block w-3.5 h-3.5 border-2 border-[#140F04] border-t-transparent rounded-full animate-spin mr-1.5"></span>Sending Report...';
    btn.disabled = true;
});
</script>
@endpush
@endsection
