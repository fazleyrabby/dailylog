<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['user_id', 'ip_address', 'server_name', 'latency_ms', 'download_speed', 'upload_speed'])]
class SpeedtestLog extends Model
{
    protected function casts(): array
    {
        return [
            'latency_ms' => 'float',
            'download_speed' => 'float',
            'upload_speed' => 'float',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
