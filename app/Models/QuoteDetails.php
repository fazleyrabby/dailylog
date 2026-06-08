<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['entry_id', 'author', 'source', 'location'])]
class QuoteDetails extends Model
{
    protected $primaryKey = 'entry_id';
    public $incrementing = false;

    public function entry(): BelongsTo
    {
        return $this->belongsTo(Entry::class, 'entry_id');
    }
}
