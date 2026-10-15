<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Unggas', 'Ternak Kecil', 'Ternak Besar'] as $name) {
            Category::updateOrCreate(['type' => 'livestock', 'slug' => Str::slug($name)], ['name' => $name, 'is_active' => true]);
        }

        foreach (['Telur', 'Susu', 'Daging', 'Pupuk Organik', 'Pakan'] as $name) {
            Category::updateOrCreate(['type' => 'product', 'slug' => Str::slug($name)], ['name' => $name, 'is_active' => true]);
        }
    }
}
