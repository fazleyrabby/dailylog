<?php

namespace App\Domains\Search\Services;

use App\Domains\Search\DTOs\SearchFilter;
use App\Domains\Search\DTOs\SearchResult;
use App\Models\Entry;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Postgres FTS on entries.search_vector (docs/08). Trigram fallback on title
 * when FTS yields few hits. Owner scoping is provided by the Entry global scope.
 */
class SearchService
{
    private const TRIGRAM_FALLBACK_THRESHOLD = 3;

    /** @return LengthAwarePaginator<SearchResult> */
    public function search(SearchFilter $filter, int $page = 1): LengthAwarePaginator
    {
        $hasQuery = $filter->q !== '';

        $base = Entry::query();
        $this->applyCommonFilters($base, $filter);

        if (! $hasQuery) {
            return $this->paginate($this->selectColumns($base, false)->orderByDesc('entries.last_activity_at'), $filter->perPage, $page);
        }

        $ftsQuery = (clone $base)
            ->whereRaw('search_vector @@ websearch_to_tsquery(\'english\', ?)', [$filter->q]);

        $ftsCount = (clone $ftsQuery)->count();

        if ($ftsCount >= self::TRIGRAM_FALLBACK_THRESHOLD) {
            return $this->paginate(
                $this->selectColumns($ftsQuery, true, $filter->q)
                    ->orderByRaw('ts_rank_cd(search_vector, websearch_to_tsquery(\'english\', ?)) DESC, entries.last_activity_at DESC', [$filter->q]),
                $filter->perPage,
                $page,
            );
        }

        // Trigram fallback augments FTS hits with similarity-ranked title matches.
        return $this->trigramFallback($filter, $page);
    }

    private function applyCommonFilters(Builder $query, SearchFilter $filter): void
    {
        if (! $filter->includeArchived) {
            $query->whereNull('entries.archived_at');
        }

        if ($filter->types !== []) {
            $query->whereIn('entries.type', $filter->types);
        }

        if ($filter->status !== null) {
            $query->where('entries.status', $filter->status);
        }

        if ($filter->projectSlug !== null) {
            $query->whereHas('project', fn ($q) => $q->where('slug', $filter->projectSlug));
        }

        if ($filter->tagSlugs !== []) {
            $query->whereHas('tags', fn ($q) => $q->whereIn('slug', $filter->tagSlugs));
        }

        if ($filter->from !== null) {
            $query->where('entries.last_activity_at', '>=', $filter->from);
        }

        if ($filter->to !== null) {
            $query->where('entries.last_activity_at', '<=', $filter->to);
        }
    }

    private function selectColumns(Builder $query, bool $withSnippet, ?string $rawQuery = null): Builder
    {
        $columns = ['entries.id', 'entries.type', 'entries.title', 'entries.last_activity_at'];

        if ($withSnippet && $rawQuery !== null) {
            $query->addSelect(DB::raw("ts_rank_cd(search_vector, websearch_to_tsquery('english', " . DB::connection()->getPdo()->quote($rawQuery) . ")) AS rank"));
            $query->addSelect(DB::raw("ts_headline('english', coalesce(body, ''), websearch_to_tsquery('english', " . DB::connection()->getPdo()->quote($rawQuery) . "), 'MaxFragments=2, MinWords=5, StartSel=<mark>, StopSel=</mark>') AS snippet"));
        } else {
            $query->addSelect(DB::raw('0::float AS rank'));
            $query->addSelect(DB::raw('NULL::text AS snippet'));
        }

        return $query->select($columns);
    }

    /** @return LengthAwarePaginator<SearchResult> */
    private function trigramFallback(SearchFilter $filter, int $page): LengthAwarePaginator
    {
        $base = Entry::query();
        $this->applyCommonFilters($base, $filter);

        $query = $base
            ->select(['entries.id', 'entries.type', 'entries.title', 'entries.last_activity_at'])
            ->selectRaw('NULL::text AS snippet')
            ->selectRaw('GREATEST(similarity(coalesce(title,\'\'), ?), 0) AS rank', [$filter->q])
            ->whereRaw('(coalesce(title,\'\') ILIKE ? OR similarity(coalesce(title,\'\'), ?) > 0.2)', ['%' . $filter->q . '%', $filter->q])
            ->orderByDesc('rank')
            ->orderByDesc('entries.last_activity_at');

        return $this->paginate($query, $filter->perPage, $page);
    }

    /** @return LengthAwarePaginator<SearchResult> */
    private function paginate(Builder $query, int $perPage, int $page): LengthAwarePaginator
    {
        $total = (clone $query)->getQuery()->getCountForPagination();
        $rows = $query->forPage($page, $perPage)->get();

        $items = $rows->map(fn ($row) => SearchResult::fromRow($row))->all();

        return new Paginator($items, $total, $perPage, $page, [
            'path' => Paginator::resolveCurrentPath(),
            'pageName' => 'page',
        ]);
    }

    /** Top N grouped by type for the Cmd-K palette. */
    public function suggest(string $q, int $perType = 5): Collection
    {
        $base = Entry::query()
            ->whereNull('entries.archived_at');

        if ($q !== '') {
            $base->whereRaw('search_vector @@ websearch_to_tsquery(\'english\', ?)', [$q]);
        }

        $rows = $base
            ->select(['entries.id', 'entries.type', 'entries.title', 'entries.last_activity_at'])
            ->orderByDesc('entries.last_activity_at')
            ->limit($perType * 10)
            ->get();

        return $rows->groupBy(fn ($r) => $r->type instanceof \App\Enums\EntryType ? $r->type->value : (string) $r->type)
            ->map(fn ($g) => $g->take($perType));
    }
}
