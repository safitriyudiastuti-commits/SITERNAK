<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['store_id', 'bank_name', 'account_number', 'account_holder', 'supporting_document_path', 'is_primary', 'status', 'verified_by', 'verified_at'])]
class StoreBankAccount extends Model
{
    protected function casts(): array
    {
        return ['is_primary' => 'boolean', 'verified_at' => 'datetime'];
    }

    public function store(): BelongsTo { return $this->belongsTo(Store::class); }
}
