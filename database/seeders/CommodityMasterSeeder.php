<?php

namespace Database\Seeders;

use App\Models\CommodityCategory;
use App\Models\CommodityType;
use Illuminate\Database\Seeder;

class CommodityMasterSeeder extends Seeder
{
    public function run(): void
    {
        $live = ['bibit', 'pembesaran', 'siap_potong', 'indukan_kontes'];
        $liveNoContest = ['bibit', 'pembesaran', 'siap_potong'];
        $contest = ['Postur', 'Bobot badan', 'Tanduk'];

        // [kelompok, kategori, urutan, [[jenis, mode, peruntukan, kategori kontes], ...]]
        $data = [
            ['ternak_hidup', 'Unggas', 1, [
                ['Ayam Broiler', ['batch'], $liveNoContest, []],
                ['Ayam Kampung', ['batch'], $liveNoContest, []],
                ['Ayam Ketawa', ['individual', 'batch'], $live, []],
                ['Bebek Peking', ['batch'], $liveNoContest, []],
                ['Burung Puyuh', ['batch'], $liveNoContest, []],
            ]],
            ['ternak_hidup', 'Ternak kecil', 2, [
                ['Kambing Etawa', ['individual', 'batch'], $live, $contest],
                ['Domba Garut', ['individual', 'batch'], $live, $contest],
            ]],
            ['ternak_hidup', 'Ternak besar', 3, [
                ['Sapi Limousin', ['individual', 'batch'], $live, $contest],
                ['Sapi Brahman', ['individual', 'batch'], $live, $contest],
            ]],
            ['hasil_ternak', 'Hasil ternak', 4, [
                ['Daging', ['produk'], [], []],
                ['Telur', ['produk'], [], []],
                ['Susu', ['produk'], [], []],
                ['Kulit dan pupuk', ['produk'], [], []],
            ]],
        ];

        foreach ($data as [$group, $categoryName, $order, $types]) {
            $category = CommodityCategory::updateOrCreate(
                ['name' => $categoryName],
                ['group' => $group, 'sort_order' => $order, 'is_active' => true]
            );

            foreach ($types as [$name, $modes, $purposes, $contestCategories]) {
                // updateOrCreate: aman dijalankan berulang, tidak membuat data ganda.
                CommodityType::updateOrCreate(
                    ['category_id' => $category->id, 'name' => $name],
                    [
                        'selling_modes' => $modes,
                        'purposes' => $purposes,
                        'form_fields' => $group === 'ternak_hidup' ? CommodityType::DEFAULT_FORM_FIELDS : [],
                        'contest_categories' => $contestCategories,
                    ]
                );
            }
        }
    }
}
