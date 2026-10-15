<?php

namespace Database\Factories;

use App\Models\Breed;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Breed> */
class BreedFactory extends Factory
{
    protected $model = Breed::class;

    public function definition(): array
    {
        return ['category_id' => Category::factory(), 'name' => fake()->unique()->word(), 'is_active' => true];
    }
}
