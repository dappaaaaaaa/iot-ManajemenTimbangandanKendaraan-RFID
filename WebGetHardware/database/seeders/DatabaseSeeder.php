<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. jalankan PermissionSeeder lebih dulu
        $this->call(PermissionSeeder::class);

        // 2. ambil role yang sudah dibuat
        $operator = Role::findByName('operator');
        $accessor = Role::findByName('accessor');

        // 3. buat 15 user demo
        for ($i = 1; $i <= 15; $i++) {
            $user = User::firstOrCreate(
                ['email' => "admin{$i}@admin.com"],
                [
                    'name'     => "admin{$i}",
                    'password' => Hash::make('12345678'),
                ]
            );

            // 1‒12 → operator, 13‒15 → accessor
            if ($i > 12) {
                $user->syncRoles($accessor);
            } else {
                $user->syncRoles($operator);
            }
        }

        $this->command->info('✅ 15 demo users created with roles.');
    }
}
