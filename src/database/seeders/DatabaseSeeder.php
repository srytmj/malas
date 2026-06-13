<?php

namespace Database\Seeders;

use App\Modules\Core\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name'     => 'Super Admin',
            'email'    => 'superadmin@malas.local',
            'password' => Hash::make('password'),
            'role'     => 'super_admin',
        ]);

        User::create([
            'name'     => 'Test User',
            'email'    => 'user@malas.local',
            'password' => Hash::make('password'),
            'role'     => 'user',
        ]);
    }
}
