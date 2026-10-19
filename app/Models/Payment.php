<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['order_id', 'method', 'status', 'amount', 'proof_path', 'transaction_reference', 'payer_name', 'bank_name', 'destination_account', 'destination_account_name', 'transferred_at', 'qris_callback_reference', 'verification_note', 'verified_by', 'verified_at'])]
class Payment extends Model
{
    protected function casts(): array { return ['amount' => 'decimal:2', 'verified_at' => 'datetime', 'transferred_at' => 'datetime']; }
    public function order(): BelongsTo { return $this->belongsTo(Order::class); }
}
