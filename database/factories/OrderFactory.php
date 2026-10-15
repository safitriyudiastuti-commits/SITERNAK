<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Order> */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        return ['code' => fake()->unique()->bothify('ORD-######'), 'buyer_id' => User::factory(), 'store_id' => Store::factory(), 'status' => 'pending_confirmation', 'total_amount' => 0];
    }
}
