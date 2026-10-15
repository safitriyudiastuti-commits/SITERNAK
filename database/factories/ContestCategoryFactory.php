<?php

namespace Database\Factories;

use App\Models\ContestCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ContestCategory> */
class ContestCategoryFactory extends Factory
{
    protected $model = ContestCategory::class;

    public function definition(): array
    {
        return ['name' => fake()->unique()->words(2, true), 'livestock_group' => fake()->randomElement(['unggas', 'ternak_kecil', 'ternak_besar']), 'is_active' => true];
    }
}
