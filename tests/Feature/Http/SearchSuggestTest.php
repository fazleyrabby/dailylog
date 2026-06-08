<?php

namespace Tests\Feature\Http;

use App\Enums\EntryType;
use App\Models\Entry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchSuggestTest extends TestCase
{
    use RefreshDatabase;

    public function test_empty_query_returns_recents_grouped_by_type(): void
    {
        $user = User::factory()->create();
        Entry::factory()->for($user)->type(EntryType::Note)->count(2)->create();
        Entry::factory()->for($user)->type(EntryType::Task)->count(2)->create();

        $resp = $this->actingAs($user)
            ->getJson(route('partials.search.suggest'))
            ->assertOk()
            ->json();

        $this->assertArrayHasKey('groups', $resp);
        $this->assertArrayHasKey('note', $resp['groups']);
        $this->assertArrayHasKey('task', $resp['groups']);
    }

    public function test_query_filters_results(): void
    {
        $user = User::factory()->create();
        Entry::factory()->for($user)->type(EntryType::Note)->create(['title' => 'Postgres tuning', 'body' => 'pg']);
        Entry::factory()->for($user)->type(EntryType::Note)->create(['title' => 'Vue notes', 'body' => 'spa']);

        $resp = $this->actingAs($user)
            ->getJson(route('partials.search.suggest', ['q' => 'postgres']))
            ->json();

        $allTitles = collect($resp['groups'])->flatten(1)->pluck('title');
        $this->assertTrue($allTitles->contains('Postgres tuning'));
        $this->assertFalse($allTitles->contains('Vue notes'));
    }
}
