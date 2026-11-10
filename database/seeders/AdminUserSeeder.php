<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@sparkling.test'],
            [
                'name' => 'System Admin',
                'password' => 'Admin@123456',
                'role' => 'admin',
                'active' => true,
            ]
        );
    }
}
