<?php

namespace App\Models\Concerns;

trait OwnedByUser
{
    protected static function bootOwnedByUser(): void
    {
        static::addGlobalScope(new OwnedByUserScope);

        static::creating(function ($model) {
            if (empty($model->user_id) && auth()->check()) {
                $model->user_id = auth()->id();
            }
        });
    }

    public function scopeWithoutOwnership($query)
    {
        return $query->withoutGlobalScope(OwnedByUserScope::class);
    }
}
