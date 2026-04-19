<?php

namespace Database\Seeders;

use App\Models\Access\RoleModel;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * Sincroniza roles com permissões Spatie (guard sanctum), de forma idempotente.
 * Executar após AccessSeeder (catálogo de permissões criado).
 */
class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $registrar = app(PermissionRegistrar::class);
        $registrar->forgetCachedPermissions();

        $guard = 'sanctum';

        $allPermissions = Permission::query()->where('guard_name', $guard)->get();

        $admin = RoleModel::query()->firstOrCreate(
            ['name' => 'admin', 'guard_name' => $guard],
            ['is_active' => true],
        );
        $admin->syncPermissions($allPermissions);

        $viewer = RoleModel::query()->firstOrCreate(
            ['name' => 'viewer', 'guard_name' => $guard],
            ['is_active' => true],
        );
        $readOnly = $allPermissions->filter(fn (Permission $p): bool => str_ends_with($p->name, '.read'));
        $viewer->syncPermissions($readOnly);

        $manager = RoleModel::query()->firstOrCreate(
            ['name' => 'manager', 'guard_name' => $guard],
            ['is_active' => true],
        );
        $withoutRolesAdmin = $allPermissions->filter(
            fn (Permission $p): bool => ! str_starts_with($p->name, 'roles.'),
        );
        $manager->syncPermissions($withoutRolesAdmin);

        $named = [
            'Visualizador de Logs' => ['logs.read'],
            'Gerente de Artigos' => ['articles.read', 'articles.create', 'articles.update'],
            'Editor de Calendário' => [
                'calendar-events.read',
                'calendar-events.create',
                'calendar-events.update',
                'calendar-events.delete',
                'calendar-types.read',
                'calendar-actions.read',
            ],
        ];

        foreach ($named as $roleName => $permissionNames) {
            $role = RoleModel::query()->firstOrCreate(
                ['name' => $roleName, 'guard_name' => $guard],
                ['is_active' => true],
            );
            $perms = Permission::query()
                ->where('guard_name', $guard)
                ->whereIn('name', $permissionNames)
                ->get();
            $role->syncPermissions($perms);
        }

        $registrar->forgetCachedPermissions();
    }
}
