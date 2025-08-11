<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->createSuperAdmin();
    }

    /**
     * Create the Super Admin user
     */
    private function createSuperAdmin(): void
    {
        $superAdmin = User::factory()->create([
            'first_name' => config('admin.first_name', 'Super'),
            'last_name' => config('admin.last_name', 'Admin'),
            'email' => config('admin.email', 'admin@genericsaas.com'),
            'phone_number' => config('admin.phone', '+237670000001'),
            'password' => Hash::make(config('admin.password', 'password')),
        ]);

        $superAdmin->assignRole(UserRole::ADMIN()->value);
    }
}
