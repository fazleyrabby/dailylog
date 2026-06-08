<?php

namespace App\Domains\Capture\Parsers;

use App\Domains\Capture\DTOs\ParsedCapture;
use App\Enums\EntryType;
use Illuminate\Support\Str;

/**
 * Implements the capture grammar from docs/09 §3.
 *
 * Verb-first (task|note|journal|bookmark|quote|idea|learning|resource);
 * inline tokens #tag, @project, !priority, due:<date>; bare URL -> bookmark.
 * Always succeeds: ambiguous input falls back to type=note.
 */
class CaptureGrammarParser
{
    private const VERBS = [
        'task' => EntryType::Task,
        'note' => EntryType::Note,
        'journal' => EntryType::Journal,
        'bookmark' => EntryType::Bookmark,
        'quote' => EntryType::Quote,
        'idea' => EntryType::Idea,
        'learning' => EntryType::Learning,
        'resource' => EntryType::Resource,
    ];

    private const PRIORITY_WORDS = ['low' => 1, 'med' => 2, 'medium' => 2, 'high' => 3];

    public function __construct(private readonly NaturalDateParser $dates)
    {
    }

    public function parse(string $raw): ParsedCapture
    {
        $raw = trim($raw);

        [$type, $remainder] = $this->extractVerb($raw);

        $url = $this->extractUrl($remainder);
        if ($url !== null && $type === null) {
            $type = EntryType::Bookmark;
        }

        $tags = [];
        $remainder = preg_replace_callback('/(?<![\w\/])#([\p{L}\p{N}_-]+)/u', function ($m) use (&$tags) {
            $tags[] = Str::lower($m[1]);
            return '';
        }, $remainder) ?? $remainder;

        $projectSlug = null;
        $remainder = preg_replace_callback('/(?<![\w\/])@([\p{L}\p{N}_-]+)/u', function ($m) use (&$projectSlug) {
            $projectSlug ??= Str::slug($m[1]);
            return '';
        }, $remainder) ?? $remainder;

        $priority = null;
        $remainder = preg_replace_callback('/!([1-3]|low|medium|med|high)\b/i', function ($m) use (&$priority) {
            $token = Str::lower($m[1]);
            $priority ??= ctype_digit($token) ? (int) $token : self::PRIORITY_WORDS[$token];
            return '';
        }, $remainder) ?? $remainder;

        $dueAt = null;
        $remainder = preg_replace_callback('/\bdue:(\S+)/i', function ($m) use (&$dueAt) {
            $dueAt ??= $this->dates->parse($m[1]);
            return '';
        }, $remainder) ?? $remainder;

        if ($url !== null) {
            $remainder = str_replace($url, '', $remainder);
        }

        $title = trim(preg_replace('/\s+/', ' ', $remainder) ?? '');
        $title = $title === '' ? null : $title;

        $type ??= EntryType::Note;

        return new ParsedCapture(
            type: $type,
            title: $title ?? $url,
            body: null,
            tags: array_values(array_unique($tags)),
            projectSlug: $projectSlug,
            priority: $priority,
            dueAt: $dueAt,
            url: $url,
        );
    }

    /** @return array{0: EntryType|null, 1: string} */
    private function extractVerb(string $raw): array
    {
        if (! preg_match('/^(\w+)\s+(.*)$/s', $raw, $m)) {
            $word = strtolower($raw);
            if (isset(self::VERBS[$word])) {
                return [self::VERBS[$word], ''];
            }
            return [null, $raw];
        }

        $word = strtolower($m[1]);
        if (isset(self::VERBS[$word])) {
            return [self::VERBS[$word], $m[2]];
        }

        return [null, $raw];
    }

    private function extractUrl(string $text): ?string
    {
        if (preg_match('#https?://\S+#i', $text, $m)) {
            return rtrim($m[0], '.,;)');
        }

        return null;
    }
}
