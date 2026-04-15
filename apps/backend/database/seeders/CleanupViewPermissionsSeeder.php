<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class CleanupViewPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        DB::transaction(function (): void {
            $viewPermissions = Permission::query()
                ->where('name', 'like', '%.view')
                ->get();

            foreach ($viewPermissions as $viewPermission) {
                $readName = preg_replace('/\.view$/', '.read', $viewPermission->name);
                if (! is_string($readName) || $readName === '') {
                    continue;
                }

                $readPermission = Permission::firstOrCreate([
                    'name' => $readName,
                    'guard_name' => $viewPermission->guard_name,
                ]);

                foreach ($viewPermission->roles as $role) {
                    if (! $role->hasPermissionTo($readPermission)) {
                        $role->givePermissionTo($readPermission);
                    }
                }

                foreach ($viewPermission->users as $user) {
                    if (! $user->hasPermissionTo($readPermission)) {
                        $user->givePermissionTo($readPermission);
                    }
                }
            }

            Permission::query()
                ->where('name', 'like', '%.view')
                ->delete();
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
