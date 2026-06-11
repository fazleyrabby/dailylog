<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['user_id', 'wallet_id', 'target_wallet_id', 'type', 'amount', 'occurred_on', 'description'])]
class WalletTransaction extends Model
{
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'occurred_on' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Entry::class, 'wallet_id');
    }

    public function targetWallet(): BelongsTo
    {
        return $this->belongsTo(Entry::class, 'target_wallet_id');
    }
}
