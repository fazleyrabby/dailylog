<?php

namespace Tests\Unit\Capture;

use App\Domains\Capture\Parsers\NaturalDateParser;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

class NaturalDateParserTest extends TestCase
{
    private NaturalDateParser $parser;
    private CarbonImmutable $now;

    protected function setUp(): void
    {
        $this->parser = new NaturalDateParser;
        // Wednesday 2026-06-10
        $this->now = CarbonImmutable::create(2026, 6, 10, 9, 0, 0);
    }

    public function test_today_tomorrow_yesterday(): void
    {
        $this->assertSame('2026-06-10', $this->parser->parse('today', $this->now)->toDateString());
        $this->assertSame('2026-06-11', $this->parser->parse('tomorrow', $this->now)->toDateString());
        $this->assertSame('2026-06-09', $this->parser->parse('yesterday', $this->now)->toDateString());
    }

    public function test_weekdays_advance_to_next(): void
    {
        $this->assertSame('2026-06-12', $this->parser->parse('fri', $this->now)->toDateString());
        $this->assertSame('2026-06-15', $this->parser->parse('monday', $this->now)->toDateString());
    }

    public function test_in_n_days_and_weeks(): void
    {
        $this->assertSame('2026-06-13', $this->parser->parse('in-3-days', $this->now)->toDateString());
        $this->assertSame('2026-06-24', $this->parser->parse('in-2-weeks', $this->now)->toDateString());
    }

    public function test_iso_and_md_dates(): void
    {
        $this->assertSame('2026-07-01', $this->parser->parse('2026-07-01', $this->now)->toDateString());
        $this->assertSame('2026-12-25', $this->parser->parse('12-25', $this->now)->toDateString());
    }

    public function test_unknown_returns_null(): void
    {
        $this->assertNull($this->parser->parse('whenever', $this->now));
    }
}
