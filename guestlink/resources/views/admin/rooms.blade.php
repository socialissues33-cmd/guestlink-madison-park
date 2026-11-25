@extends('layouts.app')
@section('title', 'Rooms & QR Codes')
@section('page-title', 'Room & QR Management')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div class="d-flex align-items-center gap-2 flex-grow-1 flex-md-grow-0" style="min-width:min(280px, 100%);">
        <form method="GET" action="{{ route('admin.rooms') }}" class="m-0 d-flex align-items-center gap-2 w-100">
            <div style="position:relative; width:100%;">
                <i class="bi bi-search" style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:#8899AA; font-size:0.85rem;"></i>
                <input type="text"
                       id="roomsSearchInput"
                       name="search"
                       class="form-control"
                       placeholder="Search Room #, Type, Floor..."
                       value="{{ request('search') }}"
                       onkeyup="liveFilterRooms(this.value)"
                       style="padding-left:34px; background:rgba(11,20,34,0.75); border:1px solid rgba(201,168,76,0.25); color:#fff; border-radius:20px; font-size:0.82rem;"
                       autocomplete="off">
            </div>
            @if(request('search'))
            <a href="{{ route('admin.rooms') }}" class="btn-ghost px-2 py-1 text-decoration-none" style="font-size:0.75rem; border-radius:15px;">
                ✕ Clear
            </a>
            @endif
        </form>
    </div>

    <div class="d-flex align-items-center gap-2 flex-wrap">
        {{-- 1-Click Switch: Local Wi-Fi Network QR (Option A) vs Public Tunnel QR --}}
        @php
            $currentQrMode = $qrMode ?? 'local';
            $isLocalMode = $currentQrMode === 'local';
        @endphp
        <form method="POST" action="{{ route('admin.rooms.qr.mode') }}" class="m-0 d-inline">
            @csrf
            <input type="hidden" name="qr_mode" value="{{ $isLocalMode ? 'public' : 'local' }}">
            <button type="submit" class="btn-ghost px-3 py-2" style="border-radius:10px; color:{{ $isLocalMode ? '#86EFAC' : '#93C5FD' }}; border-color:{{ $isLocalMode ? 'rgba(134,239,172,0.4)' : 'rgba(147,197,253,0.4)' }};" title="Click to toggle default QR mode between Local Wi-Fi Hotspot (Option A) and Public Cloudflare Tunnel">
                <i class="bi {{ $isLocalMode ? 'bi-wifi' : 'bi-globe' }} me-1"></i>
                QR Mode: <strong>{{ $isLocalMode ? 'Local Wi-Fi (' . ($detectedLanIp ?? 'LAN') . ')' : 'Public Tunnel' }}</strong>
                <span class="ms-1 opacity-75" style="font-size:0.7rem;">[Switch]</span>
            </button>
        </form>

        {{-- 1-Click Printable 3-Room Defense Sheet (Option A: Rooms 201, 202, 203 + Free Wi-Fi QR) --}}
        <a href="{{ route('admin.rooms.qr.sheet') }}" class="btn-gold px-3 py-2 text-decoration-none" style="border-radius:10px; font-size:0.82rem;" title="Print 3 Room QR Cards (201, 202, 203) + Free Wi-Fi QR for Capstone Defense">
            <i class="bi bi-printer-fill me-1"></i> Print 3 Defense QRs
        </a>

        <button type="button" class="btn-ghost px-3 py-2" data-bs-toggle="modal" data-bs-target="#wifiQrResetModal" style="border-radius:10px; color:#E8D08D; border-color:rgba(201,168,76,0.35);">
            <i class="bi bi-router me-1"></i> Wi-Fi &amp; Hotspot IP
        </button>

        @role('admin')
        <button class="btn-gold px-4 py-2" data-bs-toggle="modal" data-bs-target="#addRoomModal" style="border-radius:10px;">
            <i class="bi bi-plus-circle me-1"></i> Add Room
        </button>
        @endrole
    </div>
</div>

<div class="table-card">
    <div class="table-responsive">
        <table class="data-table" id="roomsTable">
            <thead>
                <tr>
                    <th>Room No.</th>
                    <th>Type</th>
                    <th>Floor</th>
                    <th>Description</th>
                    <th>QR Token</th>
                    <th>Status</th>
                    <th style="min-width:180px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rooms as $room)
                <tr class="room-row">
                    <td>
                        <span class="room-badge-formal" style="background:rgba(201,168,76,0.1); border-color:rgba(201,168,76,0.25); color:#E8D08D; font-weight:700; font-size:0.82rem;">
                            Room {{ $room->room_number }}
                        </span>
                    </td>
                    <td style="font-size:0.82rem; font-weight:600;">{{ $room->room_type }}</td>
                    <td style="font-size:0.82rem; color:#8899AA;">Floor {{ $room->floor }}</td>
                    <td style="font-size:0.78rem; color:#8899AA; max-width:150px;">{{ $room->description ?: '—' }}</td>
                    <td><code style="font-size:0.7rem; color:#8899AA;">{{ substr($room->qr_token, 0, 10) }}...</code></td>
                    <td>
                        @if($room->is_active)
                        <span class="badge bg-success">Active</span>
                        @else
                        <span class="badge bg-secondary">Inactive</span>
                        @endif
                    </td>
                    <td>
                        <div class="d-flex align-items-center gap-1 flex-wrap">
                            {{-- Download Print QR --}}
                            <a href="{{ route('admin.rooms.qr.download', $room) }}" class="btn-gold px-2 py-1" style="font-size:0.72rem; border-radius:6px;" title="Print/Download QR Code">
                                <i class="bi bi-qr-code"></i> QR
                            </a>

                            {{-- Room Folio / Bill Summary --}}
                            <a href="{{ route('admin.rooms.folio', $room) }}" class="btn-ghost px-2 py-1" style="font-size:0.72rem; color:#E6CA7A; border-color:rgba(201,162,74,0.35);" title="View Room Folio & Charges">
                                <i class="bi bi-receipt-cutoff"></i> Folio
                            </a>

                            @role('admin')
                            {{-- Edit Room Modal Trigger --}}
                            <button type="button" class="btn-ghost px-2 py-1" style="font-size:0.72rem; color:#93c5fd; border-color:rgba(147,197,253,0.3);" title="Edit Room Details" data-bs-toggle="modal" data-bs-target="#editRoomModal{{ $room->id }}">
                                <i class="bi bi-pencil-square"></i>
                            </button>
                            @endrole

                            {{-- Toggle Status --}}
                            <form method="POST" action="{{ route('admin.rooms.toggle', $room) }}" class="d-inline">
                                @csrf
                                <button type="submit" class="btn-ghost px-2 py-1" style="font-size:0.72rem;" title="{{ $room->is_active ? 'Deactivate Room (Check-Out)' : 'Activate Room (Check-In)' }}">
                                    <i class="bi {{ $room->is_active ? 'bi-toggle-on text-success' : 'bi-toggle-off text-muted' }}"></i>
                                </button>
                            </form>

                            {{-- Regenerate QR --}}
                            <form method="POST" action="{{ route('admin.rooms.qr', $room) }}" class="d-inline">
                                @csrf
                                <button type="submit" class="btn-ghost px-2 py-1" style="font-size:0.72rem;" title="Regenerate QR Code" onclick="return confirm('Regenerate QR? Old printed QR codes will stop routing.')">
                                    <i class="bi bi-arrow-repeat"></i>
                                </button>
                            </form>

                            @role('admin')
                            {{-- Delete Room --}}
                            <form method="POST" action="{{ route('admin.rooms.destroy', $room) }}" class="d-inline" onsubmit="return confirm('Are you sure you want to permanently delete Room {{ $room->room_number }}? This action cannot be undone.')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-ghost px-2 py-1" style="font-size:0.72rem; color:#f87171; border-color:rgba(248,113,113,0.3);" title="Delete Room">
                                    <i class="bi bi-trash3"></i>
                                </button>
                            </form>
                            @endrole
                        </div>
                    </td>
                </tr>
                @empty
                <tr id="roomEmptyRow"><td colspan="7" style="text-align:center; color:#8899AA; padding:3rem;">No rooms found.</td></tr>
                @endforelse
                <tr id="roomNoMatchRow" style="display:none;"><td colspan="7" style="text-align:center; color:#8899AA; padding:2.5rem;">No matching rooms found.</td></tr>
            </tbody>
        </table>
    </div>
    @if($rooms->hasPages())
    <div class="p-3">{{ $rooms->links() }}</div>
    @endif
</div>

{{-- Edit Room Modals (rendered outside <tbody> for valid HTML5 DOM) --}}
@foreach($rooms as $room)
<div class="modal fade" id="editRoomModal{{ $room->id }}" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="background:#162236; border:1px solid rgba(201,168,76,0.3);">
            <div class="modal-header" style="border-color:rgba(201,168,76,0.2);">
                <h5 class="modal-title font-serif" style="color:#C9A84C;">Edit Room {{ $room->room_number }}</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('admin.rooms.update', $room) }}">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label" style="font-size:0.78rem; font-weight:600; color:#E8D08D;">Room Number</label>
                        <input type="text" name="room_number" class="form-control" value="{{ $room->room_number }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" style="font-size:0.78rem; font-weight:600; color:#E8D08D;">Room Type</label>
                        <select name="room_type" class="form-select" required>
                            @foreach(['Standard','Deluxe','Suite','Executive Luxury Suite','Family Room','Function Hall','Other'] as $t)
                            <option value="{{ $t }}" {{ $room->room_type === $t ? 'selected' : '' }}>{{ $t }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" style="font-size:0.78rem; font-weight:600; color:#E8D08D;">Floor</label>
                        <input type="number" name="floor" class="form-control" min="1" value="{{ $room->floor }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" style="font-size:0.78rem; font-weight:600; color:#E8D08D;">Description (Optional)</label>
                        <input type="text" name="description" class="form-control" value="{{ $room->description }}" placeholder="e.g. Garden View, High Floor...">
                    </div>
                    <div class="form-check form-switch mt-2">
                        <input type="hidden" name="is_active" value="0">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="activeCheck{{ $room->id }}" {{ $room->is_active ? 'checked' : '' }}>
                        <label class="form-check-label" for="activeCheck{{ $room->id }}" style="font-size:0.8rem; color:#cbd5e1;">Active and Available for Guests</label>
                    </div>
                </div>
                <div class="modal-footer" style="border-color:rgba(201,168,76,0.2);">
                    <button type="button" class="btn-ghost px-3 py-2" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn-gold px-4 py-2">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endforeach

@push('scripts')
<script>
    function liveFilterRooms(keyword) {
        const term = (keyword || '').toLowerCase().trim();
        const table = document.getElementById('roomsTable');
        if (!table) return;
        const rows = table.querySelectorAll('tbody tr.room-row');
        let visibleCount = 0;

        rows.forEach(row => {
            const text = row.innerText.toLowerCase();
            if (!term || text.includes(term)) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        const emptyRow = document.getElementById('roomNoMatchRow') || document.getElementById('roomEmptyRow');
        if (emptyRow) {
            emptyRow.style.display = (visibleCount === 0 && rows.length > 0) ? '' : 'none';
        }
    }
</script>
@endpush

{{-- Add Room Modal --}}
<div class="modal fade" id="addRoomModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="background:#162236; border:1px solid rgba(201,168,76,0.3);">
            <div class="modal-header" style="border-color:rgba(201,168,76,0.2);">
                <h5 class="modal-title font-serif" style="color:#C9A84C;">Add New Room</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('admin.rooms.store') }}">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label" style="font-size:0.78rem; font-weight:600; color:#E8D08D;">Room Number</label>
                        <input type="text" name="room_number" class="form-control" placeholder="e.g. 201, 301, 501" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" style="font-size:0.78rem; font-weight:600; color:#E8D08D;">Room Type</label>
                        <select name="room_type" class="form-select" required>
                            @foreach(['Standard','Deluxe','Suite','Executive Luxury Suite','Family Room','Function Hall','Other'] as $t)
                            <option value="{{ $t }}">{{ $t }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" style="font-size:0.78rem; font-weight:600; color:#E8D08D;">Floor</label>
                        <input type="number" name="floor" class="form-control" min="1" value="2" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" style="font-size:0.78rem; font-weight:600; color:#E8D08D;">Description (Optional)</label>
                        <input type="text" name="description" class="form-control" placeholder="Brief description...">
                    </div>
                </div>
                <div class="modal-footer" style="border-color:rgba(201,168,76,0.2);">
                    <button type="button" class="btn-ghost px-3 py-2" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn-gold px-4 py-2">Create Room &amp; QR</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Hotel-Wide Free Wi-Fi QR & Clean Reset Modal --}}
<div class="modal fade" id="wifiQrResetModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="background:#162236; border:1px solid rgba(201,168,76,0.3);">
            <div class="modal-header" style="border-color:rgba(201,168,76,0.2);">
                <h5 class="modal-title font-serif" style="color:#C9A84C;">
                    <i class="bi bi-wifi me-1"></i> Hotel Wi-Fi Scan-to-Reconnect &amp; Reset
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('admin.rooms.wifi.reset') }}">
                @csrf
                <div class="modal-body">
                    <div class="text-center mb-3 p-3 rounded-3" style="background:rgba(11,20,34,0.8); border:1px solid rgba(201,168,76,0.22);">
                        <div class="bg-white p-2 rounded-3 d-inline-block mb-2">
                            {!! $wifiQrMini ?? '' !!}
                        </div>
                        <div style="font-size:0.82rem; color:#F8FAFC; font-weight:700;">{{ $wifiSsid ?? 'MadisonPark_Guest' }}</div>
                        <div style="font-size:0.72rem; color:#94A3B8;">
                            Guests never type a voucher &mdash; they simply scan the QR code to connect or reconnect after a Wi-Fi reset.
                        </div>
                        @if(!empty($wifiLastReset))
                        <div style="font-size:0.68rem; color:#E8D08D; margin-top:4px;">
                            Last Wi-Fi Session Reset: {{ $wifiLastReset }}
                        </div>
                        @endif
                    </div>

                    <div class="mb-3">
                        <label class="form-label" style="font-size:0.78rem; font-weight:600; color:#E8D08D;">Hotel Wi-Fi Network Name (SSID)</label>
                        <input type="text" name="wifi_ssid" class="form-control" value="{{ $wifiSsid ?? 'MadisonPark_Guest' }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" style="font-size:0.78rem; font-weight:600; color:#E8D08D;">Embedded QR Passkey (Leave blank for Open Wi-Fi)</label>
                        <input type="text" name="wifi_password" id="wifiPassInput" class="form-control" value="{{ $wifiPassword ?? 'luxurywelcome' }}" placeholder="Leave empty for open no-password Wi-Fi">
                        <div style="font-size:0.7rem; color:#94A3B8; margin-top:4px;">
                            Embedded directly inside the QR code so guests only scan once to reconnect &mdash; zero manual typing.
                        </div>
                    </div>

                    <div class="row g-2">
                        <div class="col-sm-6">
                            <label class="form-label" style="font-size:0.78rem; font-weight:600; color:#E8D08D;">Default Room QR Mode</label>
                            <select name="qr_mode" class="form-select">
                                <option value="local" {{ ($qrMode ?? 'local') === 'local' ? 'selected' : '' }}>Local Wi-Fi / Hotspot (Option A)</option>
                                <option value="public" {{ ($qrMode ?? 'local') === 'public' ? 'selected' : '' }}>Public Cloudflare Tunnel</option>
                            </select>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label" style="font-size:0.78rem; font-weight:600; color:#E8D08D;">Hotspot Laptop IP (Optional Override)</label>
                            <input type="text" name="custom_lan_ip" class="form-control" value="{{ $customLanIp ?? '' }}" placeholder="Auto: {{ $detectedLanIp ?? '192.168.x.x' }}">
                        </div>
                    </div>
                </div>
                <div class="modal-footer d-flex justify-content-between" style="border-color:rgba(201,168,76,0.2);">
                    <button type="button" class="btn-ghost px-3 py-2" style="font-size:0.75rem;"
                            onclick="document.getElementById('wifiPassInput').value='mp' + Math.floor(1000 + Math.random()*9000);">
                        <i class="bi bi-arrow-repeat me-1"></i> Generate New Reset Key
                    </button>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn-ghost px-3 py-2" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn-gold px-4 py-2">Reset &amp; Update Wi-Fi QR</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
