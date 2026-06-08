<?php

namespace App\Domains\Capture\DTOs;

class CapturedEntryData
{
    public function __construct(
        public string $type,
        public ?string $title = null,
        public ?string $body = null,
        public array $tags = [],
        public ?string $projectSlug = null,
        public ?string $priority = null,
        public ?string $dueString = null,
        public array $meta = []
    ) {}
}
