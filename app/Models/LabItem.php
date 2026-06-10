<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable([
    'entry_id', 'target_entry_id', 'type', 'title', 'content',
    'x', 'y', 'width', 'height', 'color'
])]
class LabItem extends Model
{
    use HasFactory;

    protected $casts = [
        'x' => 'integer',
        'y' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
    ];

    public function board(): BelongsTo
    {
        return $this->belongsTo(Entry::class, 'entry_id');
    }

    public function target(): BelongsTo
    {
        return $this->belongsTo(Entry::class, 'target_entry_id');
    }
}
