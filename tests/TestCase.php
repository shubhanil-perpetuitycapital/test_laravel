<?php

namespace Tests;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function seedStore(): void
    {
        config(['store.admin.password' => 'secret-pass-123', 'store.products_per_page' => 50]);
        $this->seed(DatabaseSeeder::class);
    }

    protected function owner(array $attributes = []): User
    {
        return User::query()->create($attributes + [
            'name' => 'Owner', 'email' => 'owner'.uniqid().'@example.com', 'password' => 'correct-horse-123',
            'role' => User::ROLE_ADMIN, 'is_active' => true,
        ]);
    }

    protected function staff(): User
    {
        return $this->owner(['role' => User::ROLE_STAFF, 'name' => 'Staff']);
    }
}
