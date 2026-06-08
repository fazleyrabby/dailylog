<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['source_id', 'target_id', 'relation'])]
class EntryLink extends Model
{
    public $timestamps = false;

    public function source(): BelongsTo
    {
        return $this->belongsTo(Entry::class, 'source_id');
    }

    public function target(): BelongsTo
    {
        return $this->belongsTo(Entry::class, 'target_id');
    }
}
