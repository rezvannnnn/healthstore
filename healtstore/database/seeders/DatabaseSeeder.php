<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        User::query()->updateOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'phone' => '09110000001',
                'role' => User::ROLE_USER,
                'is_admin' => false,
                'phone_verified_at' => now(),
                'password' => null,
                'admin_username' => null,
                'admin_title' => null,
                'admin_active' => true,
                'admin_permissions' => [],
            ],
        );

        $adminPassword = config('admin.seed.password');

        if (filled($adminPassword)) {
            User::query()->updateOrCreate(
                ['admin_username' => strtolower((string) config('admin.seed.username', 'admin'))],
                [
                    'name' => 'مدیر داروخونه',
                    'email' => 'admin@darukhooneh.local',
                    'admin_username' => strtolower((string) config('admin.seed.username', 'admin')),
                    'admin_title' => 'مدیر سیستم',
                    'password' => $adminPassword,
                    'role' => User::ROLE_ADMIN,
                    'is_admin' => true,
                    'admin_active' => true,
                    'admin_permissions' => [],
                ],
            );
        }
    }
}
