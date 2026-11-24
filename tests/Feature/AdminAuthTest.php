<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        foreach (['/admin', '/admin/orders', '/admin/products', '/admin/settings/company'] as $url) {
            $this->get($url)->assertRedirect('/admin/login');
        }
        $this->get('/admin/login')->assertOk()->assertSee('Sign in to the admin');
    }

    public function test_owner_can_sign_in_and_out(): void
    {
        $user = $this->owner(['email' => 'owner@example.com']);

        $this->post('/admin/login', ['email' => 'owner@example.com', 'password' => 'correct-horse-123'])->assertRedirect('/admin');
        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->last_login_at);

        $this->post('/admin/logout')->assertRedirect('/admin/login');
        $this->assertGuest();
    }

    public function test_wrong_password_inactive_and_rate_limit(): void
    {
        $this->owner(['email' => 'owner@example.com']);
        $this->post('/admin/login', ['email' => 'owner@example.com', 'password' => 'nope'])->assertSessionHasErrors('email');

        for ($i = 0; $i < 4; $i++) {
            $this->post('/admin/login', ['email' => 'owner@example.com', 'password' => 'wrong']);
        }
        $this->post('/admin/login', ['email' => 'owner@example.com', 'password' => 'correct-horse-123'])
            ->assertSessionHasErrors(['email' => 'Too many sign-in attempts. Try again in 5 minute(s).']);
        $this->assertGuest();

        $this->owner(['email' => 'off@example.com', 'is_active' => false]);
        $this->post('/admin/login', ['email' => 'off@example.com', 'password' => 'correct-horse-123'])->assertSessionHasErrors('email');
    }

    public function test_deactivated_session_is_signed_out(): void
    {
        $user = $this->owner();
        $this->actingAs($user);
        $user->update(['is_active' => false]);

        $this->get('/admin')->assertRedirect('/admin/login');
    }

    public function test_staff_cannot_open_owner_screens(): void
    {
        $staff = $this->staff();
        $this->actingAs($staff);

        $this->get('/admin')->assertOk()->assertDontSee('Company setup');
        $this->get('/admin/orders')->assertOk();
        foreach (['/admin/settings/company', '/admin/settings/store', '/admin/coupons', '/admin/delivery-zones', '/admin/users'] as $url) {
            $this->get($url)->assertForbidden();
        }
    }

    public function test_profile_password_change_requires_current_password(): void
    {
        $user = $this->owner(['email' => 'owner@example.com']);

        $this->actingAs($user)->put('/admin/profile', [
            'name' => 'Owner', 'email' => 'owner@example.com',
            'current_password' => 'wrong', 'password' => 'newpassword99', 'password_confirmation' => 'newpassword99',
        ])->assertSessionHasErrors('current_password');

        $this->actingAs($user)->put('/admin/profile', [
            'name' => 'Owner Renamed', 'email' => 'owner@example.com',
            'current_password' => 'correct-horse-123', 'password' => 'newpassword99', 'password_confirmation' => 'newpassword99',
        ])->assertSessionHasNoErrors();

        $this->assertTrue(password_verify('newpassword99', $user->fresh()->password));
    }
}
