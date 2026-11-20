<?php

namespace App\Services;

use App\Models\Product;

/**
 * Builds WhatsApp click-to-chat links (https://wa.me/<number>?text=...).
 *
 * This opens WhatsApp with a pre-filled message that the customer sends
 * themselves; nothing is sent automatically. Automatic delivery would need
 * the official WhatsApp Business (Cloud) API, which is not configured.
 */
class WhatsAppService
{
    public function __construct(
        private SettingService $settings,
        private CompanyProfile $company,
    ) {}

    /** Digits only, Indian numbers normalised to 91XXXXXXXXXX. */
    public static function normalizeNumber(?string $number): string
    {
        $digits = preg_replace('/\D+/', '', (string) $number) ?? '';

        if (strlen($digits) === 11 && str_starts_with($digits, '0')) {
            $digits = substr($digits, 1);
        }

        if (strlen($digits) === 10) {
            $digits = '91'.$digits;
        }

        return $digits;
    }

    public function businessNumber(): string
    {
        return self::normalizeNumber($this->settings->get('whatsapp_number'));
    }

    public function isConfigured(): bool
    {
        return strlen($this->businessNumber()) >= 11;
    }

    public function link(?string $message = null, ?string $to = null): string
    {
        $number = $to !== null ? self::normalizeNumber($to) : $this->businessNumber();
        $url = 'https://wa.me/'.$number;

        if (filled($message)) {
            $url .= '?text='.rawurlencode($message);
        }

        return $url;
    }

    public function generalLink(): string
    {
        return $this->link($this->settings->get('whatsapp_default_message'));
    }

    public function productEnquiryLink(Product $product, int $quantity = 1): string
    {
        return $this->link($this->productEnquiryMessage($product, (string) $quantity));
    }

    /**
     * Pass "{qty}" as the quantity to get a template the product page's
     * quantity selector fills in before opening WhatsApp.
     */
    public function productEnquiryMessage(Product $product, string $quantity = '1'): string
    {
        return implode("\n", [
            'Hi '.$this->company->name().'! I would like to order:',
            '',
            '• '.$product->name.($product->weight ? ' ('.$product->weight.')' : ''),
            '  Quantity: '.$quantity,
            '  Price: '.money($product->effective_price).' each',
            '',
            $product->url,
        ]);
    }

    public function customOrderLink(): string
    {
        return $this->link('Hi '.$this->company->name().'! I would like to ask about a customised chocolate order.');
    }
}
