<?php

namespace App\Domains\Search\DTOs;

use Carbon\CarbonImmutable;

final readonly class SearchResult
{
    public function __construct(
        public int $id,
        public string $type,
        public ?string $title,
        public ?string $snippet,
        public string $url,
        public float $rank,
        public ?CarbonImmutable $lastActivityAt,
    ) {
    }

    public static function fromRow(object $row): self
    {
        $typeString = $row->type instanceof \App\Enums\EntryType ? $row->type->value : (string) $row->type;

        return new self(
            id: (int) $row->id,
            type: $typeString,
            title: $row->title ?? null,
            snippet: $row->snippet ?? null,
            url: self::detailUrl($typeString, (int) $row->id),
            rank: (float) ($row->rank ?? 0),
            lastActivityAt: isset($row->last_activity_at) ? CarbonImmutable::parse($row->last_activity_at) : null,
        );
    }

    public static function detailUrl(string $type, int $id): string
    {
        return match ($type) {
            'task' => "/tasks/{$id}",
            'note' => "/notes/{$id}",
            'journal' => "/journal/{$id}",
            'bookmark' => "/bookmarks/{$id}",
            'quote' => "/quotes/{$id}",
            'resource' => "/resources/{$id}",
            'learning' => "/learning/{$id}",
            'idea' => "/ideas/{$id}",
            default => "/e/{$id}",
        };
    }
}
