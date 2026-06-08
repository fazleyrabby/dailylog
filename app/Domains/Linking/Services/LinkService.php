<?php

namespace App\Domains\Linking\Services;

use App\Models\Entry;
use Illuminate\Support\Facades\DB;

/**
 * Resolves [[wiki-link]] tokens in an entry body to entry_links rows
 * (docs/06 §92). Self-links are rejected by a DB CHECK constraint;
 * we also skip them up-front. Stale links not present in the new body
 * are removed for the same source/relation, so editing a note prunes.
 */
class LinkService
{
    private const RELATION = 'references';
    private const TOKEN = '/\[\[\s*([^\[\]]+?)\s*\]\]/u';

    /** @return list<int>  ids of newly linked target entries */
    public function resolveBody(Entry $source): array
    {
        $titles = $this->extractTitles((string) $source->body);
        $targetIds = $this->resolveTitles($titles, $source->id);

        DB::transaction(function () use ($source, $targetIds) {
            DB::table('entry_links')
                ->where('source_id', $source->id)
                ->where('relation', self::RELATION)
                ->delete();

            foreach ($targetIds as $tid) {
                DB::table('entry_links')->insertOrIgnore([
                    'source_id' => $source->id,
                    'target_id' => $tid,
                    'relation' => self::RELATION,
                    'created_at' => now(),
                ]);
            }
        });

        return $targetIds;
    }

    /** @return list<string> */
    private function extractTitles(string $body): array
    {
        if ($body === '') {
            return [];
        }

        if (! preg_match_all(self::TOKEN, $body, $matches)) {
            return [];
        }

        return array_values(array_unique(array_map('trim', $matches[1])));
    }

    /**
     * @param  list<string>  $titles
     * @return list<int>
     */
    private function resolveTitles(array $titles, int $sourceId): array
    {
        if ($titles === []) {
            return [];
        }

        return Entry::query()
            ->whereIn(DB::raw('lower(title)'), array_map(strtolower(...), $titles))
            ->where('id', '!=', $sourceId)
            ->pluck('id')
            ->all();
    }
}
