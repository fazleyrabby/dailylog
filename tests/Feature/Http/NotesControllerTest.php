<?php

use App\Enums\EntryType;
use App\Models\Entry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('guest cannot access notes', function () {
    $this->get(route('notes.index'))
        ->assertRedirect(route('auth.login'));
});

test('index shows user notes', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    Entry::factory()->for($user)->type(EntryType::Note)->create([
        'title' => 'My Secret Note',
        'body' => 'Only for me',
    ]);

    Entry::factory()->for($otherUser)->type(EntryType::Note)->create([
        'title' => 'Someone Elses Note',
        'body' => 'None of your business',
    ]);

    $this->actingAs($user)
        ->get(route('notes.index'))
        ->assertOk()
        ->assertViewHas('notes', function ($notes) {
            return count($notes) === 1 && $notes[0]['title'] === 'My Secret Note';
        });
});

test('store creates blank note', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson(route('notes.store'))
        ->assertStatus(201)
        ->assertJsonPath('note.title', 'Untitled Note');

    $this->assertDatabaseHas('entries', [
        'user_id' => $user->id,
        'title' => 'Untitled Note',
        'type' => 'note',
    ]);
});

test('update modifies title and body', function () {
    $user = User::factory()->create();
    $entry = Entry::factory()->for($user)->type(EntryType::Note)->create([
        'title' => 'Old Title',
        'body' => 'Old Body',
    ]);

    $this->actingAs($user)
        ->putJson(route('notes.update', $entry), [
            'title' => 'New Awesome Title',
            'body' => 'New Awesome Body',
        ])
        ->assertOk()
        ->assertJsonPath('note.title', 'New Awesome Title')
        ->assertJsonPath('note.body', 'New Awesome Body');

    expect($entry->fresh()->title)->toEqual('New Awesome Title');
    expect($entry->fresh()->body)->toEqual('New Awesome Body');
});

test('destroy archives note', function () {
    $user = User::factory()->create();
    $entry = Entry::factory()->for($user)->type(EntryType::Note)->create([
        'title' => 'Archivable Note',
    ]);

    $this->actingAs($user)
        ->deleteJson(route('notes.destroy', $entry))
        ->assertOk()
        ->assertJson(['success' => true]);

    expect($entry->fresh()->archived_at)->not->toBeNull();
});
