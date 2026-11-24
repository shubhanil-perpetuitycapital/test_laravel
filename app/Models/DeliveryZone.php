<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeliveryZone extends Model
{
    protected $fillable = [
        'name', 'description', 'delivery_charge', 'free_delivery_threshold',
        'same_day_available', 'same_day_cutoff', 'lead_days', 'estimated_text', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'delivery_charge' => 'decimal:2',
            'free_delivery_threshold' => 'decimal:2',
            'same_day_available' => 'boolean',
            'lead_days' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function pincodes(): HasMany
    {
        return $this->hasMany(DeliveryPincode::class)->orderBy('pincode');
    }
}
