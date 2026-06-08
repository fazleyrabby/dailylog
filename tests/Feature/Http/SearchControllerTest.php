<?php

namespace Tests\Feature\Http;

use App\Enums\EntryType;
use App\Models\Entry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_empty_search_renders_landing(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('search.index'))
            ->assertOk()
            ->assertSee('Search across everything');
    }

    public function test_search_with_q_returns_matches(): void
    {
        $user = User::factory()->create();
        Entry::factory()->for($user)->type(EntryType::Note)->create([
            'title' => 'Tailwind v4 release notes',
            'body' => 'Lots of perf wins',
        ]);

        $this->actingAs($user)
            ->get(route('search.index', ['q' => 'tailwind']))
            ->assertOk()
            ->assertSee('Tailwind v4 release notes');
    }
}
