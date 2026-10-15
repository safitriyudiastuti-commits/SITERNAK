<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => env('SITERNAK_ADMIN_EMAIL', 'admin@siternak.test')],
            ['name' => 'Administrator SITERNAK', 'password' => env('SITERNAK_ADMIN_PASSWORD', 'password'), 'role' => 'admin'],
        );
    }
}
