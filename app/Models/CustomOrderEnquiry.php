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

    protected $fillable = [
        'enquiry_number', 'customer_id', 'name', 'mobile', 'email', 'occasion',
        'chocolate_type', 'quantity', 'budget', 'required_date', 'delivery_pincode',
        'message', 'reference_image', 'status', 'quoted_amount', 'quotation_notes',
        'internal_notes', 'converted_order_id',
    ];

    protected function casts(): array
    {
        return [
            'required_date' => 'date',
            'quoted_amount' => 'decimal:2',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function convertedOrder(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'converted_order_id');
    }
}
