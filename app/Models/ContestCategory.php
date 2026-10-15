<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'livestock_group', 'is_active'])]
class ContestCategory extends Model
{
    protected function casts(): array { return ['is_active' => 'boolean']; }
}
