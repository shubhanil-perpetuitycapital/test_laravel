<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\URL;

class Order extends Model
{
    use SoftDeletes;

    public const PAYMENT_METHODS = ['upi' => 'UPI', 'cod' => 'Cash on delivery'];
    public const DELIVERY_METHODS = ['local' => 'Kolkata local delivery', 'outstation' => 'Courier (outside Kolkata)'];

    protected $fillable = [
        'order_number', 'customer_id', 'source', 'status', 'payment_method', 'payment_status',
        'payment_reference', 'payment_screenshot', 'payment_submitted_at', 'delivery_method', 'delivery_zone_id',
        'recipient_name', 'recipient_mobile', 'address_line', 'city', 'state', 'pincode',
        'delivery_instructions', 'order_notes', 'expected_delivery_date', 'delivery_estimate_text',
        'subtotal', 'coupon_id', 'coupon_code', 'discount_amount', 'delivery_charge',
        'delivery_charge_pending', 'total', 'courier_name', 'tracking_number', 'tracking_url', 'internal_notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'payment_status' => PaymentStatus::class,
            'payment_submitted_at' => 'datetime',
            'expected_delivery_date' => 'date',
            'subtotal' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'delivery_charge' => 'decimal:2',
            'delivery_charge_pending' => 'boolean',
            'total' => 'decimal:2',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class)->withTrashed();
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class)->latest('id');
    }

    public function deliveryZone(): BelongsTo
    {
        return $this->belongsTo(DeliveryZone::class);
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class)->withTrashed();
    }

    /** Orders that count towards revenue. */
    public function scopeRevenue(Builder $query): Builder
    {
        return $query->where('status', '!=', OrderStatus::Cancelled->value);
    }

    public function getPaymentMethodLabelAttribute(): string
    {
        return self::PAYMENT_METHODS[$this->payment_method] ?? ucfirst((string) $this->payment_method);
    }

    public function getDeliveryMethodLabelAttribute(): string
    {
        return self::DELIVERY_METHODS[$this->delivery_method] ?? ucfirst((string) $this->delivery_method);
    }

    public function getFullAddressAttribute(): string
    {
        return $this->address_line.', '.$this->city.', '.$this->state.' – '.$this->pincode;
    }

    public function canSubmitPayment(): bool
    {
        return $this->payment_method === 'upi'
            && in_array($this->payment_status, [PaymentStatus::Unpaid, PaymentStatus::Submitted], true)
            && $this->status !== OrderStatus::Cancelled;
    }

    /**
     * Private link to the customer's confirmation page. Signed so order
     * details can't be read by guessing order numbers.
     */
    public function confirmationUrl(): string
    {
        return URL::signedRoute('orders.confirmation', ['order' => $this->order_number]);
    }

    public function trackingPageUrl(): string
    {
        return route('track.show', ['order' => $this->order_number]);
    }
}
