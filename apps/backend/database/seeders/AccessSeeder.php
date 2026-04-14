<?php

namespace Database\Seeders;

use App\Models\Access\RoleModel;
use App\Models\User;
use App\Support\PermissionCatalog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;

class AccessSeeder extends Seeder
{
    public function run(): void
    {
        $modules = PermissionCatalog::modules();
        $actions = PermissionCatalog::actions();

        foreach ($modules as $module) {
            foreach ($actions as $action) {
                Permission::firstOrCreate([
                    'name' => "{$module}.{$action}",
                    'guard_name' => 'sanctum',
                ]);
            }
        }

        $adminRole = RoleModel::query()->firstOrCreate([
            'name' => 'admin',
            'guard_name' => 'sanctum',
        ], [
            'is_active' => true,
        ]);
        $adminRole->update(['is_active' => true]);

        $adminRole->syncPermissions(Permission::query()->where('guard_name', 'sanctum')->get());
        $adminUser = User::firstOrCreate(
            ['email' => 'admin@sistemagestao.com'],
            [
                'name' => 'Admin',
                'password' => Hash::make('admin123456789'),
                'is_active' => true,
            ]
        );
        if (! $adminUser->is_active) {
            $adminUser->update(['is_active' => true]);
        }

        $adminUser->syncRoles([$adminRole->name]);
    }
}
