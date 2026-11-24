<?php

namespace App\Services;

use App\Models\CustomOrderEnquiry;
use App\Models\Order;
use App\Models\Product;

/**
 * Builds WhatsApp click-to-chat links (https://wa.me/<number>?text=...).
 *
 * These open WhatsApp with a pre-filled message that a person then sends.
 * Nothing is delivered automatically: that would need the official
 * WhatsApp Business (Cloud) API, which this project does not use.
 */
class WhatsAppService
{
    public function __construct(
        private SettingService $settings,
        private CompanyProfile $company,
    ) {}

    /** Digits only; 10-digit Indian numbers get the 91 country code. */
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

        return filled($message) ? $url.'?text='.rawurlencode($message) : $url;
    }

    public function generalLink(): string
    {
        return $this->link($this->settings->get('whatsapp_default_message'));
    }

    public function productEnquiryLink(Product $product, int $quantity = 1): string
    {
        return $this->link($this->productEnquiryMessage($product, (string) $quantity));
    }

    /** Pass "{qty}" to get a template the quantity selector fills in. */
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

    /** Customer → business: send the order details after checkout. */
    public function orderToBusinessLink(Order $order): string
    {
        return $this->link($this->render('whatsapp_order_template', $order));
    }

    /** Business → customer: order confirmation. */
    public function confirmationToCustomerLink(Order $order): string
    {
        return $this->link($this->render('whatsapp_confirmation_template', $order), $order->recipient_mobile);
    }

    /** Business → customer: current status. */
    public function statusToCustomerLink(Order $order): string
    {
        return $this->link($this->render('whatsapp_status_template', $order), $order->recipient_mobile);
    }

    /** Business → enquiry customer, including the quote if there is one. */
    public function enquiryReplyLink(CustomOrderEnquiry $enquiry): string
    {
        $lines = ['Hi '.$enquiry->name.', thank you for your custom chocolate enquiry ('.$enquiry->enquiry_number.') with '.$this->company->name().'.'];

        if ($enquiry->quoted_amount) {
            $lines[] = '';
            $lines[] = 'Our quote: '.money($enquiry->quoted_amount, true);
            if ($enquiry->quotation_notes) {
                $lines[] = $enquiry->quotation_notes;
            }
        }

        return $this->link(implode("\n", $lines), $enquiry->mobile);
    }

    /** Customer → business after submitting the custom order form. */
    public function enquiryToBusinessLink(CustomOrderEnquiry $enquiry): string
    {
        $lines = array_filter([
            'Hi '.$this->company->name().'! I sent a custom order enquiry ('.$enquiry->enquiry_number.').',
            $enquiry->occasion ? 'Occasion: '.$enquiry->occasion : null,
            'Quantity: '.$enquiry->quantity,
            $enquiry->required_date ? 'Needed by: '.$enquiry->required_date->format('j M Y') : null,
        ]);

        return $this->link(implode("\n", $lines));
    }

    /** Fill a message template with order details. */
    public function render(string $templateKey, Order $order): string
    {
        $order->loadMissing('items');

        $items = $order->items->map(fn ($item) => '• '.$item->product_name.' × '.$item->quantity.' — '.money($item->line_total))->implode("\n");

        $delivery = $order->delivery_method_label;
        if ($order->delivery_estimate_text) {
            $delivery .= ' ('.$order->delivery_estimate_text.')';
        }

        $courier = $order->courier_name || $order->tracking_number
            ? trim('Courier: '.($order->courier_name ?: '—').($order->tracking_number ? ', tracking no. '.$order->tracking_number : ''))
            : '';

        $total = money($order->total, true).($order->delivery_charge_pending ? ' + delivery (to be confirmed)' : '');

        $message = strtr((string) $this->settings->get($templateKey), [
            '{brand}' => $this->company->name(),
            '{name}' => $order->recipient_name,
            '{order_number}' => $order->order_number,
            '{items}' => $items,
            '{total}' => $total,
            '{payment_method}' => $order->payment_method_label,
            '{delivery}' => $delivery,
            '{address}' => $order->full_address,
            '{status}' => $order->status->label(),
            '{courier}' => $courier,
            '{tracking_link}' => $order->trackingPageUrl(),
        ]);

        // Collapse blank lines left by empty placeholders.
        return trim(preg_replace("/\n{3,}/", "\n\n", $message) ?? $message);
    }
}
