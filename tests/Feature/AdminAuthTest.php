<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAuthTest extends TestCase
{
    use RefreshDatabase;

    private function owner(array $attributes = []): User
    {
        return User::query()->create($attributes + [
            'name' => 'Owner',
            'email' => 'owner@example.com',
            'password' => 'correct-horse-123',
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
        $this->get('/admin/company')->assertRedirect('/admin/login');
    }

    public function test_login_page_renders(): void
    {
        $this->get('/admin/login')->assertOk()->assertSee('Sign in to the admin');
    }

    public function test_owner_can_sign_in_and_out(): void
    {
        $this->owner();

        $this->post('/admin/login', ['email' => 'owner@example.com', 'password' => 'correct-horse-123'])
            ->assertRedirect('/admin');

        $this->assertAuthenticated();
        $this->get('/admin')->assertOk()->assertSee('Finish setting up your store');

        $this->post('/admin/logout')->assertRedirect('/admin/login');
        $this->assertGuest();
    }

    public function test_wrong_password_is_rejected(): void
    {
        $this->owner();

        $this->from('/admin/login')
            ->post('/admin/login', ['email' => 'owner@example.com', 'password' => 'nope'])
            ->assertRedirect('/admin/login')
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_inactive_accounts_cannot_sign_in(): void
    {
        $this->owner(['is_active' => false]);

        $this->post('/admin/login', ['email' => 'owner@example.com', 'password' => 'correct-horse-123'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_deactivated_session_is_signed_out(): void
    {
        $user = $this->owner();
        $this->actingAs($user);
        $user->update(['is_active' => false]);

        $this->get('/admin')->assertRedirect('/admin/login');
    }

    public function test_login_is_rate_limited_after_five_failures(): void
    {
        $this->owner();

        for ($i = 0; $i < 5; $i++) {
            $this->post('/admin/login', ['email' => 'owner@example.com', 'password' => 'wrong']);
        }

        $this->post('/admin/login', ['email' => 'owner@example.com', 'password' => 'correct-horse-123'])
            ->assertSessionHasErrors(['email' => 'Too many sign-in attempts. Try again in 5 minute(s).']);

        $this->assertGuest();
    }

    public function test_staff_cannot_open_company_setup(): void
    {
        $staff = $this->owner(['email' => 'staff@example.com', 'role' => User::ROLE_STAFF]);

        $this->actingAs($staff)->get('/admin')->assertOk()->assertDontSee('Company setup');
        $this->actingAs($staff)->get('/admin/company')->assertForbidden();
    }

    public function test_profile_password_change_requires_current_password(): void
    {
        $user = $this->owner();

        $this->actingAs($user)->from('/admin/profile')->put('/admin/profile', [
            'name' => 'Owner', 'email' => 'owner@example.com',
            'current_password' => 'wrong', 'password' => 'newpassword99', 'password_confirmation' => 'newpassword99',
        ])->assertSessionHasErrors('current_password');

        $this->actingAs($user)->put('/admin/profile', [
            'name' => 'Owner Renamed', 'email' => 'owner@example.com',
            'current_password' => 'correct-horse-123', 'password' => 'newpassword99', 'password_confirmation' => 'newpassword99',
        ])->assertSessionHasNoErrors();

        $this->assertSame('Owner Renamed', $user->fresh()->name);
        $this->assertTrue(password_verify('newpassword99', $user->fresh()->password));
    }
}
