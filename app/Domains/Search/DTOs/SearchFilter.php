<?php

namespace App\Domains\Search\DTOs;

use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

final readonly class SearchFilter
{
    /**
     * @param  list<string>  $types
     * @param  list<string>  $tagSlugs
     */
    public function __construct(
        public string $q = '',
        public array $types = [],
        public array $tagSlugs = [],
        public ?string $projectSlug = null,
        public ?string $status = null,
        public ?CarbonImmutable $from = null,
        public ?CarbonImmutable $to = null,
        public bool $includeArchived = false,
        public int $perPage = 25,
    ) {
    }

    public static function fromRequest(Request $request): self
    {
        return new self(
            q: trim((string) $request->query('q', '')),
            types: array_values(array_filter((array) $request->query('type', []))),
            tagSlugs: array_values(array_filter((array) $request->query('tag', []))),
            projectSlug: $request->query('project') ?: null,
            status: $request->query('status') ?: null,
            from: self::parseDate($request->query('from')),
            to: self::parseDate($request->query('to')),
            includeArchived: filter_var($request->query('archived'), FILTER_VALIDATE_BOOLEAN),
            perPage: min(max((int) $request->query('per', 25), 5), 100),
        );
    }

    public function isEmpty(): bool
    {
        return $this->q === ''
            && $this->types === []
            && $this->tagSlugs === []
            && $this->projectSlug === null
            && $this->status === null
            && $this->from === null
            && $this->to === null;
    }

    private static function parseDate(mixed $raw): ?CarbonImmutable
    {
        if (! is_string($raw) || $raw === '') {
            return null;
        }
        try {
            return CarbonImmutable::parse($raw);
        } catch (\Throwable) {
            return null;
        }
    }
}
