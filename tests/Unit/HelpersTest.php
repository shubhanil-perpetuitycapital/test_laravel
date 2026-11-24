<?php

namespace Tests\Unit;

use App\Services\HtmlSanitizer;
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

    public function test_html_sanitizer_keeps_formatting_and_strips_scripts(): void
    {
        $clean = (new HtmlSanitizer())->clean('<h2 style="color:red">Hi</h2><a href="javascript:alert(1)">x</a><img src=x onerror=alert(1)><script>bad()</script><details><summary>Q</summary><p>A</p></details>');

        $this->assertStringContainsString('<h2>Hi</h2>', $clean);
        $this->assertStringContainsString('<details><summary>Q</summary>', $clean);
        $this->assertStringNotContainsString('javascript', $clean);
        $this->assertStringNotContainsString('onerror', $clean);
        $this->assertStringNotContainsString('script', $clean);
        $this->assertStringNotContainsString('style', $clean);
    }
}
