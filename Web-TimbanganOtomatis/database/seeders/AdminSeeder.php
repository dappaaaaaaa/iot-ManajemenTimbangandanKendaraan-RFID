<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        // Buat role jika belum ada
        $operator = Role::firstOrCreate(['name' => 'operator']);
        $accessor = Role::firstOrCreate(['name' => 'accessor']);

        // Buat user admin jika belum ada
        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin Akses',
                'password' => bcrypt('admin123'), // Ganti nanti setelah login!
            ]
        );

        // Assign role accessor
        if (! $admin->hasRole('accessor')) {
            $admin->assignRole('accessor');
        }
    }
}

