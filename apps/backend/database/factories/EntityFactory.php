<?php

namespace Database\Factories;

use App\Models\EntityModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EntityModel>
 */
class EntityFactory extends Factory
{
    protected $model = EntityModel::class;

    public function definition(): array
    {
        return [
            'number' => 'ENT-'.$this->faker->unique()->numerify('######'),
            'nif' => $this->faker->unique()->numerify('#########'),
            'name' => $this->faker->company(),
            'address' => $this->faker->streetAddress(),
            'postal_code' => '1000-100',
            'city' => $this->faker->city(),
            'email' => $this->faker->safeEmail(),
            'gdpr_consent' => false,
            'is_client' => true,
            'is_supplier' => false,
            'is_active' => true,
        ];
    }
}
