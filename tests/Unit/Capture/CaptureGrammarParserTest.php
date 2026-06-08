<?php

namespace Tests\Unit\Capture;

use App\Domains\Capture\Parsers\CaptureGrammarParser;
use App\Domains\Capture\Parsers\NaturalDateParser;
use App\Enums\EntryType;
use PHPUnit\Framework\TestCase;

class CaptureGrammarParserTest extends TestCase
{
    private CaptureGrammarParser $parser;

    protected function setUp(): void
    {
        $this->parser = new CaptureGrammarParser(new NaturalDateParser);
    }

    public function test_full_task_line(): void
    {
        $p = $this->parser->parse('task review auth PR due:tomorrow !high #security #auth @sideproject');

        $this->assertSame(EntryType::Task, $p->type);
        $this->assertSame('review auth PR', $p->title);
        $this->assertSame(['security', 'auth'], $p->tags);
        $this->assertSame('sideproject', $p->projectSlug);
        $this->assertSame(3, $p->priority);
        $this->assertNotNull($p->dueAt);
    }

    public function test_bare_url_becomes_bookmark(): void
    {
        $p = $this->parser->parse('https://laravel.com/docs');

        $this->assertSame(EntryType::Bookmark, $p->type);
        $this->assertSame('https://laravel.com/docs', $p->url);
    }

    public function test_url_with_tags(): void
    {
        $p = $this->parser->parse('https://redis.io/streams #redis @research');

        $this->assertSame(EntryType::Bookmark, $p->type);
        $this->assertSame('https://redis.io/streams', $p->url);
        $this->assertSame(['redis'], $p->tags);
        $this->assertSame('research', $p->projectSlug);
    }

    public function test_no_verb_defaults_to_note(): void
    {
        $p = $this->parser->parse('quick thought about indexes');

        $this->assertSame(EntryType::Note, $p->type);
        $this->assertSame('quick thought about indexes', $p->title);
    }

    public function test_priority_numeric_and_word(): void
    {
        $this->assertSame(2, $this->parser->parse('task x !2')->priority);
        $this->assertSame(1, $this->parser->parse('task x !low')->priority);
        $this->assertSame(3, $this->parser->parse('task x !high')->priority);
    }

    public function test_tag_dedup(): void
    {
        $p = $this->parser->parse('task x #db #db #DB');
        $this->assertSame(['db'], $p->tags);
    }

    public function test_idea_verb(): void
    {
        $p = $this->parser->parse('idea personal LLM index');
        $this->assertSame(EntryType::Idea, $p->type);
        $this->assertSame('personal LLM index', $p->title);
    }
}
