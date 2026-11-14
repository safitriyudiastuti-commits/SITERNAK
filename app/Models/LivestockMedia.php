<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LivestockMedia extends Model
{
    protected $table = 'livestock_media';

    protected $fillable = [
        'path',
        'original_name',
        'mime_type',
        'size',
        'type',
    ];

    protected function casts(): array
    {
        return ['size' => 'integer'];
    }

    public function livestock(): BelongsTo
    {
        return $this->belongsTo(Livestock::class);
    }
}
