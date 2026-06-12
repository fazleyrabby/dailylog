<?php

namespace Tests\Feature\Http;

use App\Enums\EntryType;
use App\Models\Entry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResourcesControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_resources(): void
    {
        $this->get(route('resources.index'))
            ->assertRedirect(route('auth.login'));
    }

    public function test_index_shows_user_resources(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $resource = Entry::factory()->for($user)->type(EntryType::Resource)->create([
            'title' => 'My Book',
            'status' => 'to_consume',
        ]);
        $resource->resourceDetails()->create([
            'resource_type' => 'book',
            'author' => 'Author A',
            'url' => 'https://example.com/book',
            'consume_state' => 'to_consume',
            'rating' => 5,
        ]);

        $other = Entry::factory()->for($otherUser)->type(EntryType::Resource)->create([
            'title' => 'Other Book',
            'status' => 'to_consume',
        ]);
        $other->resourceDetails()->create([
            'resource_type' => 'book',
            'author' => 'Author B',
            'url' => 'https://example.com/other',
            'consume_state' => 'to_consume',
            'rating' => 4,
        ]);

        $this->actingAs($user)
            ->get(route('resources.index'))
            ->assertOk()
            ->assertViewHas('resources', function ($resources) {
                return count($resources) === 1 && $resources[0]['title'] === 'My Book';
            });
    }

    public function test_store_creates_resource_with_details_and_tags(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('resources.store'), [
                'title' => 'Laravel Up & Running',
                'resource_type' => 'book',
                'consume_state' => 'consuming',
                'author' => 'Matt Stauffer',
                'url' => 'https://laravelupandrunning.com',
                'rating' => 5,
                'tags' => ['laravel', 'php'],
            ])
            ->assertStatus(201)
            ->assertJsonPath('resource.title', 'Laravel Up & Running')
            ->assertJsonPath('resource.type', 'book')
            ->assertJsonPath('resource.state', 'consuming');

        $this->assertDatabaseHas('entries', [
            'user_id' => $user->id,
            'type' => 'resource',
            'title' => 'Laravel Up & Running',
            'status' => 'consuming',
        ]);

        $this->assertDatabaseHas('resource_details', [
            'resource_type' => 'book',
            'author' => 'Matt Stauffer',
            'url' => 'https://laravelupandrunning.com',
            'consume_state' => 'consuming',
            'rating' => 5,
        ]);

        $entry = Entry::where('user_id', $user->id)->first();
        $this->assertCount(2, $entry->tags);
    }

    public function test_update_modifies_resource_and_syncs_tags(): void
    {
        $user = User::factory()->create();
        $entry = Entry::factory()->for($user)->type(EntryType::Resource)->create([
            'title' => 'Old Title',
            'status' => 'to_consume',
        ]);
        $entry->resourceDetails()->create([
            'resource_type' => 'book',
            'consume_state' => 'to_consume',
        ]);
        
        $entry->tags()->create([
            'user_id' => $user->id,
            'name' => 'oldtag',
            'slug' => 'oldtag'
        ]);

        $this->actingAs($user)
            ->putJson(route('resources.update', $entry), [
                'title' => 'New Title',
                'resource_type' => 'video',
                'consume_state' => 'done',
                'author' => 'New Author',
                'url' => 'https://example.com/new',
                'rating' => 4,
                'tags' => ['newtag'],
            ])
            ->assertOk()
            ->assertJsonPath('resource.title', 'New Title')
            ->assertJsonPath('resource.type', 'video')
            ->assertJsonPath('resource.state', 'done');

        $this->assertEquals('New Title', $entry->fresh()->title);
        $this->assertEquals('done', $entry->fresh()->status);
        $this->assertEquals('video', $entry->resourceDetails->fresh()->resource_type);
        $this->assertEquals('New Author', $entry->resourceDetails->fresh()->author);
        $this->assertEquals('done', $entry->resourceDetails->fresh()->consume_state);
        $this->assertEquals(4, $entry->resourceDetails->fresh()->rating);
        $this->assertCount(1, $entry->fresh()->tags);
        $this->assertEquals('newtag', $entry->fresh()->tags->first()->name);
    }

    public function test_destroy_archives_resource(): void
    {
        $user = User::factory()->create();
        $entry = Entry::factory()->for($user)->type(EntryType::Resource)->create([
            'title' => 'Resource to delete',
            'status' => 'to_consume',
        ]);

        $this->actingAs($user)
            ->deleteJson(route('resources.destroy', $entry))
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertNotNull($entry->fresh()->archived_at);
    }
}
