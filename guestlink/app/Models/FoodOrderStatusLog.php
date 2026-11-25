<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FoodOrderStatusLog extends Model
{
    protected $fillable = [
        'food_order_id', 'from_status', 'to_status', 'notes', 'changed_by',
    ];

    protected $touches = ['foodOrder'];

    public function foodOrder(): BelongsTo
    {
        return $this->belongsTo(FoodOrder::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
