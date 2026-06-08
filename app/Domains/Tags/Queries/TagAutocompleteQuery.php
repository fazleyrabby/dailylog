<?php

namespace App\Domains\Tags\Queries;

use App\Models\Tag;
use Illuminate\Support\Collection;

class TagAutocompleteQuery
{
    public function run(string $prefix, int $limit = 10): Collection
    {
        $prefix = trim($prefix);
        $query = Tag::query();

        if ($prefix !== '') {
            $query->where('name', 'ilike', $prefix . '%');
        }

        return $query
            ->orderBy('name')
            ->limit($limit)
            ->get(['id', 'name', 'slug', 'color']);
    }
}
