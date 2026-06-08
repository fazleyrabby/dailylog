<?php

namespace Tests\Feature\Domains\Linking;

use App\Domains\Linking\Queries\BacklinksQuery;
use App\Domains\Linking\Queries\OutboundLinksQuery;
use App\Domains\Linking\Services\LinkService;
use App\Models\Entry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BacklinksQueryTest extends TestCase
{
    use RefreshDatabase;

    public function test_backlinks_returns_sources_pointing_at_entry(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $hub = Entry::factory()->for($user)->create(['title' => 'Hub']);
        $a = Entry::factory()->for($user)->create(['title' => 'A', 'body' => '[[Hub]]']);
        $b = Entry::factory()->for($user)->create(['title' => 'B', 'body' => 'no link']);

        app(LinkService::class)->resolveBody($a);
        app(LinkService::class)->resolveBody($b);

        $bls = app(BacklinksQuery::class)->run($hub);

        $this->assertCount(1, $bls);
        $this->assertSame($a->id, $bls->first()->id);
    }

    public function test_outbound_returns_targets_pointed_at_by_entry(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $a = Entry::factory()->for($user)->create(['title' => 'A']);
        $b = Entry::factory()->for($user)->create(['title' => 'B']);
        $source = Entry::factory()->for($user)->create(['title' => 'S', 'body' => '[[A]] [[B]]']);

        app(LinkService::class)->resolveBody($source);

        $out = app(OutboundLinksQuery::class)->run($source);

        $this->assertCount(2, $out);
    }
}
