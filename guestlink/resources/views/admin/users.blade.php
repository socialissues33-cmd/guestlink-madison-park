@extends('layouts.app')
@section('title', 'Users')
@section('page-title', 'User Management')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div class="d-flex align-items-center gap-2 flex-grow-1 flex-md-grow-0" style="min-width:min(280px, 100%);">
        <form method="GET" action="{{ route('admin.users') }}" class="m-0 d-flex align-items-center gap-2 w-100">
            <div style="position:relative; width:100%;">
                <i class="bi bi-search" style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:#8899AA; font-size:0.85rem;"></i>
                <input type="text"
                       id="usersSearchInput"
                       name="search"
                       class="form-control"
                       placeholder="Search Name, Email, Dept, Role..."
                       value="{{ request('search') }}"
                       onkeyup="liveFilterUsers(this.value)"
                       style="padding-left:34px; background:rgba(11,20,34,0.75); border:1px solid rgba(201,168,76,0.25); color:#fff; border-radius:20px; font-size:0.82rem;"
                       autocomplete="off">
            </div>
            @if(request('search'))
            <a href="{{ route('admin.users') }}" class="btn-ghost px-2 py-1 text-decoration-none" style="font-size:0.75rem; border-radius:15px;">
                ✕ Clear
            </a>
            @endif
        </form>
    </div>

    <button class="btn-gold px-4 py-2" data-bs-toggle="modal" data-bs-target="#addUserModal" style="border-radius:10px;">
        <i class="bi bi-person-plus me-1"></i> Add User
    </button>
</div>

<div class="table-card">
    <div class="table-responsive">
        <table class="data-table" id="usersTable">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Department</th>
                    <th>Status</th>
                    <th style="min-width:180px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                <tr class="user-row">
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div style="width:34px;height:34px;border-radius:50%;background:rgba(201,168,76,0.15);border:1px solid rgba(201,168,76,0.3);display:flex;align-items:center;justify-content:center;font-weight:700;color:#C9A84C;font-size:0.85rem;">
                                {{ strtoupper(substr($user->name, 0, 1)) }}
                            </div>
                            <div>
                                <span style="font-weight:600; font-size:0.85rem; color:#fff;">{{ $user->name }}</span>
                                @if($user->username)
                                <code class="ms-1" style="font-size:0.72rem; color:#E8D08D;">(&#64;{{ $user->username }})</code>
                                @endif
                                @if(auth()->id() === $user->id)
                                <span class="badge ms-1" style="background:rgba(201,168,76,0.2); color:#E8D08D; font-size:0.65rem;">You</span>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td style="font-size:0.82rem; color:#8899AA;">{{ $user->email }}</td>
                    <td>
                        @foreach($user->roles as $role)
                        <span style="display:inline-block; white-space:nowrap; background:rgba(201,168,76,0.15); border:1px solid rgba(201,168,76,0.3); border-radius:20px; padding:0.18rem 0.65rem; font-size:0.7rem; color:#E6CB7E; text-transform:capitalize;">
                            {{ str_replace('_', ' ', $role->name) }}
                        </span>
                        @endforeach
                    </td>
                    <td style="font-size:0.82rem; color:#8899AA;">{{ $user->department ?? '—' }}</td>
                    <td>
                        @if($user->is_active)
                        <span class="badge bg-success">Active</span>
                        @else
                        <span class="badge bg-secondary">Inactive</span>
                        @endif
                    </td>
                    <td>
                        <div class="d-flex align-items-center gap-1 flex-wrap">
                            {{-- Edit User Button --}}
                            <button type="button" class="btn-ghost px-2 py-1" style="font-size:0.72rem; color:#93c5fd; border-color:rgba(147,197,253,0.3);" title="Edit User Details" data-bs-toggle="modal" data-bs-target="#editUserModal{{ $user->id }}">
                                <i class="bi bi-pencil-square"></i> Edit
                            </button>

                            {{-- Activate / Deactivate Toggle (Cannot deactivate self) --}}
                            @if(auth()->id() !== $user->id)
                            <form method="POST" action="{{ route('admin.users.toggle', $user) }}" class="d-inline">
                                @csrf
                                <button type="submit" class="btn-ghost px-2 py-1" style="font-size:0.72rem;" title="{{ $user->is_active ? 'Deactivate User' : 'Activate User' }}">
                                    <i class="bi {{ $user->is_active ? 'bi-toggle-on text-success' : 'bi-toggle-off text-muted' }}"></i>
                                </button>
                            </form>

                            {{-- Delete User (Cannot delete self or only admin) --}}
                            <form method="POST" action="{{ route('admin.users.destroy', $user) }}" class="d-inline" onsubmit="return confirm('Permanently delete staff user \'{{ $user->name }}\'? This action cannot be reversed.')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-ghost px-2 py-1" style="font-size:0.72rem; color:#f87171; border-color:rgba(248,113,113,0.3);" title="Delete Staff User">
                                    <i class="bi bi-trash3"></i>
                                </button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr id="userEmptyRow"><td colspan="6" style="text-align:center; color:#8899AA; padding:3rem;">No users found.</td></tr>
                @endforelse
                <tr id="userNoMatchRow" style="display:none;"><td colspan="6" style="text-align:center; color:#8899AA; padding:2.5rem;">No matching staff accounts found.</td></tr>
            </tbody>
        </table>
    </div>
    @if($users->hasPages())
    <div class="p-3">{{ $users->links() }}</div>
    @endif
</div>

{{-- Edit User Modals (rendered outside <tbody> for valid HTML5 DOM) --}}
@foreach($users as $user)
<div class="modal fade" id="editUserModal{{ $user->id }}" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="background:#162236; border:1px solid rgba(201,168,76,0.3);">
            <div class="modal-header" style="border-color:rgba(201,168,76,0.2);">
                <h5 class="modal-title font-serif" style="color:#C9A84C;">Edit User: {{ $user->name }}</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('admin.users.update', $user) }}">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label" style="font-size:0.78rem; font-weight:600; color:#E8D08D;">Full Name</label>
                        <input type="text" name="name" class="form-control" value="{{ $user->name }}" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-sm-6">
                            <label class="form-label" style="font-size:0.78rem; font-weight:600; color:#E8D08D;">Username</label>
                            <input type="text" name="username" class="form-control" value="{{ $user->username }}" placeholder="e.g. admin">
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label" style="font-size:0.78rem; font-weight:600; color:#E8D08D;">Email Address (Optional)</label>
                            <input type="email" name="email" class="form-control" value="{{ $user->email }}" placeholder="Optional email...">
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-sm-6">
                            <label class="form-label" style="font-size:0.78rem; font-weight:600; color:#E8D08D;">System Role</label>
                            <select name="role" class="form-select" required>
                                @foreach($roles as $role)
                                <option value="{{ $role->name }}" {{ $user->hasRole($role->name) ? 'selected' : '' }}>
                                    {{ ucwords(str_replace('_',' ',$role->name)) }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label" style="font-size:0.78rem; font-weight:600; color:#E8D08D;">Department</label>
                            @php
                                $deptOptions = ['Front Desk', 'Maintenance', 'Housekeeping', 'Restaurant', 'Administration', 'Management'];
                                if ($user->department && !in_array($user->department, $deptOptions, true)) {
                                    $deptOptions[] = $user->department;
                                }
                            @endphp
                            <select name="department" class="form-select" required>
                                @foreach($deptOptions as $dOpt)
                                <option value="{{ $dOpt }}" {{ ($user->department ?? 'Front Desk') === $dOpt ? 'selected' : '' }}>{{ $dOpt }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-sm-6">
                            <label class="form-label" style="font-size:0.78rem; font-weight:600; color:#E8D08D;">New Password (Optional)</label>
                            <input type="password" name="password" class="form-control" placeholder="Leave blank to keep" minlength="6" autocomplete="new-password" readonly onfocus="this.removeAttribute('readonly')">
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label" style="font-size:0.78rem; font-weight:600; color:#E8D08D;">Confirm New Password</label>
                            <input type="password" name="password_confirmation" class="form-control" placeholder="Repeat new password" minlength="6" autocomplete="new-password" readonly onfocus="this.removeAttribute('readonly')">
                        </div>
                    </div>
                    <div class="form-check form-switch mt-2">
                        <input type="hidden" name="is_active" value="0">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="userActiveCheck{{ $user->id }}" {{ $user->is_active ? 'checked' : '' }}>
                        <label class="form-check-label" for="userActiveCheck{{ $user->id }}" style="font-size:0.8rem; color:#cbd5e1;">Active and Enabled Account</label>
                    </div>
                </div>
                <div class="modal-footer" style="border-color:rgba(201,168,76,0.2);">
                    <button type="button" class="btn-ghost px-3 py-2" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn-gold px-4 py-2">Update User</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endforeach

@push('scripts')
<script>
    function liveFilterUsers(keyword) {
        const term = (keyword || '').toLowerCase().trim();
        const table = document.getElementById('usersTable');
        if (!table) return;
        const rows = table.querySelectorAll('tbody tr.user-row');
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

        const emptyRow = document.getElementById('userNoMatchRow') || document.getElementById('userEmptyRow');
        if (emptyRow) {
            emptyRow.style.display = (visibleCount === 0 && rows.length > 0) ? '' : 'none';
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        const addModal = document.getElementById('addUserModal');
        const addForm = document.getElementById('addUserForm');
        if (addModal && addForm) {
            addModal.addEventListener('show.bs.modal', function() {
                addForm.reset();
                addForm.querySelectorAll('input[name="username"], input[name="email"], input[name="password"], input[name="password_confirmation"]').forEach(el => {
                    el.value = '';
                    el.setAttribute('readonly', 'readonly');
                });
            });
        }
    });
</script>
@endpush

{{-- Add User Modal --}}
<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="background:#162236; border:1px solid rgba(201,168,76,0.3);">
            <div class="modal-header" style="border-color:rgba(201,168,76,0.2);">
                <h5 class="modal-title font-serif" style="color:#C9A84C;">Add New User</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('admin.users.store') }}" id="addUserForm" autocomplete="off">
                @csrf
                {{-- Decoy fields absorb browser saved-password autofill --}}
                <input type="text" name="_fake_user" autocomplete="username" tabindex="-1" aria-hidden="true" style="position:absolute; left:-9999px; width:1px; height:1px; opacity:0;">
                <input type="password" name="_fake_pass" autocomplete="current-password" tabindex="-1" aria-hidden="true" style="position:absolute; left:-9999px; width:1px; height:1px; opacity:0;">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label" style="font-size:0.78rem; font-weight:600; color:#E8D08D;">Full Name</label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. Juan dela Cruz" autocomplete="off" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-sm-6">
                            <label class="form-label" style="font-size:0.78rem; font-weight:600; color:#E8D08D;">Username</label>
                            <input type="text" name="username" class="form-control" placeholder="e.g. juan_fd" autocomplete="off" readonly onfocus="this.removeAttribute('readonly')" required>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label" style="font-size:0.78rem; font-weight:600; color:#E8D08D;">Email Address (Optional)</label>
                            <input type="email" name="email" class="form-control" placeholder="Optional email..." autocomplete="off" readonly onfocus="this.removeAttribute('readonly')">
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-sm-6">
                            <label class="form-label" style="font-size:0.78rem; font-weight:600; color:#E8D08D;">Password</label>
                            <input type="password" name="password" class="form-control" placeholder="Min. 6 characters" autocomplete="new-password" readonly onfocus="this.removeAttribute('readonly')" required minlength="6">
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label" style="font-size:0.78rem; font-weight:600; color:#E8D08D;">Confirm Password</label>
                            <input type="password" name="password_confirmation" class="form-control" placeholder="Repeat password" autocomplete="new-password" readonly onfocus="this.removeAttribute('readonly')" required minlength="6">
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-sm-6">
                            <label class="form-label" style="font-size:0.78rem; font-weight:600; color:#E8D08D;">Role</label>
                            <select name="role" class="form-select" required>
                                @foreach($roles as $role)
                                <option value="{{ $role->name }}">{{ ucwords(str_replace('_',' ',$role->name)) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label" style="font-size:0.78rem; font-weight:600; color:#E8D08D;">Department</label>
                            <select name="department" class="form-select" required>
                                @foreach(['Front Desk', 'Maintenance', 'Housekeeping', 'Restaurant', 'Administration', 'Management'] as $dOpt)
                                <option value="{{ $dOpt }}">{{ $dOpt }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer" style="border-color:rgba(201,168,76,0.2);">
                    <button type="button" class="btn-ghost px-3 py-2" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn-gold px-4 py-2">Create User</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
