<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['code', 'buyer_id', 'store_id', 'status', 'total_amount', 'delivery_city', 'delivery_address', 'completed_at'])]
class Order extends Model
{
    protected function casts(): array { return ['total_amount' => 'decimal:2', 'completed_at' => 'datetime']; }
    public function buyer(): BelongsTo { return $this->belongsTo(User::class, 'buyer_id'); }
    public function store(): BelongsTo { return $this->belongsTo(Store::class); }
    public function items(): HasMany { return $this->hasMany(OrderItem::class); }
    public function payment(): HasOne { return $this->hasOne(Payment::class); }
}
