<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use App\Models\User;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // Buat role jika belum ada
        $operator = Role::firstOrCreate(['name' => 'operator']);
        $accessor = Role::firstOrCreate(['name' => 'accessor']);

        // Assign role ke user tertentu (misal ID 2 sampai 5 operator, 6-10 accessor)
        User::find(1)?->assignRole('operator');
        User::find(2)?->assignRole('operator');
        User::find(3)?->assignRole('operator');
        User::find(4)?->assignRole('operator');
        User::find(5)?->assignRole('operator');

        User::find(7)?->assignRole('accessor');
        User::find(8)?->assignRole('accessor');
        User::find(9)?->assignRole('accessor');
        User::find(10)?->assignRole('accessor');
    }
}
