<?php

namespace Database\Factories;

use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Store>
 */
class StoreFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->state(['role' => 'peternak']),
            'name' => fake()->company(),
            'business_categories' => ['ternak_besar'],
            'city' => fake()->city(),
            'province' => fake()->state(),
            'address' => fake()->address(),
            'status' => 'active',
            'vacation_mode' => false,
            'vacation_hide_listings' => false,
        ];
    }
}
