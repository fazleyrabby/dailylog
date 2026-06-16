<?php

use App\Domains\Search\DTOs\SearchFilter;
use App\Domains\Search\Services\SearchService;
use App\Enums\EntryType;
use App\Models\Entry;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('fts matches title and body', function () {
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

    expect($results->total())->toBeGreaterThanOrEqual(3);
    expect(collect($results->items())->pluck('title')->all())->toContain('Postgres FTS notes');
});

test('type filter', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Entry::factory()->for($user)->type(EntryType::Task)->create(['title' => 'review pg migration', 'body' => 'check indexes']);
    Entry::factory()->for($user)->type(EntryType::Note)->create(['title' => 'pg notes', 'body' => 'GIN trgm']);
    Entry::factory()->for($user)->type(EntryType::Note)->create(['title' => 'pg study guide', 'body' => 'tuning']);

    $results = app(SearchService::class)->search(new SearchFilter(q: 'pg', types: ['task']));

    expect($results->total())->toBe(1);
    expect($results->items()[0]->type)->toBe('task');
});

test('archived excluded by default', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Entry::factory()->for($user)->type(EntryType::Note)->create(['title' => 'visible postgres tuning', 'body' => 'present']);
    Entry::factory()->for($user)->type(EntryType::Note)->archived()->create(['title' => 'hidden postgres', 'body' => 'archived']);

    $results = app(SearchService::class)->search(new SearchFilter(q: 'postgres'));
    $titles = collect($results->items())->pluck('title')->all();

    expect($titles)->toContain('visible postgres tuning');
    expect($titles)->not->toContain('hidden postgres');
});

test('tag filter', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $tag = Tag::factory()->for($user)->create(['name' => 'redis', 'slug' => 'redis']);

    $a = Entry::factory()->for($user)->create(['title' => 'redis streams research', 'body' => 'consumer groups']);
    $b = Entry::factory()->for($user)->create(['title' => 'redis cluster', 'body' => 'sharding']);
    $a->tags()->attach($tag->id);

    $results = app(SearchService::class)->search(new SearchFilter(q: 'redis', tagSlugs: ['redis']));

    expect($results->total())->toBe(1);
});

test('empty query returns recent', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    Entry::factory()->for($user)->count(3)->create();

    $results = app(SearchService::class)->search(new SearchFilter(q: ''));

    expect($results->total())->toBe(3);
});

test('trigram fallback for typo', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    Entry::factory()->for($user)->create(['title' => 'PostgreSQL connection pooling', 'body' => 'pgbouncer']);

    $results = app(SearchService::class)->search(new SearchFilter(q: 'postgr'));

    expect($results->total())->toBeGreaterThanOrEqual(1);
});
