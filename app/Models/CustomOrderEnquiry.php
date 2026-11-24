<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomOrderEnquiry extends Model
{
    use SoftDeletes;

    public const STATUSES = [
        'new' => 'New',
        'contacted' => 'Contacted',
        'quoted' => 'Quoted',
        'converted' => 'Converted to order',
        'closed' => 'Closed',
    ];

    public const STATUS_BADGES = [
        'new' => 'text-bg-warning',
        'contacted' => 'text-bg-info',
        'quoted' => 'text-bg-primary',
        'converted' => 'text-bg-success',
        'closed' => 'text-bg-secondary',
    ];

    public const OCCASIONS = ['Birthday', 'Anniversary', 'Wedding', 'Corporate gifting', 'Festival', 'Baby shower', 'Return gifts', 'Other'];
    public const CHOCOLATE_TYPES = ['Dark', 'Milk', 'White', 'Ruby', 'Assorted', 'Not sure yet'];

    protected $fillable = [
        'enquiry_number', 'customer_id', 'name', 'mobile', 'email', 'occasion',
        'chocolate_type', 'quantity', 'budget', 'required_date', 'delivery_pincode',
        'message', 'reference_image', 'status', 'quoted_amount', 'quotation_notes',
        'internal_notes', 'converted_order_id',
    ];

    protected function casts(): array
    {
        return ['required_date' => 'date', 'quoted_amount' => 'decimal:2'];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function convertedOrder(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'converted_order_id');
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst($this->status);
    }

    public function getStatusBadgeAttribute(): string
    {
        return self::STATUS_BADGES[$this->status] ?? 'text-bg-light';
    }
}
