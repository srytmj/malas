<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure the super_admin Spatie role exists (created by shield:generate)
        $role = Role::firstOrCreate(
            ['name' => 'super_admin', 'guard_name' => 'web'],
        );

        $user = User::firstOrCreate(
            ['email' => 'admin@malas.test'],
            [
                'name' => 'MALAS Admin',
                'password' => 'password',
                'role' => 'super_admin',
                'email_verified_at' => now(),
            ]
        );

        $user->assignRole($role);
    }
}
