<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        // 1. daftar resource & aksi yang ingin dibuat
        $resources = ['rfid', 'measurement'];         // tambahkan jika ada
        $actions   = ['view', 'create', 'edit', 'delete'];
        $guard     = 'web';

        // 2. buat permission CRUD
        foreach ($resources as $res) {
            foreach ($actions as $act) {
                Permission::firstOrCreate([
                    'name'       => "$act $res",
                    'guard_name' => $guard,
                ]);
            }
        }

        // 3. tentukan hak tiap role
        $operatorPerm = Permission::whereIn('name', [
            'view rfid',
            'create rfid',
            'view measurement',
        ])->pluck('name');

        $accessorPerm = Permission::whereIn('name', [
            'view rfid',
            'view measurement',
            'create measurement',
            'edit measurement',
            'delete measurement',
        ])->pluck('name');

        // 4. sinkron ke role
        $operator = Role::firstOrCreate(['name' => 'operator', 'guard_name' => $guard]);
        $operator->syncPermissions($operatorPerm);

        $accessor = Role::firstOrCreate(['name' => 'accessor', 'guard_name' => $guard]);
        $accessor->syncPermissions($accessorPerm);

        $this->command->info('✅ Permissions & roles synced.');
    }
}
