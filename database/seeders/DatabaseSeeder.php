<?php

namespace DatabaseSeeders;

use AppModelsUser;
use IlluminateDatabaseConsoleSeedsWithoutModelEvents;
use IlluminateDatabaseSeeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        $users = [
            [
                'name' => 'مدیر داروخونه',
                'email' => 'admin@darukhooneh.local',
                'phone' => '09000000001',
                'role' => User::ROLE_ADMIN,
                'is_admin' => true,
            ],
            [
                'name' => 'کاربر تست',
                'email' => 'user@darukhooneh.local',
                'phone' => '09000000002',
                'role' => User::ROLE_USER,
                'is_admin' => false,
            ],
            [
                'name' => 'مسئول انبار',
                'email' => 'storagekeeper@darukhooneh.local',
                'phone' => '09000000003',
                'role' => User::ROLE_STORAGEKEEPER,
                'is_admin' => false,
            ],
        ];

        foreach ($users as $userData) {
            User::updateOrCreate(
                ['email' => $userData['email']],
                [
                    ...$userData,
                    'phone_verified_at' => now(),
                    'password' => null,
                ],
            );
        }
    }
}
