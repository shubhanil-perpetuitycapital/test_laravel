<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use RuntimeException;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = (string) config('store.admin.email');
        $password = config('store.admin.password');

        if (User::query()->where('email', $email)->exists()) {
            $this->command?->info("Admin {$email} already exists — password left unchanged.");

            return;
        }

        if (blank($password)) {
            if (app()->environment('production')) {
                throw new RuntimeException('Set ADMIN_PASSWORD in .env before seeding in production.');
            }

            $password = Str::password(16, symbols: false);
            $this->command?->warn("ADMIN_PASSWORD not set. Generated password for {$email}: {$password}");
        }

        User::query()->create([
            'name' => config('store.admin.name'),
            'email' => $email,
            'password' => $password,
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $this->command?->info("Admin account created: {$email}");
    }
}
