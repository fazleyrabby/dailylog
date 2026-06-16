<?php

use App\Domains\Tags\Actions\AttachTags;
use App\Domains\Tags\Actions\CreateTag;
use App\Domains\Tags\Actions\DetachTag;
use App\Domains\Tags\Actions\MergeTags;
use App\Domains\Tags\Actions\RenameTag;
use App\Models\Entry;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('create tag is idempotent by name', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $a = app(CreateTag::class)->execute('Postgres');
    $b = app(CreateTag::class)->execute('Postgres');

    expect($b->id)->toBe($a->id);
    expect(Tag::query()->count())->toBe(1);
});

test('rename tag updates slug', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $tag = Tag::factory()->for($user)->create(['name' => 'redis', 'slug' => 'redis']);

    app(RenameTag::class)->execute($tag, 'Redis Cluster');

    expect($tag->fresh()->name)->toBe('Redis Cluster');
    expect($tag->fresh()->slug)->toBe('redis-cluster');
});

test('merge moves pivots and deletes source', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $source = Tag::factory()->for($user)->create(['name' => 'pg', 'slug' => 'pg']);
    $target = Tag::factory()->for($user)->create(['name' => 'postgres', 'slug' => 'postgres']);

    $e1 = Entry::factory()->for($user)->create();
    $e2 = Entry::factory()->for($user)->create();
    $e1->tags()->attach($source->id);
    $e2->tags()->attach([$source->id, $target->id]);

    app(MergeTags::class)->execute($source, $target);

    expect(Tag::query()->find($source->id))->toBeNull();
    expect($target->fresh()->entries()->count())->toBe(2);
});

test('attach creates missing tags and pivots', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $entry = Entry::factory()->for($user)->create();

    app(AttachTags::class)->execute($entry, ['Docker', 'security', 'security']);

    $names = $entry->tags()->pluck('name')->sort()->values()->all();
    expect($names)->toBe(['Docker', 'security']);
});

test('detach removes pivot', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $entry = Entry::factory()->for($user)->create();
    $tag = Tag::factory()->for($user)->create();
    $entry->tags()->attach($tag->id);

    app(DetachTag::class)->execute($entry, $tag);

    expect($entry->tags()->count())->toBe(0);
});
