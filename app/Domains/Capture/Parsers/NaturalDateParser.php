<?php

namespace App\Domains\Capture\Parsers;

use Carbon\CarbonImmutable;
use Throwable;

/**
 * Resolves natural-date tokens from docs/09 §3 into CarbonImmutable.
 * Returns null for unrecognized input (capture never blocks on parsing).
 */
class NaturalDateParser
{
    private const WEEKDAYS = [
        'mon' => 1, 'tue' => 2, 'wed' => 3, 'thu' => 4,
        'fri' => 5, 'sat' => 6, 'sun' => 0,
    ];

    public function parse(string $token, ?CarbonImmutable $now = null): ?CarbonImmutable
    {
        $now ??= CarbonImmutable::now();
        $token = strtolower(trim($token));

        if ($token === '') {
            return null;
        }

        if ($token === 'today') {
            return $now->startOfDay();
        }

        if ($token === 'tomorrow') {
            return $now->addDay()->startOfDay();
        }

        if ($token === 'yesterday') {
            return $now->subDay()->startOfDay();
        }

        if ($token === 'next-week' || $token === 'next_week') {
            return $now->addWeek()->startOfDay();
        }

        foreach (self::WEEKDAYS as $abbr => $isoDow) {
            if (str_starts_with($token, $abbr)) {
                $target = $now->next($isoDow);
                return $target->startOfDay();
            }
        }

        if (preg_match('/^in[-_](\d+)[-_]?(day|days|d|week|weeks|w)?$/', $token, $m)) {
            $n = (int) $m[1];
            $unit = $m[2] ?? 'd';
            return str_starts_with($unit, 'w')
                ? $now->addWeeks($n)->startOfDay()
                : $now->addDays($n)->startOfDay();
        }

        try {
            return CarbonImmutable::createFromFormat('Y-m-d', $token)->startOfDay();
        } catch (Throwable) {
            // fall through
        }

        try {
            return CarbonImmutable::createFromFormat('m-d', $token)
                ->setYear((int) $now->format('Y'))
                ->startOfDay();
        } catch (Throwable) {
            return null;
        }
    }
}
