<?php

namespace App\Domains\Tags\Actions;

use App\Models\Tag;
use Illuminate\Support\Str;

class RenameTag
{
    public function execute(Tag $tag, string $name): Tag
    {
        $name = trim($name);

        if ($name === $tag->name) {
            return $tag;
        }

        $tag->name = $name;
        $tag->slug = $this->uniqueSlug($name, $tag->id);
        $tag->save();

        return $tag;
    }

    private function uniqueSlug(string $name, int $ignoreId): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i = 1;
        while (Tag::query()->where('slug', $slug)->where('id', '!=', $ignoreId)->exists()) {
            $slug = $base . '-' . (++$i);
        }
        return $slug;
    }
}
