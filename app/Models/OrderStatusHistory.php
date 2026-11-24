<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderStatusHistory extends Model
{
    protected $fillable = ['order_id', 'from_status', 'to_status', 'comment', 'visible_to_customer', 'user_id'];

    protected function casts(): array
    {
        return ['visible_to_customer' => 'boolean'];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getToStatusLabelAttribute(): string
    {
        return OrderStatus::tryFrom($this->to_status)?->label() ?? $this->to_status;
    }
}
