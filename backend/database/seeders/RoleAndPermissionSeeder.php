<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleAndPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 権限を作る
        $permissions = [
            'safety-check.create',
            'safety-check.close',
            'safety-check.view-all',
            'user.manage',
            'safety-check.view',
            'safety-response.create',
        ];

        foreach ($permissions as $permission) {
            Permission::create([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        // 役割を作る
        $admin = Role::create([
            'name' => 'admin',
        ]);

        $employee = Role::create([
            'name' => 'employee',
        ]);


        // 権限をDBから取得
        $adminPermissions = Permission::whereIn('name', [
            'safety-check.create',
            'safety-check.close',
            'safety-check.view-all',
            'user.manage',
        ])->get();

        $employeePermissions = Permission::whereIn('name', [
            'safety-check.view',
            'safety-response.create',
        ])->get();

        // 管理者に権限を付ける
        $admin->givePermissionTo($adminPermissions);

        // 社員に権限を付ける
        $employee->givePermissionTo($employeePermissions);

    }
}
