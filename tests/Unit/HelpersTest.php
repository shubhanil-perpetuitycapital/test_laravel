<?php

namespace Tests\Unit;

use App\Services\WhatsAppService;
use PHPUnit\Framework\TestCase;

class HelpersTest extends TestCase
{
    public function test_money_uses_indian_digit_grouping(): void
    {
        $this->assertSame('₹0', money(0));
        $this->assertSame('₹999', money(999));
        $this->assertSame('₹1,250', money(1250));
        $this->assertSame('₹1,25,000', money(125000));
        $this->assertSame('₹12,34,56,789', money(123456789));
        $this->assertSame('₹1,250.50', money(1250.5, true));
        $this->assertSame('₹300', money(299.5));
    }

    public function test_whatsapp_numbers_are_normalised(): void
    {
        $this->assertSame('919830012345', WhatsAppService::normalizeNumber('98300 12345'));
        $this->assertSame('919830012345', WhatsAppService::normalizeNumber('+91 98300-12345'));
        $this->assertSame('919830012345', WhatsAppService::normalizeNumber('09830012345'));
        $this->assertSame('447700900123', WhatsAppService::normalizeNumber('+44 7700 900123'));
    }
}
