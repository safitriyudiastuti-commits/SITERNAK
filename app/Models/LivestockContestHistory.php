<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LivestockContestHistory extends Model
{
    protected $fillable = [
        'livestock_id',
        'contest_name',
        'category',
        'rank',
        'contest_date',
        'organizer',
        'certificate_path',
        'certificate_original_name',
        'certificate_mime_type',
        'status',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'contest_date' => 'date',
        ];
    }

    public function livestock(): BelongsTo
    {
        return $this->belongsTo(Livestock::class);
    }

    public function scopePublicVerified(Builder $query): Builder
    {
        return $query->where('status', 'verified');
    }
}
