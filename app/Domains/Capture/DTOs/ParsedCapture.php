<?php

namespace App\Domains\Capture\DTOs;

use App\Enums\EntryType;
use Carbon\CarbonImmutable;

final readonly class ParsedCapture
{
    /**
     * @param  list<string>  $tags
     */
    public function __construct(
        public EntryType $type,
        public ?string $title,
        public ?string $body,
        public array $tags,
        public ?string $projectSlug,
        public ?int $priority,
        public ?CarbonImmutable $dueAt,
        public ?string $url,
    ) {
    }
}
