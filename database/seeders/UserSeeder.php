<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->create([
            'name'     => 'Super Admin',
            'email'    => 'superadmin@malas.dev',
            'password' => Hash::make('password'),
            'role'     => 'super_admin',
        ]);

        User::factory()->create([
            'name'     => 'Admin',
            'email'    => 'admin@malas.dev',
            'password' => Hash::make('password'),
            'role'     => 'admin',
        ]);

        User::factory()->create([
            'name'     => 'QA User',
            'email'    => 'qa@malas.dev',
            'password' => Hash::make('password'),
            'role'     => 'user',
        ]);

        User::factory()->count(3)->create([
            'role' => 'user',
        ]);
    }
}
