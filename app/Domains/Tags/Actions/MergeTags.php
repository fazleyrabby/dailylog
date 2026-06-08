<?php

namespace App\Domains\Tags\Actions;

use App\Models\Tag;
use Illuminate\Support\Facades\DB;

class MergeTags
{
    /**
     * Move every pivot row from $source to $target, then delete source.
     * Both tags must belong to the same owner (global scope guarantees this).
     */
    public function execute(Tag $source, Tag $target): Tag
    {
        if ($source->id === $target->id) {
            return $target;
        }

        DB::transaction(function () use ($source, $target) {
            DB::table('entry_tag')
                ->where('tag_id', $source->id)
                ->whereNotIn('entry_id', function ($q) use ($target) {
                    $q->select('entry_id')->from('entry_tag')->where('tag_id', $target->id);
                })
                ->update(['tag_id' => $target->id]);

            DB::table('entry_tag')->where('tag_id', $source->id)->delete();

            $source->delete();
        });

        return $target;
    }
}
