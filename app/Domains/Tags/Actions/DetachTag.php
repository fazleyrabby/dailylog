<?php

namespace App\Domains\Tags\Actions;

use App\Models\Entry;
use App\Models\Tag;

class DetachTag
{
    public function execute(Entry $entry, Tag $tag): void
    {
        $entry->tags()->detach($tag->id);
    }
}
