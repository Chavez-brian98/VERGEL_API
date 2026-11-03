<?php

namespace Database\Factories;

use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Supplier>
 */
class SupplierFactory extends Factory
{
    protected $model = Supplier::class;

    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'trade_name' => fake()->optional()->company(),
            'tax_id' => '0614-' . fake()->numerify('######') . '-101-' . fake()->randomDigit(),
            'nrc' => fake()->numerify('######-#'),
            'economic_activity' => fake()->randomElement(['Venta de Insumos Agrícolas', 'Vivero y Jardinería', 'Ferretería y Herramientas', 'Fertilizantes y Sustratos']),
            'phone' => fake()->numerify('7###-####'),
            'email' => fake()->unique()->safeEmail(),
            'address' => fake()->address(),
            'contact_name' => fake()->name(),
            'contact_phone' => fake()->numerify('7###-####'),
            'status' => 'active',
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
