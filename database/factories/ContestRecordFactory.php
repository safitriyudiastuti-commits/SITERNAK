<?php

namespace Database\Factories;

use App\Models\ContestRecord;
use App\Models\Livestock;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ContestRecord> */
class ContestRecordFactory extends Factory
{
    protected $model = ContestRecord::class;

    public function definition(): array
    {
        return ['livestock_id' => Livestock::factory(), 'contest_name' => fake()->words(3, true), 'event_date' => fake()->date(), 'certificate_path' => 'contest-certificates/demo.pdf', 'status' => 'pending'];
    }
}
