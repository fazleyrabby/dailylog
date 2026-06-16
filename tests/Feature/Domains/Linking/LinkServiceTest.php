<?php

use App\Domains\Linking\Services\LinkService;
use App\Models\Entry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

test('resolves wiki links in body to entry links', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $target = Entry::factory()->for($user)->create(['title' => 'Postgres FTS notes']);
    $source = Entry::factory()->for($user)->create([
        'title' => 'Research',
        'body' => 'See [[Postgres FTS notes]] for the indexes.',
    ]);

    $ids = app(LinkService::class)->resolveBody($source);

    expect($ids)->toBe([$target->id]);
    $this->assertDatabaseHas('entry_links', ['source_id' => $source->id, 'target_id' => $target->id]);
});

test('unknown titles ignored', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $source = Entry::factory()->for($user)->create(['body' => 'See [[Missing Note]].']);

    $ids = app(LinkService::class)->resolveBody($source);

    expect($ids)->toBe([]);
    expect(DB::table('entry_links')->where('source_id', $source->id)->count())->toBe(0);
});

test('stale links pruned on re resolution', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $a = Entry::factory()->for($user)->create(['title' => 'A']);
    $b = Entry::factory()->for($user)->create(['title' => 'B']);
    $source = Entry::factory()->for($user)->create(['title' => 'S', 'body' => '[[A]] [[B]]']);

    app(LinkService::class)->resolveBody($source);
    expect(DB::table('entry_links')->where('source_id', $source->id)->count())->toBe(2);

    $source->body = 'only [[A]] now';
    $source->save();
    app(LinkService::class)->resolveBody($source);

    expect(DB::table('entry_links')->where('source_id', $source->id)->count())->toBe(1);
    $this->assertDatabaseMissing('entry_links', ['source_id' => $source->id, 'target_id' => $b->id]);
});

test('self link skipped', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $entry = Entry::factory()->for($user)->create(['title' => 'Loop', 'body' => '[[Loop]]']);

    $ids = app(LinkService::class)->resolveBody($entry);

    expect($ids)->toBe([]);
});
