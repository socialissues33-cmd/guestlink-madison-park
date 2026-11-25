@extends('layouts.app')
@section('title', 'Categories')
@section('page-title', 'Request Categories')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div class="d-flex align-items-center gap-2 flex-grow-1 flex-md-grow-0" style="min-width:min(280px, 100%);">
        <form method="GET" action="{{ route('admin.categories') }}" class="m-0 d-flex align-items-center gap-2 w-100">
            <div style="position:relative; width:100%;">
                <i class="bi bi-search" style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:#8899AA; font-size:0.85rem;"></i>
                <input type="text"
                       id="categoriesSearchInput"
                       name="search"
                       class="form-control"
                       placeholder="Search Categories..."
                       value="{{ request('search') }}"
                       onkeyup="liveFilterCategories(this.value)"
                       style="padding-left:34px; background:rgba(11,20,34,0.75); border:1px solid rgba(201,168,76,0.25); color:#fff; border-radius:20px; font-size:0.82rem;"
                       autocomplete="off">
            </div>
            @if(request('search'))
            <a href="{{ route('admin.categories') }}" class="btn-ghost px-2 py-1 text-decoration-none" style="font-size:0.75rem; border-radius:15px;">
                ✕ Clear
            </a>
            @endif
        </form>
    </div>

    <button class="btn-gold px-4 py-2" data-bs-toggle="modal" data-bs-target="#addCatModal" style="border-radius:10px;">
        <i class="bi bi-plus-circle me-1"></i> Add Category
    </button>
</div>

<div class="table-card">
    <div class="table-responsive">
        <table class="data-table" id="categoriesTable">
            <thead>
                <tr>
                    <th style="width:60px;">Icon</th>
                    <th>Category Name</th>
                    <th>Workflow Type</th>
                    <th>Status</th>
                    <th style="min-width:160px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($categories as $cat)
                <tr class="category-row">
                    <td style="font-size:1.15rem; text-align:center; color:#E8D08D;">
                        @if($cat->icon && str_starts_with(trim($cat->icon), 'bi-'))
                            <i class="bi {{ trim($cat->icon) }}"></i>
                        @elseif($cat->icon)
                            {{ $cat->icon }}
                        @else
                            <i class="bi bi-tag"></i>
                        @endif
                    </td>
                    <td style="font-weight:600; font-size:0.85rem; color:#fff;">{{ $cat->name }}</td>
                    <td>
                        @if($cat->type === 'maintenance')
                        <span class="badge" style="background:rgba(245,158,11,0.18); color:#fcd34d; border:1px solid rgba(245,158,11,0.35); font-weight:600; padding:0.35rem 0.65rem;">
                            <i class="bi bi-tools me-1"></i> Maintenance
                        </span>
                        @else
                        <span class="badge" style="background:rgba(56,189,248,0.18); color:#7dd3fc; border:1px solid rgba(56,189,248,0.35); font-weight:600; padding:0.35rem 0.65rem;">
                            <i class="bi bi-bell me-1"></i> Service
                        </span>
                        @endif
                    </td>
                    <td>
                        @if($cat->is_active)
                        <span class="badge" style="background:rgba(16,185,129,0.18); color:#6ee7b7; border:1px solid rgba(16,185,129,0.35); font-weight:600; padding:0.35rem 0.65rem;">
                            <i class="bi bi-check-circle me-1"></i> Active
                        </span>
                        @else
                        <span class="badge" style="background:rgba(148,163,184,0.18); color:#94a3b8; border:1px solid rgba(148,163,184,0.35); font-weight:600; padding:0.35rem 0.65rem;">
                            <i class="bi bi-dash-circle me-1"></i> Inactive
                        </span>
                        @endif
                    </td>
                    <td>
                        <div class="d-flex align-items-center gap-1 flex-wrap">
                            {{-- Edit Category Modal Trigger --}}
                            <button type="button" class="btn-ghost px-2 py-1" style="font-size:0.72rem; color:#93c5fd; border-color:rgba(147,197,253,0.3);" title="Edit Category" data-bs-toggle="modal" data-bs-target="#editCatModal{{ $cat->id }}">
                                <i class="bi bi-pencil-square"></i> Edit
                            </button>

                            {{-- Toggle Status --}}
                            <form method="POST" action="{{ route('admin.categories.toggle', $cat) }}" class="d-inline">
                                @csrf
                                <button type="submit" class="btn-ghost px-2 py-1" style="font-size:0.72rem;" title="{{ $cat->is_active ? 'Deactivate Category' : 'Activate Category' }}">
                                    <i class="bi {{ $cat->is_active ? 'bi-toggle-on text-success' : 'bi-toggle-off text-muted' }}"></i>
                                </button>
                            </form>

                            {{-- Delete Category --}}
                            <form method="POST" action="{{ route('admin.categories.destroy', $cat) }}" class="d-inline" onsubmit="return confirm('Permanently delete category \'{{ $cat->name }}\'?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-ghost px-2 py-1" style="font-size:0.72rem; color:#f87171; border-color:rgba(248,113,113,0.3);" title="Delete Category">
                                    <i class="bi bi-trash3"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr id="catEmptyRow"><td colspan="5" style="text-align:center; color:#8899AA; padding:3rem;">No categories found.</td></tr>
                @endforelse
                <tr id="catNoMatchRow" style="display:none;"><td colspan="5" style="text-align:center; color:#8899AA; padding:2.5rem;">No matching categories found.</td></tr>
            </tbody>
        </table>
    </div>
    @if($categories->hasPages())<div class="p-3">{{ $categories->links() }}</div>@endif
</div>

{{-- Edit Category Modals (rendered outside <tbody> for valid HTML5 DOM) --}}
@foreach($categories as $cat)
<div class="modal fade" id="editCatModal{{ $cat->id }}" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="background:#162236; border:1px solid rgba(201,168,76,0.3);">
            <div class="modal-header" style="border-color:rgba(201,168,76,0.2);">
                <h5 class="modal-title font-serif" style="color:#C9A84C;">Edit Category: {{ $cat->name }}</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('admin.categories.update', $cat) }}">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label" style="font-size:0.78rem; font-weight:600; color:#E8D08D;">Category Name</label>
                        <input type="text" name="name" class="form-control" value="{{ $cat->name }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" style="font-size:0.78rem; font-weight:600; color:#E8D08D;">Workflow Type</label>
                        <select name="type" class="form-select" required>
                            <option value="service" {{ $cat->type === 'service' ? 'selected' : '' }}>Service (Front Desk / Concierge)</option>
                            <option value="maintenance" {{ $cat->type === 'maintenance' ? 'selected' : '' }}>Maintenance (Engineering / Repairs)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" style="font-size:0.78rem; font-weight:600; color:#E8D08D;">Icon (Emoji / Symbol)</label>
                        <input type="text" name="icon" class="form-control" value="{{ $cat->icon }}" placeholder="e.g. bi-key or bi-bell" maxlength="32">
                    </div>
                    <div class="form-check form-switch mt-2">
                        <input type="hidden" name="is_active" value="0">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="catActiveCheck{{ $cat->id }}" {{ $cat->is_active ? 'checked' : '' }}>
                        <label class="form-check-label" for="catActiveCheck{{ $cat->id }}" style="font-size:0.8rem; color:#cbd5e1;">Active and Visible to Guests</label>
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
    function liveFilterCategories(keyword) {
        const term = (keyword || '').toLowerCase().trim();
        const table = document.getElementById('categoriesTable');
        if (!table) return;
        const rows = table.querySelectorAll('tbody tr.category-row');
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

        const emptyRow = document.getElementById('catNoMatchRow') || document.getElementById('catEmptyRow');
        if (emptyRow) {
            emptyRow.style.display = (visibleCount === 0 && rows.length > 0) ? '' : 'none';
        }
    }
</script>
@endpush

{{-- Add Category Modal --}}
<div class="modal fade" id="addCatModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="background:#162236; border:1px solid rgba(201,168,76,0.3);">
            <div class="modal-header" style="border-color:rgba(201,168,76,0.2);">
                <h5 class="modal-title font-serif" style="color:#C9A84C;">Add New Category</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('admin.categories.store') }}">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label" style="font-size:0.78rem; font-weight:600; color:#E8D08D;">Category Name</label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. Bath Amenities" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" style="font-size:0.78rem; font-weight:600; color:#E8D08D;">Workflow Type</label>
                        <select name="type" class="form-select" required>
                            <option value="service">Service (Front Desk / Concierge)</option>
                            <option value="maintenance">Maintenance (Engineering / Repairs)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" style="font-size:0.78rem; font-weight:600; color:#E8D08D;">Icon (Emoji / Symbol)</label>
                        <input type="text" name="icon" class="form-control" placeholder="e.g. bi-key or bi-bell" maxlength="32">
                    </div>
                </div>
                <div class="modal-footer" style="border-color:rgba(201,168,76,0.2);">
                    <button type="button" class="btn-ghost px-3 py-2" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn-gold px-4 py-2">Create Category</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
