<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Livestock;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Livestock> */
class LivestockFactory extends Factory
{
    protected $model = Livestock::class;

    public function definition(): array
    {
        return ['store_id' => Store::factory(), 'category_id' => Category::factory(), 'name' => fake()->word(), 'sale_mode' => 'individual', 'sex' => 'male', 'price' => fake()->numberBetween(1000000, 25000000), 'stock' => 1, 'unit' => 'ekor', 'status' => 'active'];
    }
}
