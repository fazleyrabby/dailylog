<?php

namespace Tests\Feature\Http;

use App\Enums\EntryType;
use App\Jobs\EnrichBookmarkJob;
use App\Models\Entry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class BookmarksControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_bookmarks(): void
    {
        $this->get(route('bookmarks.index'))
            ->assertRedirect(route('auth.login'));
    }

    public function test_index_shows_user_bookmarks(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $bookmark = Entry::factory()->for($user)->type(EntryType::Bookmark)->create([
            'title' => 'My Bookmark',
        ]);
        $bookmark->bookmarkDetails()->create([
            'url' => 'https://example.com/my',
            'site' => 'example.com',
            'review_state' => 'unread',
        ]);

        $other = Entry::factory()->for($otherUser)->type(EntryType::Bookmark)->create([
            'title' => 'Other Bookmark',
        ]);
        $other->bookmarkDetails()->create([
            'url' => 'https://example.com/other',
            'site' => 'example.com',
            'review_state' => 'unread',
        ]);

        $this->actingAs($user)
            ->get(route('bookmarks.index'))
            ->assertOk()
            ->assertViewHas('bookmarks', function ($bookmarks) {
                return count($bookmarks) === 1 && $bookmarks[0]['title'] === 'My Bookmark';
            });
    }

    public function test_store_creates_bookmark_and_dispatches_job(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('bookmarks.store'), [
                'url' => 'https://laravel.com/docs',
                'tags' => ['laravel', 'docs'],
            ])
            ->assertStatus(201)
            ->assertJsonPath('bookmark.url', 'https://laravel.com/docs');

        $this->assertDatabaseHas('entries', [
            'user_id' => $user->id,
            'type' => 'bookmark',
        ]);

        $this->assertDatabaseHas('bookmark_details', [
            'url' => 'https://laravel.com/docs',
            'review_state' => 'unread',
        ]);

        $entry = Entry::where('user_id', $user->id)->first();
        $this->assertCount(2, $entry->tags);

        Queue::assertPushed(EnrichBookmarkJob::class);
    }

    public function test_mark_reviewed_updates_state(): void
    {
        $user = User::factory()->create();
        $entry = Entry::factory()->for($user)->type(EntryType::Bookmark)->create([
            'title' => 'Some Bookmark',
        ]);
        $entry->bookmarkDetails()->create([
            'url' => 'https://example.com',
            'site' => 'example.com',
            'review_state' => 'unread',
        ]);

        $this->actingAs($user)
            ->patchJson(route('bookmarks.reviewed', $entry))
            ->assertOk()
            ->assertJsonPath('bookmark.state', 'reviewed');

        $this->assertEquals('reviewed', $entry->bookmarkDetails->fresh()->review_state);
    }

    public function test_destroy_archives_bookmark(): void
    {
        $user = User::factory()->create();
        $entry = Entry::factory()->for($user)->type(EntryType::Bookmark)->create([
            'title' => 'Bookmark to Delete',
        ]);

        $this->actingAs($user)
            ->deleteJson(route('bookmarks.destroy', $entry))
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertNotNull($entry->fresh()->archived_at);
    }
}
