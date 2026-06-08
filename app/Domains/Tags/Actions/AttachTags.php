<?php

namespace App\Domains\Tags\Actions;

use App\Models\Entry;
use App\Models\Tag;

class AttachTags
{
    public function __construct(private readonly CreateTag $create)
    {
    }

    /**
     * @param  list<string>  $names
     * @return list<Tag>
     */
    public function execute(Entry $entry, array $names): array
    {
        $tags = [];
        foreach (array_unique($names) as $name) {
            $tags[] = $this->create->execute($name);
        }

        $entry->tags()->syncWithoutDetaching(array_column($tags, 'id'));

        return $tags;
    }
}
