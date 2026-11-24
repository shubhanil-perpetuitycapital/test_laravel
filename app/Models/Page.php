<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    /** Linked from the footer and checkout; can be edited but not deleted. */
    public const SYSTEM_SLUGS = [
        'about-us', 'faq', 'privacy-policy', 'terms-and-conditions',
        'shipping-delivery-policy', 'cancellation-refund-policy',
    ];

    protected $fillable = ['title', 'slug', 'content', 'meta_title', 'meta_description', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function isSystem(): bool
    {
        return in_array($this->slug, self::SYSTEM_SLUGS, true);
    }
}
