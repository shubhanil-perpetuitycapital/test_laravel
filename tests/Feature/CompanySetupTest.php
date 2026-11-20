<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use App\Support\SettingsSchema;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CompanySetupTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SettingSeeder::class);
        $this->owner = User::query()->create([
            'name' => 'Owner', 'email' => 'owner@example.com', 'password' => 'secret-pass-123',
            'role' => User::ROLE_ADMIN, 'is_active' => true,
        ]);
    }

    /** A complete, valid submission based on the schema defaults. */
    private function payload(array $overrides = []): array
    {
        $data = [];
        foreach (SettingsSchema::companyFields() as $key => $field) {
            if ($field['type'] !== 'image') {
                $data[$key] = $field['default'];
            }
        }

        return array_merge($data, $overrides);
    }

    public function test_company_setup_page_shows_every_section(): void
    {
        $response = $this->actingAs($this->owner)->get('/admin/company')->assertOk();

        foreach (['Brand', 'Contact', 'Address', 'Legal &amp; tax', 'Social links', 'SEO'] as $tab) {
            $response->assertSee($tab, false);
        }
    }

    public function test_saved_details_appear_on_the_website(): void
    {
        $this->actingAs($this->owner)->put('/admin/company', $this->payload([
            'company_name' => 'Cocoa Test Kitchen',
            'tagline' => 'Tested by hand',
            'phone' => '+91 98300 11111',
            'whatsapp_number' => '98300 22222',
            'address_line1' => '12 Lake Road',
            'gstin' => '19abcde1234f1z5',
            'fssai_number' => '1234 5678 9012 34',
            'instagram_url' => 'https://instagram.com/cocoatest',
            'facebook_url' => '',
        ]))->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame('Cocoa Test Kitchen', Setting::query()->where('key', 'company_name')->value('value'));
        $this->assertSame('919830022222', Setting::query()->where('key', 'whatsapp_number')->value('value'), 'WhatsApp number is normalised with country code');
        $this->assertSame('19ABCDE1234F1Z5', Setting::query()->where('key', 'gstin')->value('value'));

        $this->get('/')
            ->assertOk()
            ->assertSee('Cocoa Test Kitchen')
            ->assertSee('Tested by hand')
            ->assertSee('https://wa.me/919830022222', false)
            ->assertSee('12 Lake Road')
            ->assertSee('FSSAI Lic. No. 12345678901234')
            ->assertSee('GSTIN 19ABCDE1234F1Z5')
            ->assertSee('https://instagram.com/cocoatest', false)
            ->assertDontSee('bi-facebook', false);
    }

    public function test_hiding_the_address_shows_only_city(): void
    {
        $this->actingAs($this->owner)->put('/admin/company', $this->payload([
            'address_line1' => '7 Secret Lane',
            'show_address_publicly' => '0',
        ]))->assertSessionHasNoErrors();

        $this->get('/contact')->assertOk()->assertDontSee('7 Secret Lane')->assertSee('Kolkata, West Bengal');
    }

    public function test_invalid_values_are_rejected_with_messages(): void
    {
        $this->actingAs($this->owner)->from('/admin/company')->put('/admin/company', $this->payload([
            'company_name' => '',
            'gstin' => 'NOTAGSTIN',
            'fssai_number' => '123',
            'instagram_url' => 'not a url',
            'pincode' => '7000',
        ]))->assertRedirect('/admin/company')
            ->assertSessionHasErrors(['company_name', 'gstin', 'fssai_number', 'instagram_url', 'pincode']);

        $this->assertSame(config('store.brand_name'), Setting::query()->where('key', 'company_name')->value('value'));
    }

    public function test_logo_upload_and_removal(): void
    {
        Storage::fake('uploads');

        $this->actingAs($this->owner)->put('/admin/company', $this->payload([
            'logo' => UploadedFile::fake()->image('logo.png', 400, 120),
        ]))->assertSessionHasNoErrors();

        $path = Setting::query()->where('key', 'logo')->value('value');
        $this->assertNotNull($path);
        Storage::disk('uploads')->assertExists($path);
        $this->get('/')->assertSee('uploads/'.$path, false);

        $this->actingAs($this->owner)->put('/admin/company', $this->payload(['remove_logo' => '1']))
            ->assertSessionHasNoErrors();

        Storage::disk('uploads')->assertMissing($path);
        $this->assertNull(Setting::query()->where('key', 'logo')->value('value'));
    }

    public function test_non_image_logo_is_rejected(): void
    {
        Storage::fake('uploads');

        $this->actingAs($this->owner)->put('/admin/company', $this->payload([
            'logo' => UploadedFile::fake()->create('logo.svg', 10, 'image/svg+xml'),
        ]))->assertSessionHasErrors('logo');
    }
}
