<?php

namespace App\Domains\Entries\Services;

use Illuminate\Support\Str;

/**
 * Wraps Laravel's CommonMark integration. Output is escaped by default
 * (no raw HTML) so user-supplied note bodies cannot inject markup (docs/15 §4).
 */
class MarkdownRenderer
{
    public function render(?string $body): string
    {
        if ($body === null || $body === '') {
            return '';
        }

        return Str::markdown($body, [
            'html_input' => 'escape',
            'allow_unsafe_links' => false,
        ]);
    }
}
