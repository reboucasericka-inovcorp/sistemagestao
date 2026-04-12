<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class ApplicationPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $names = [
            'entities.view',
            'entities.create',
            'entities.update',
            'entities.delete',
            'contacts.view',
            'contacts.create',
            'contacts.update',
            'contacts.delete',
        ];

        $user = User::query()->orderBy('id')->first();

        foreach ($names as $name) {
            $permission = Permission::firstOrCreate(
                ['name' => $name, 'guard_name' => 'sanctum']
            );

            if ($user !== null && ! $user->hasPermissionTo($permission)) {
                $user->givePermissionTo($permission);
            }
        }
    }
}
