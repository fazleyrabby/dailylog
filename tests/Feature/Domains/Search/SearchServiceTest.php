<?php

namespace Tests\Feature\Domains\Search;

use App\Domains\Search\DTOs\SearchFilter;
use App\Domains\Search\Services\SearchService;
use App\Enums\EntryType;
use App\Models\Entry;
use App\Models\Project;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_fts_matches_title_and_body(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Entry::factory()->for($user)->type(EntryType::Note)->create([
            'title' => 'Postgres FTS notes',
            'body' => 'GIN indexes are the key',
        ]);
        Entry::factory()->for($user)->type(EntryType::Note)->create([
            'title' => 'Unrelated note',
            'body' => 'about Redis streams',
        ]);
        Entry::factory()->for($user)->type(EntryType::Note)->create([
            'title' => 'Another postgres note',
            'body' => 'maintenance with VACUUM',
        ]);
        Entry::factory()->for($user)->type(EntryType::Note)->create([
            'title' => 'Postgres tuning',
            'body' => 'pgbouncer connection pooling',
        ]);

        $results = app(SearchService::class)->search(new SearchFilter(q: 'postgres'));

        $this->assertGreaterThanOrEqual(3, $results->total());
        $this->assertContains('Postgres FTS notes', collect($results->items())->pluck('title')->all());
    }

    public function test_type_filter(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Entry::factory()->for($user)->type(EntryType::Task)->create(['title' => 'review pg migration', 'body' => 'check indexes']);
        Entry::factory()->for($user)->type(EntryType::Note)->create(['title' => 'pg notes', 'body' => 'GIN trgm']);
        Entry::factory()->for($user)->type(EntryType::Note)->create(['title' => 'pg study guide', 'body' => 'tuning']);

        $results = app(SearchService::class)->search(new SearchFilter(q: 'pg', types: ['task']));

        $this->assertSame(1, $results->total());
        $this->assertSame('task', $results->items()[0]->type);
    }

    public function test_archived_excluded_by_default(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Entry::factory()->for($user)->type(EntryType::Note)->create(['title' => 'visible postgres tuning', 'body' => 'present']);
        Entry::factory()->for($user)->type(EntryType::Note)->archived()->create(['title' => 'hidden postgres', 'body' => 'archived']);

        $results = app(SearchService::class)->search(new SearchFilter(q: 'postgres'));
        $titles = collect($results->items())->pluck('title')->all();

        $this->assertContains('visible postgres tuning', $titles);
        $this->assertNotContains('hidden postgres', $titles);
    }

    public function test_tag_filter(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $tag = Tag::factory()->for($user)->create(['name' => 'redis', 'slug' => 'redis']);

        $a = Entry::factory()->for($user)->create(['title' => 'redis streams research', 'body' => 'consumer groups']);
        $b = Entry::factory()->for($user)->create(['title' => 'redis cluster', 'body' => 'sharding']);
        $a->tags()->attach($tag->id);

        $results = app(SearchService::class)->search(new SearchFilter(q: 'redis', tagSlugs: ['redis']));

        $this->assertSame(1, $results->total());
    }

    public function test_empty_query_returns_recent(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        Entry::factory()->for($user)->count(3)->create();

        $results = app(SearchService::class)->search(new SearchFilter(q: ''));

        $this->assertSame(3, $results->total());
    }

    public function test_trigram_fallback_for_typo(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        Entry::factory()->for($user)->create(['title' => 'PostgreSQL connection pooling', 'body' => 'pgbouncer']);

        $results = app(SearchService::class)->search(new SearchFilter(q: 'postgr'));

        $this->assertGreaterThanOrEqual(1, $results->total());
    }
}
