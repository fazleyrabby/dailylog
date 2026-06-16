<?php

use App\Domains\Capture\Parsers\CaptureGrammarParser;
use App\Domains\Capture\Parsers\NaturalDateParser;
use App\Enums\EntryType;

beforeEach(function () {
    $this->parser = new CaptureGrammarParser(new NaturalDateParser);
});

test('full task line', function () {
    $p = $this->parser->parse('task review auth PR due:tomorrow !high #security #auth @sideproject');

    expect($p->type)->toBe(EntryType::Task);
    expect($p->title)->toBe('review auth PR');
    expect($p->tags)->toBe(['security', 'auth']);
    expect($p->projectSlug)->toBe('sideproject');
    expect($p->priority)->toBe(3);
    expect($p->dueAt)->not->toBeNull();
});

test('bare url becomes bookmark', function () {
    $p = $this->parser->parse('https://laravel.com/docs');

    expect($p->type)->toBe(EntryType::Bookmark);
    expect($p->url)->toBe('https://laravel.com/docs');
});

test('url with tags', function () {
    $p = $this->parser->parse('https://redis.io/streams #redis @research');

    expect($p->type)->toBe(EntryType::Bookmark);
    expect($p->url)->toBe('https://redis.io/streams');
    expect($p->tags)->toBe(['redis']);
    expect($p->projectSlug)->toBe('research');
});

test('no verb defaults to note', function () {
    $p = $this->parser->parse('quick thought about indexes');

    expect($p->type)->toBe(EntryType::Note);
    expect($p->title)->toBe('quick thought about indexes');
});

test('priority numeric and word', function () {
    expect($this->parser->parse('task x !2')->priority)->toBe(2);
    expect($this->parser->parse('task x !low')->priority)->toBe(1);
    expect($this->parser->parse('task x !high')->priority)->toBe(3);
});

test('tag dedup', function () {
    $p = $this->parser->parse('task x #db #db #DB');
    expect($p->tags)->toBe(['db']);
});

test('idea verb', function () {
    $p = $this->parser->parse('idea personal LLM index');
    expect($p->type)->toBe(EntryType::Idea);
    expect($p->title)->toBe('personal LLM index');
});
