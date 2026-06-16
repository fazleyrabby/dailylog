<?php

use App\Domains\Capture\Parsers\NaturalDateParser;
use Carbon\CarbonImmutable;

beforeEach(function () {
    $this->parser = new NaturalDateParser;

    // Wednesday 2026-06-10
    $this->now = CarbonImmutable::create(2026, 6, 10, 9, 0, 0);
});

test('today tomorrow yesterday', function () {
    expect($this->parser->parse('today', $this->now)->toDateString())->toBe('2026-06-10');
    expect($this->parser->parse('tomorrow', $this->now)->toDateString())->toBe('2026-06-11');
    expect($this->parser->parse('yesterday', $this->now)->toDateString())->toBe('2026-06-09');
});

test('weekdays advance to next', function () {
    expect($this->parser->parse('fri', $this->now)->toDateString())->toBe('2026-06-12');
    expect($this->parser->parse('monday', $this->now)->toDateString())->toBe('2026-06-15');
});

test('in n days and weeks', function () {
    expect($this->parser->parse('in-3-days', $this->now)->toDateString())->toBe('2026-06-13');
    expect($this->parser->parse('in-2-weeks', $this->now)->toDateString())->toBe('2026-06-24');
});

test('iso and md dates', function () {
    expect($this->parser->parse('2026-07-01', $this->now)->toDateString())->toBe('2026-07-01');
    expect($this->parser->parse('12-25', $this->now)->toDateString())->toBe('2026-12-25');
});

test('unknown returns null', function () {
    expect($this->parser->parse('whenever', $this->now))->toBeNull();
});
