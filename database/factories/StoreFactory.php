<?php

namespace Database\Factories;

use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Store> */
class StoreFactory extends Factory
{
    protected $model = Store::class;

    public function definition(): array
    {
        return ['user_id' => User::factory(), 'name' => fake()->company().' Farm', 'business_category' => fake()->randomElement(['unggas', 'ternak_kecil', 'ternak_besar']), 'city' => fake()->city(), 'regency' => fake()->city(), 'business_address' => fake()->address(), 'status' => 'pending'];
    }
}
