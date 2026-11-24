<?php

namespace Tests\Feature;

use App\Models\CustomOrderEnquiry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CustomOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_custom_order_enquiry_is_saved_with_private_image(): void
    {
        $this->seedStore();
        Storage::fake('local');

        $this->get('/custom-orders')->assertOk()->assertSee('Send enquiry');

        $this->post('/custom-orders', [
            'name' => 'Arjun Das', 'mobile' => '9830098300', 'email' => 'arjun@example.com',
            'occasion' => 'Wedding', 'chocolate_type' => 'Assorted', 'quantity' => '150 boxes of 4',
            'budget' => '₹200 per box', 'required_date' => now()->addDays(20)->toDateString(),
            'delivery_pincode' => '700019', 'message' => 'Names A & P on each box',
            'reference_image' => UploadedFile::fake()->image('invite.png'),
        ])->assertRedirect('/custom-orders/thank-you');

        $enquiry = CustomOrderEnquiry::query()->firstOrFail();
        $this->assertSame('new', $enquiry->status);
        $this->assertStringStartsWith('ENQ', $enquiry->enquiry_number);
        $this->assertNotNull($enquiry->customer_id);
        Storage::disk('local')->assertExists($enquiry->reference_image);

        $this->get('/custom-orders/thank-you')->assertOk()->assertSee($enquiry->enquiry_number);
    }

    public function test_validation_and_honeypot(): void
    {
        $this->seedStore();

        $this->post('/custom-orders', ['name' => '', 'mobile' => '123', 'quantity' => '', 'required_date' => '2001-01-01'])
            ->assertSessionHasErrors(['name', 'mobile', 'quantity', 'required_date']);

        $this->post('/custom-orders', ['name' => 'Bot', 'mobile' => '9830098300', 'quantity' => '10', 'website' => 'spam'])
            ->assertSessionHasErrors('website');

        $this->assertSame(0, CustomOrderEnquiry::query()->count());
    }
}
