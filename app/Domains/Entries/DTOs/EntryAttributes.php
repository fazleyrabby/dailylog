<?php

namespace App\Domains\Entries\DTOs;

use App\Enums\BodyFormat;
use App\Enums\CapturedVia;
use App\Enums\EntryType;
use Carbon\CarbonImmutable;

final readonly class EntryAttributes
{
    public function __construct(
        public EntryType $type,
        public ?string $title = null,
        public ?string $body = null,
        public BodyFormat $bodyFormat = BodyFormat::Markdown,
        public ?string $status = null,
        public ?int $projectId = null,
        public bool $pinned = false,
        public ?CapturedVia $capturedVia = null,
        public ?CarbonImmutable $occurredOn = null,
    ) {
    }

    public function toModelAttributes(): array
    {
        return array_filter([
            'type' => $this->type->value,
            'title' => $this->title,
            'body' => $this->body,
            'body_format' => $this->bodyFormat->value,
            'status' => $this->status ?? $this->type->defaultStatus(),
            'project_id' => $this->projectId,
            'pinned' => $this->pinned,
            'captured_via' => $this->capturedVia?->value,
            'occurred_on' => $this->occurredOn?->toDateString(),
        ], fn ($v) => $v !== null);
    }
}
