<?php

namespace Database\Seeders;

use App\Models\ContactFunctionModel;
use Illuminate\Database\Seeder;

class ContactFunctionSeeder extends Seeder
{
    public function run(): void
    {
        $names = [
            'Commercial',
            'Billing',
            'Technical',
            'Administrative',
        ];

        foreach ($names as $name) {
            ContactFunctionModel::firstOrCreate(
                ['name' => $name],
                ['is_active' => true]
            );
        }
    }
}
