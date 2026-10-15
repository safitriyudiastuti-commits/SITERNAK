<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['store_id', 'document_type', 'file_path', 'original_name', 'mime_type', 'status', 'review_note'])]
class StoreDocument extends Model
{
    public function store(): BelongsTo { return $this->belongsTo(Store::class); }
}
