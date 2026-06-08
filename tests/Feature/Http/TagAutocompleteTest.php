<?php

namespace Tests\Feature\Http;

use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TagAutocompleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_prefix_match_returns_owner_tags_only(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        Tag::factory()->for($owner)->create(['name' => 'postgres', 'slug' => 'postgres']);
        Tag::factory()->for($owner)->create(['name' => 'postman', 'slug' => 'postman']);
        Tag::factory()->for($owner)->create(['name' => 'redis', 'slug' => 'redis']);
        Tag::factory()->for($stranger)->create(['name' => 'postcss', 'slug' => 'postcss']);

        $this->actingAs($owner)
            ->getJson(route('partials.tags.autocomplete', ['q' => 'post']))
            ->assertOk()
            ->assertJsonCount(2, 'tags');
    }

    public function test_empty_query_returns_first_tags(): void
    {
        $owner = User::factory()->create();
        Tag::factory()->for($owner)->count(3)->create();

        $this->actingAs($owner)
            ->getJson(route('partials.tags.autocomplete'))
            ->assertOk()
            ->assertJsonCount(3, 'tags');
    }
}
