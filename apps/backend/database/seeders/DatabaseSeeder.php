<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::updateOrCreate(
            ['email' => 'admin@sistemagestao.com'],
            [
                'name' => 'Administrador',
                'password' => 'admin123456789',
            ]
        );

        $this->call(ContactFunctionSeeder::class);
        $this->call(ApplicationPermissionsSeeder::class);
    }
}
