<?php

use App\Enums\EntryType;
use App\Jobs\EnrichBookmarkJob;
use App\Models\Entry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

test('guest cannot access bookmarks', function () {
    $this->get(route('bookmarks.index'))
        ->assertRedirect(route('auth.login'));
});

test('index shows user bookmarks', function () {
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
});

test('store creates bookmark and dispatches job', function () {
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
    expect($entry->tags)->toHaveCount(2);

    Queue::assertPushed(EnrichBookmarkJob::class);
});

test('mark reviewed updates state', function () {
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

    expect($entry->bookmarkDetails->fresh()->review_state)->toEqual('reviewed');
});

test('destroy archives bookmark', function () {
    $user = User::factory()->create();
    $entry = Entry::factory()->for($user)->type(EntryType::Bookmark)->create([
        'title' => 'Bookmark to Delete',
    ]);

    $this->actingAs($user)
        ->deleteJson(route('bookmarks.destroy', $entry))
        ->assertOk()
        ->assertJson(['success' => true]);

    expect($entry->fresh()->archived_at)->not->toBeNull();
});
