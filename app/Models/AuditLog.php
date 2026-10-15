<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['admin_id', 'action', 'subject_type', 'subject_id', 'description', 'metadata'])]
class AuditLog extends Model
{
    protected function casts(): array
    {
        return ['metadata' => 'array'];
    }
}
