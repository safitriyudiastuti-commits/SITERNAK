<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['livestock_id', 'contest_category_id', 'contest_name', 'organizer', 'rank', 'event_date', 'certificate_path', 'status', 'review_note', 'reviewed_by', 'reviewed_at'])]
class ContestRecord extends Model
{
    protected function casts(): array { return ['event_date' => 'date', 'reviewed_at' => 'datetime']; }
    public function livestock(): BelongsTo { return $this->belongsTo(Livestock::class); }
    public function category(): BelongsTo { return $this->belongsTo(ContestCategory::class, 'contest_category_id'); }
}
