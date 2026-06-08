<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['entry_id', 'kind', 'provider', 'progress', 'total_units', 'completed_units', 'status', 'target_date'])]
class LearningDetails extends Model
{
    protected $primaryKey = 'entry_id';
    public $incrementing = false;

    protected function casts(): array
    {
        return [
            'progress' => 'integer',
            'total_units' => 'integer',
            'completed_units' => 'integer',
            'target_date' => 'date',
        ];
    }

    public function entry(): BelongsTo
    {
        return $this->belongsTo(Entry::class, 'entry_id');
    }
}
