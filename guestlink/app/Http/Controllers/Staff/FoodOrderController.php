<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\FoodCategory;
use App\Models\FoodItem;
use App\Models\FoodOrder;
use App\Models\FoodOrderStatusLog;
use Illuminate\Http\Request;

class FoodOrderController extends Controller
{
    private function isFoodRestrictedRole(): bool
    {
        $user = auth()->user();
        return $user
            && $user->roles()->exists()
            && $user->hasAnyRole(['maintenance', 'maintenance_supervisor', 'housekeeping'])
            && !$user->hasAnyRole(['admin', 'owner', 'front_desk', 'restaurant']);
    }

    public function index(Request $request)
    {
        if ($this->isFoodRestrictedRole()) {
            return redirect()->route('dashboard')
                ->with('error', 'Food Orders & Kitchen Queue are restricted to Restaurant, Front Desk, and Admin personnel.');
        }

        \App\Services\StatusProgressionService::runAutoProgression();

        $query = FoodOrder::with(['room', 'orderItems.foodItem', 'processedBy']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('reference_number', 'like', "%{$search}%")
                  ->orWhere('guest_name', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%")
                  ->orWhereHas('room', fn($r) => $r->where('room_number', 'like', "%{$search}%"))
                  ->orWhereHas('orderItems.foodItem', fn($f) => $f->where('name', 'like', "%{$search}%"));
            });
        }

        $orders = $query->latest()->paginate(15)->withQueryString();
        return view('staff.food.orders', compact('orders'));
    }

    public function show(FoodOrder $foodOrder)
    {
        if ($this->isFoodRestrictedRole()) {
            return redirect()->route('dashboard')
                ->with('error', 'Food Orders & Kitchen Queue are restricted to Restaurant, Front Desk, and Admin personnel.');
        }

        \App\Services\StatusProgressionService::runAutoProgression();
        $foodOrder->refresh();
        $foodOrder->load(['room', 'orderItems.foodItem', 'processedBy', 'statusLogs.changedBy']);
        return view('staff.food.order-detail', compact('foodOrder'));
    }

    protected function ensureCanManageFood(): void
    {
        $user = auth()->user();
        if ($user && $user->roles()->exists()) {
            if (
                $user->hasAnyRole(['maintenance', 'maintenance_supervisor', 'housekeeping']) &&
                !$user->hasAnyRole(['admin', 'owner', 'front_desk', 'restaurant'])
            ) {
                abort(403, 'Maintenance and Housekeeping personnel cannot modify food orders or dining menu items.');
            }
        }
    }

    public function updateStatus(Request $request, FoodOrder $foodOrder)
    {
        $this->ensureCanManageFood();

        $validated = $request->validate([
            'status' => 'required|in:pending,confirmed,preparing,ready,delivered,cancelled',
            'notes'  => 'nullable|string|max:500',
        ]);

        $oldStatus = $foodOrder->status;
        $newStatus = $validated['status'];

        // Strict Forward-Only & Terminal-Lock Policy (cannot move backwards or reopen delivered/cancelled orders)
        $check = \App\Services\StatusProgressionService::canTransitionFoodOrder($oldStatus, $newStatus);
        if (!$check['allowed']) {
            return back()->with('error', $check['error']);
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($foodOrder, $oldStatus, $newStatus, $validated) {
            $foodOrder->update([
                'status'       => $newStatus,
                'processed_by' => auth()->id(),
            ]);

            // Prevent duplicate logs like Pending -> Pending unless there is a meaningful note
            if ($oldStatus !== $newStatus) {
                FoodOrderStatusLog::create([
                    'food_order_id' => $foodOrder->id,
                    'from_status'   => $oldStatus,
                    'to_status'     => $newStatus,
                    'notes'         => $validated['notes'] ?? ($newStatus === 'delivered' ? 'Delivery confirmed & order completed (locked).' : null),
                    'changed_by'    => auth()->id(),
                ]);
            } elseif (!empty($validated['notes'])) {
                FoodOrderStatusLog::create([
                    'food_order_id' => $foodOrder->id,
                    'from_status'   => $oldStatus,
                    'to_status'     => $newStatus,
                    'notes'         => $validated['notes'],
                    'changed_by'    => auth()->id(),
                ]);
            }
        });

        $msg = $newStatus === 'delivered'
            ? 'Order marked as Delivered & Completed (permanently locked).'
            : 'Order status updated.';

        return back()->with('success', $msg);
    }

    // Menu Management
    public function menuIndex()
    {
        if ($this->isFoodRestrictedRole()) {
            return redirect()->route('dashboard')
                ->with('error', 'Times Square Menu management is restricted to Restaurant, Front Desk, and Admin personnel.');
        }

        $categories = FoodCategory::with('foodItems')->get();
        return view('staff.food.menu', compact('categories'));
    }

    private function safeUploadExtension(\Illuminate\Http\UploadedFile $file): string
    {
        return match ($file->getMimeType()) {
            'image/png'  => 'png',
            'image/webp' => 'webp',
            default      => 'jpg',
        };
    }

    public function menuStore(Request $request)
    {
        $this->ensureCanManageFood();

        $validated = $request->validate([
            'food_category_id' => 'required|exists:food_categories,id',
            'name'             => 'required|string|max:100',
            'description'      => 'nullable|string|max:300',
            'price'            => 'required|numeric|min:0',
            'is_available'     => 'boolean',
            'image'            => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
        ]);

        if ($request->hasFile('image')) {
            $dir = public_path('uploads/menu');
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            $file = $request->file('image');
            $filename = 'dish_' . time() . '_' . uniqid() . '.' . $this->safeUploadExtension($file);
            $file->move($dir, $filename);
            $validated['image'] = 'uploads/menu/' . $filename;
        } else {
            unset($validated['image']);
        }

        FoodItem::create($validated);
        return back()->with('success', 'Food item added.');
    }

    public function menuUpdate(Request $request, FoodItem $foodItem)
    {
        $this->ensureCanManageFood();

        $validated = $request->validate([
            'name'         => 'required|string|max:100',
            'description'  => 'nullable|string|max:300',
            'price'        => 'required|numeric|min:0',
            'is_available' => 'boolean',
            'image'        => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
        ]);

        if ($request->has('is_available')) {
            $validated['is_available'] = $request->boolean('is_available');
        }

        if ($request->hasFile('image')) {
            $dir = public_path('uploads/menu');
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            $oldImage = $foodItem->image;
            $file = $request->file('image');
            $filename = 'dish_' . time() . '_' . uniqid() . '.' . $this->safeUploadExtension($file);
            $file->move($dir, $filename);
            $validated['image'] = 'uploads/menu/' . $filename;

            if ($oldImage && str_starts_with($oldImage, 'uploads/menu/') && file_exists(public_path($oldImage))) {
                @unlink(public_path($oldImage));
            }
        } else {
            unset($validated['image']);
        }

        $foodItem->update($validated);
        return back()->with('success', 'Food item updated.');
    }

    public function menuToggle(FoodItem $foodItem)
    {
        $this->ensureCanManageFood();

        $foodItem->update(['is_available' => !$foodItem->is_available]);
        return back()->with('success', 'Food item availability updated.');
    }

    public function menuDestroy(FoodItem $foodItem)
    {
        $this->ensureCanManageFood();

        if ($foodItem->orderItems()->exists()) {
            return back()->with('error', "Cannot delete '{$foodItem->name}' because it is referenced by existing dining orders/folios. Toggle its availability off instead.");
        }

        if ($foodItem->image && str_starts_with($foodItem->image, 'uploads/menu/') && file_exists(public_path($foodItem->image))) {
            @unlink(public_path($foodItem->image));
        }

        $foodItem->delete();
        return back()->with('success', 'Food item removed from menu.');
    }
}
