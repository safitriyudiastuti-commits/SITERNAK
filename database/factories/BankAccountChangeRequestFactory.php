<?php

namespace Database\Factories;

use App\Models\BankAccountChangeRequest;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<BankAccountChangeRequest> */
class BankAccountChangeRequestFactory extends Factory
{
    protected $model = BankAccountChangeRequest::class;

    public function definition(): array
    {
        return ['store_id' => Store::factory(), 'requested_by' => User::factory(), 'bank_name' => fake()->company(), 'account_number' => fake()->numerify('################'), 'account_holder' => fake()->name(), 'status' => 'pending'];
    }
}
