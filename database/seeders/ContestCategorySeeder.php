<?php

namespace Database\Seeders;

use App\Models\ContestCategory;
use Illuminate\Database\Seeder;

class ContestCategorySeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['name' => 'Kontes bobot', 'livestock_group' => 'ternak_besar'],
            ['name' => 'Kontes bibit unggul', 'livestock_group' => 'unggas'],
            ['name' => 'Kontes kambing/domba', 'livestock_group' => 'ternak_kecil'],
        ] as $data) {
            ContestCategory::updateOrCreate(['name' => $data['name']], [...$data, 'is_active' => true]);
        }
    }
}
