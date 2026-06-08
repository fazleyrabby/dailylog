<?php

namespace App\Models;

use App\Models\Concerns\OwnedByUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable([
    'user_id', 'subject_type', 'subject_id', 'rule', 
    'slipping_since', 'severity', 'snoozed_until', 
    'resolved_at', 'computed_at'
])]
class SlippingSnapshot extends Model
{
    use OwnedByUser;

    protected function casts(): array
    {
        return [
            'slipping_since' => 'datetime',
            'severity' => 'integer',
            'snoozed_until' => 'datetime',
            'resolved_at' => 'datetime',
            'computed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subject()
    {
        return $this->morphTo('subject', 'subject_type', 'subject_id');
    }
}
