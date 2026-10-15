<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['store_id', 'requested_by', 'bank_name', 'account_number', 'account_holder', 'supporting_document_path', 'status', 'review_note', 'reviewed_by', 'reviewed_at'])]
class BankAccountChangeRequest extends Model
{
    protected function casts(): array { return ['reviewed_at' => 'datetime']; }

    public function store(): BelongsTo { return $this->belongsTo(Store::class); }
    public function requester(): BelongsTo { return $this->belongsTo(User::class, 'requested_by'); }
}
