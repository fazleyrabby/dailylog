<?php

use App\Domains\Linking\Queries\BacklinksQuery;
use App\Domains\Linking\Queries\OutboundLinksQuery;
use App\Domains\Linking\Services\LinkService;
use App\Models\Entry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('backlinks returns sources pointing at entry', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $hub = Entry::factory()->for($user)->create(['title' => 'Hub']);
    $a = Entry::factory()->for($user)->create(['title' => 'A', 'body' => '[[Hub]]']);
    $b = Entry::factory()->for($user)->create(['title' => 'B', 'body' => 'no link']);

    app(LinkService::class)->resolveBody($a);
    app(LinkService::class)->resolveBody($b);

    $bls = app(BacklinksQuery::class)->run($hub);

    expect($bls)->toHaveCount(1);
    expect($bls->first()->id)->toBe($a->id);
});

test('outbound returns targets pointed at by entry', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $a = Entry::factory()->for($user)->create(['title' => 'A']);
    $b = Entry::factory()->for($user)->create(['title' => 'B']);
    $source = Entry::factory()->for($user)->create(['title' => 'S', 'body' => '[[A]] [[B]]']);

    app(LinkService::class)->resolveBody($source);

    $out = app(OutboundLinksQuery::class)->run($source);

    expect($out)->toHaveCount(2);
});
