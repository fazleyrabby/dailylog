<?php

namespace App\Domains\Tags\Actions;

use App\Models\Tag;
use Illuminate\Support\Str;

class CreateTag
{
    public function execute(string $name, ?string $color = null): Tag
    {
        $name = trim($name);

        $existing = Tag::query()->where('name', $name)->first();
        if ($existing) {
            return $existing;
        }

        return Tag::query()->create([
            'name' => $name,
            'slug' => $this->uniqueSlug($name),
            'color' => $color,
        ]);
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i = 1;
        while (Tag::query()->where('slug', $slug)->exists()) {
            $slug = $base . '-' . (++$i);
        }
        return $slug;
    }
}
