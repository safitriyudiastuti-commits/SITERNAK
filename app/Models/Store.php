<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['user_id', 'name', 'logo_path', 'description', 'business_category', 'contact_phone', 'city', 'district', 'regency', 'province', 'business_address', 'weekday_hours', 'saturday_hours', 'sunday_hours', 'status', 'review_note', 'reviewed_by', 'reviewed_at', 'bank_name', 'bank_account_number', 'bank_account_holder', 'is_holiday', 'holiday_message', 'holiday_until', 'holiday_start', 'holiday_end', 'hide_products_during_holiday'])]
class Store extends Model
{
    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
            'is_holiday' => 'boolean',
            'holiday_until' => 'date',
            'holiday_start' => 'date',
            'holiday_end' => 'date',
            'hide_products_during_holiday' => 'boolean',
        ];
    }

    public function owner(): BelongsTo { return $this->belongsTo(User::class, 'user_id'); }
    public function documents(): HasMany { return $this->hasMany(StoreDocument::class); }
    public function bankAccounts(): HasMany { return $this->hasMany(StoreBankAccount::class); }
    public function livestock(): HasMany { return $this->hasMany(Livestock::class); }
    public function products(): HasMany { return $this->hasMany(Product::class); }
    public function orders(): HasMany { return $this->hasMany(Order::class); }
}
