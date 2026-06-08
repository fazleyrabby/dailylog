<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['entry_id', 'resource_type', 'author', 'url', 'consume_state', 'rating', 'external_ref'])]
class ResourceDetails extends Model
{
    protected $primaryKey = 'entry_id';
    public $incrementing = false;

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
        ];
    }

    public function entry(): BelongsTo
    {
        return $this->belongsTo(Entry::class, 'entry_id');
    }
}
