<?php

namespace Database\Factories;

use App\Models\EntityModel;
use App\Models\Finance\SupplierInvoiceModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SupplierInvoiceModel>
 */
class SupplierInvoiceFactory extends Factory
{
    protected $model = SupplierInvoiceModel::class;

    public function definition(): array
    {
        return [
            'number' => 'FT-'.$this->faker->unique()->numerify('#####'),
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'supplier_id' => EntityModel::factory()->state([
                'is_supplier' => true,
                'is_client' => false,
            ]),
            'supplier_order_id' => null,
            'total_amount' => $this->faker->randomFloat(2, 10, 1000),
            'status' => 'pending_payment',
        ];
    }

    public function paid(): self
    {
        return $this->state(fn (): array => ['status' => 'paid']);
    }
}
