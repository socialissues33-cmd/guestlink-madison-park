@extends('layouts.app')
@section('title', 'Menu Management')
@section('page-title', 'Manage Food Menu')

@section('content')
<div class="d-flex justify-content-end mb-3">
    <button class="btn-gold px-4 py-2" data-bs-toggle="modal" data-bs-target="#addItemModal" style="border-radius:10px;">
        <i class="bi bi-plus-circle me-1"></i> Add Food Item
    </button>
</div>

@foreach($categories as $cat)
@if($cat->foodItems->count() > 0)
<div class="table-card mb-3">
    <div class="p-3 border-bottom d-flex align-items-center justify-content-between" style="border-color:rgba(201,168,76,0.2) !important;">
        <div>
            <span style="font-family:'Playfair Display',serif; font-size:1.05rem; font-weight:700; color:#C9A84C;">{{ $cat->name }}</span>
            <span style="color:#8899AA; font-size:0.75rem; margin-left:0.75rem;">{{ $cat->foodItems->count() }} dishes</span>
        </div>
    </div>
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Dish / Item</th>
                    <th>Description</th>
                    <th>Price</th>
                    <th>Availability</th>
                    <th style="min-width:160px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($cat->foodItems as $item)
                <tr>
                    <td style="font-weight:600; font-size:0.85rem; color:#fff;">
                        <div class="d-flex align-items-center gap-2">
                            @if($item->image)
                                <img src="{{ asset($item->image) }}" alt="{{ $item->name }}" style="width:40px; height:40px; object-fit:cover; border-radius:8px; border:1px solid rgba(201,168,76,0.3); flex-shrink:0;">
                            @endif
                            <span>{{ $item->name }}</span>
                        </div>
                    </td>
                    <td style="font-size:0.78rem; color:#8899AA; max-width:260px;">{{ $item->description ?: '—' }}</td>
                    <td style="color:#C9A84C; font-weight:700; font-size:0.9rem;">₱{{ number_format($item->price, 2) }}</td>
                    <td>
                        @if($item->is_available)
                        <span class="badge bg-success">Available</span>
                        @else
                        <span class="badge bg-secondary">Unavailable</span>
                        @endif
                    </td>
                    <td>
                        <div class="d-flex align-items-center gap-1 flex-wrap">
                            {{-- Edit Item Modal Trigger --}}
                            <button type="button" class="btn-ghost px-2 py-1" style="font-size:0.72rem; color:#93c5fd; border-color:rgba(147,197,253,0.3);" title="Edit Food Item" data-bs-toggle="modal" data-bs-target="#editItemModal{{ $item->id }}">
                                <i class="bi bi-pencil-square"></i> Edit
                            </button>

                            {{-- Toggle Availability --}}
                            <form method="POST" action="{{ route('food.menu.toggle', $item) }}" class="d-inline">
                                @csrf
                                <button type="submit" class="btn-ghost px-2 py-1" style="font-size:0.72rem;" title="{{ $item->is_available ? 'Set Out of Stock' : 'Make Available' }}">
                                    <i class="bi {{ $item->is_available ? 'bi-toggle-on text-success' : 'bi-toggle-off text-muted' }}"></i>
                                </button>
                            </form>

                            {{-- Delete Item --}}
                            <form method="POST" action="{{ route('food.menu.destroy', $item) }}" class="d-inline" onsubmit="return confirm('Permanently remove \'{{ $item->name }}\' from the restaurant menu?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-ghost px-2 py-1" style="font-size:0.72rem; color:#f87171; border-color:rgba(248,113,113,0.3);" title="Delete Food Item">
                                    <i class="bi bi-trash3"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

{{-- Edit Food Item Modals for {{ $cat->name }} (rendered outside <tbody> for valid HTML5 DOM) --}}
@foreach($cat->foodItems as $item)
<div class="modal fade" id="editItemModal{{ $item->id }}" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="background:#162236; border:1px solid rgba(201,168,76,0.3);">
            <div class="modal-header" style="border-color:rgba(201,168,76,0.2);">
                <h5 class="modal-title font-serif" style="color:#C9A84C;">Edit Item: {{ $item->name }}</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('food.menu.update', $item) }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label" style="font-size:0.78rem; font-weight:600; color:#E8D08D;">Item Name</label>
                        <input type="text" name="name" class="form-control" value="{{ $item->name }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" style="font-size:0.78rem; font-weight:600; color:#E8D08D;">Description</label>
                        <textarea name="description" class="form-control" rows="2">{{ $item->description }}</textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" style="font-size:0.78rem; font-weight:600; color:#E8D08D;">Price (₱)</label>
                        <input type="number" name="price" class="form-control" step="0.01" min="0" value="{{ $item->price }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" style="font-size:0.78rem; font-weight:600; color:#E8D08D;">Dish Photo (Optional)</label>
                        <input type="file" name="image" class="form-control" accept="image/jpeg,image/png,image/webp">
                    </div>
                    <div class="form-check form-switch mt-2">
                        <input type="hidden" name="is_available" value="0">
                        <input class="form-check-input" type="checkbox" name="is_available" value="1" id="availCheck{{ $item->id }}" {{ $item->is_available ? 'checked' : '' }}>
                        <label class="form-check-label" for="availCheck{{ $item->id }}" style="font-size:0.8rem; color:#cbd5e1;">Available for In-Room Ordering</label>
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
@endif
@endforeach

{{-- Add Item Modal --}}
<div class="modal fade" id="addItemModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="background:#162236; border:1px solid rgba(201,168,76,0.3);">
            <div class="modal-header" style="border-color:rgba(201,168,76,0.2);">
                <h5 class="modal-title font-serif" style="color:#C9A84C;">Add Food Item</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('food.menu.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label" style="font-size:0.78rem; font-weight:600; color:#E8D08D;">Category</label>
                        <select name="food_category_id" class="form-select" required>
                            @foreach($categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" style="font-size:0.78rem; font-weight:600; color:#E8D08D;">Item Name</label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. Grilled Angus Ribeye Steak" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" style="font-size:0.78rem; font-weight:600; color:#E8D08D;">Description</label>
                        <textarea name="description" class="form-control" rows="2" placeholder="Short description, sides, or preparation details..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" style="font-size:0.78rem; font-weight:600; color:#E8D08D;">Price (₱)</label>
                        <input type="number" name="price" class="form-control" step="0.01" min="0" placeholder="0.00" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" style="font-size:0.78rem; font-weight:600; color:#E8D08D;">Dish Photo (Optional)</label>
                        <input type="file" name="image" class="form-control" accept="image/jpeg,image/png,image/webp">
                    </div>
                </div>
                <div class="modal-footer" style="border-color:rgba(201,168,76,0.2);">
                    <button type="button" class="btn-ghost px-3 py-2" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn-gold px-4 py-2">Add Dish</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
