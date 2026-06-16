<?php

use App\Enums\EntryType;
use App\Models\Entry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('guest cannot access resources', function () {
    $this->get(route('resources.index'))
        ->assertRedirect(route('auth.login'));
});

test('index shows user resources', function () {
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
});

test('store creates resource with details and tags', function () {
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
    expect($entry->tags)->toHaveCount(2);
});

test('update modifies resource and syncs tags', function () {
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
        'slug' => 'oldtag',
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

    expect($entry->fresh()->title)->toEqual('New Title');
    expect($entry->fresh()->status)->toEqual('done');
    expect($entry->resourceDetails->fresh()->resource_type)->toEqual('video');
    expect($entry->resourceDetails->fresh()->author)->toEqual('New Author');
    expect($entry->resourceDetails->fresh()->consume_state)->toEqual('done');
    expect($entry->resourceDetails->fresh()->rating)->toEqual(4);
    expect($entry->fresh()->tags)->toHaveCount(1);
    expect($entry->fresh()->tags->first()->name)->toEqual('newtag');
});

test('destroy archives resource', function () {
    $user = User::factory()->create();
    $entry = Entry::factory()->for($user)->type(EntryType::Resource)->create([
        'title' => 'Resource to delete',
        'status' => 'to_consume',
    ]);

    $this->actingAs($user)
        ->deleteJson(route('resources.destroy', $entry))
        ->assertOk()
        ->assertJson(['success' => true]);

    expect($entry->fresh()->archived_at)->not->toBeNull();
});
