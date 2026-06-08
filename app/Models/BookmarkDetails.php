<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['entry_id', 'url', 'site', 'description', 'favicon_url', 'image_url', 'fetched_at', 'review_state', 'raw_meta'])]
class BookmarkDetails extends Model
{
    protected $primaryKey = 'entry_id';
    public $incrementing = false;

    protected function casts(): array
    {
        return [
            'fetched_at' => 'datetime',
            'raw_meta' => 'array',
        ];
    }

    public function entry(): BelongsTo
    {
        return $this->belongsTo(Entry::class, 'entry_id');
    }
}
