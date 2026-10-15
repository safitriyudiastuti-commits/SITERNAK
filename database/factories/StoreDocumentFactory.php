<?php

namespace Database\Factories;

use App\Models\Store;
use App\Models\StoreDocument;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<StoreDocument> */
class StoreDocumentFactory extends Factory
{
    protected $model = StoreDocument::class;

    public function definition(): array
    {
        return ['store_id' => Store::factory(), 'document_type' => fake()->randomElement(['ktp', 'business_location', 'bank_book']), 'file_path' => 'store-documents/demo.pdf', 'original_name' => 'dokumen-demo.pdf', 'mime_type' => 'application/pdf', 'status' => 'pending'];
    }
}
