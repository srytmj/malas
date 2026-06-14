<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@malas.test'],
            [
                'name' => 'MALAS Admin',
                'password' => 'password',
                'role' => 'super_admin',
                'email_verified_at' => now(),
            ]
        );
    }
}
