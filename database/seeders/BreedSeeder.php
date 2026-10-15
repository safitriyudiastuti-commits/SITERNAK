<?php

namespace Database\Seeders;

use App\Models\Breed;
use App\Models\Category;
use Illuminate\Database\Seeder;

class BreedSeeder extends Seeder
{
    public function run(): void
    {
        $examples = ['Unggas' => ['Ayam Broiler', 'Ayam Kampung', 'Itik'], 'Ternak Kecil' => ['Kambing Etawa', 'Domba Garut'], 'Ternak Besar' => ['Sapi Limousin', 'Sapi Simmental']];

        foreach ($examples as $categoryName => $breeds) {
            $category = Category::where('type', 'livestock')->where('name', $categoryName)->first();
            if (! $category) {
                continue;
            }

            foreach ($breeds as $breedName) {
                Breed::updateOrCreate(['category_id' => $category->id, 'name' => $breedName], ['is_active' => true]);
            }
        }
    }
}
