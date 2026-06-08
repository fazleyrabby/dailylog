<?php

namespace Tests\Feature\Domains\Linking;

use App\Domains\Linking\Services\LinkService;
use App\Models\Entry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LinkServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_resolves_wiki_links_in_body_to_entry_links(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $target = Entry::factory()->for($user)->create(['title' => 'Postgres FTS notes']);
        $source = Entry::factory()->for($user)->create([
            'title' => 'Research',
            'body' => 'See [[Postgres FTS notes]] for the indexes.',
        ]);

        $ids = app(LinkService::class)->resolveBody($source);

        $this->assertSame([$target->id], $ids);
        $this->assertDatabaseHas('entry_links', ['source_id' => $source->id, 'target_id' => $target->id]);
    }

    public function test_unknown_titles_ignored(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $source = Entry::factory()->for($user)->create(['body' => 'See [[Missing Note]].']);

        $ids = app(LinkService::class)->resolveBody($source);

        $this->assertSame([], $ids);
        $this->assertSame(0, DB::table('entry_links')->where('source_id', $source->id)->count());
    }

    public function test_stale_links_pruned_on_re_resolution(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $a = Entry::factory()->for($user)->create(['title' => 'A']);
        $b = Entry::factory()->for($user)->create(['title' => 'B']);
        $source = Entry::factory()->for($user)->create(['title' => 'S', 'body' => '[[A]] [[B]]']);

        app(LinkService::class)->resolveBody($source);
        $this->assertSame(2, DB::table('entry_links')->where('source_id', $source->id)->count());

        $source->body = 'only [[A]] now';
        $source->save();
        app(LinkService::class)->resolveBody($source);

        $this->assertSame(1, DB::table('entry_links')->where('source_id', $source->id)->count());
        $this->assertDatabaseMissing('entry_links', ['source_id' => $source->id, 'target_id' => $b->id]);
    }

    public function test_self_link_skipped(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $entry = Entry::factory()->for($user)->create(['title' => 'Loop', 'body' => '[[Loop]]']);

        $ids = app(LinkService::class)->resolveBody($entry);

        $this->assertSame([], $ids);
    }
}
