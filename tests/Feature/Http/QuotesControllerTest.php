<?php

use App\Enums\EntryType;
use App\Models\Entry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('guest cannot access quotes', function () {
    $this->get(route('quotes.index'))
        ->assertRedirect(route('auth.login'));
});

test('index shows user quotes', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $quote = Entry::factory()->for($user)->type(EntryType::Quote)->create([
        'body' => 'Marcus Quote',
    ]);
    $quote->quoteDetails()->create([
        'author' => 'Marcus Aurelius',
        'source' => 'Meditations',
        'location' => 'Book IV',
    ]);

    $other = Entry::factory()->for($otherUser)->type(EntryType::Quote)->create([
        'body' => 'Other Quote',
    ]);
    $other->quoteDetails()->create([
        'author' => 'Seneca',
        'source' => 'Letters',
        'location' => 'Letter 1',
    ]);

    $this->actingAs($user)
        ->get(route('quotes.index'))
        ->assertOk()
        ->assertViewHas('quotes', function ($quotes) {
            return count($quotes) === 1 && $quotes[0]['body'] === 'Marcus Quote';
        });
});

test('store creates quote with details and tags', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson(route('quotes.store'), [
            'body' => 'I think, therefore I am.',
            'author' => 'René Descartes',
            'source' => 'Discourse on the Method',
            'location' => 'Part IV',
            'tags' => ['philosophy', 'rationalism'],
        ])
        ->assertStatus(201)
        ->assertJsonPath('quote.body', 'I think, therefore I am.')
        ->assertJsonPath('quote.author', 'René Descartes');

    $this->assertDatabaseHas('entries', [
        'user_id' => $user->id,
        'type' => 'quote',
        'body' => 'I think, therefore I am.',
    ]);

    $this->assertDatabaseHas('quote_details', [
        'author' => 'René Descartes',
        'source' => 'Discourse on the Method',
        'location' => 'Part IV',
    ]);

    $entry = Entry::where('user_id', $user->id)->first();
    expect($entry->tags)->toHaveCount(2);
});

test('update modifies quote and syncs tags', function () {
    $user = User::factory()->create();
    $entry = Entry::factory()->for($user)->type(EntryType::Quote)->create([
        'body' => 'Old Quote Body',
    ]);
    $entry->quoteDetails()->create([
        'author' => 'Old Author',
    ]);

    $entry->tags()->create([
        'user_id' => $user->id,
        'name' => 'oldtag',
        'slug' => 'oldtag',
    ]);

    $this->actingAs($user)
        ->putJson(route('quotes.update', $entry), [
            'body' => 'New Quote Body',
            'author' => 'New Author',
            'source' => 'New Source',
            'location' => 'New Location',
            'tags' => ['newtag'],
        ])
        ->assertOk()
        ->assertJsonPath('quote.body', 'New Quote Body')
        ->assertJsonPath('quote.author', 'New Author');

    expect($entry->fresh()->body)->toEqual('New Quote Body');
    expect($entry->quoteDetails->fresh()->author)->toEqual('New Author');
    expect($entry->fresh()->tags)->toHaveCount(1);
    expect($entry->fresh()->tags->first()->name)->toEqual('newtag');
});

test('destroy archives quote', function () {
    $user = User::factory()->create();
    $entry = Entry::factory()->for($user)->type(EntryType::Quote)->create([
        'body' => 'Quote to delete',
    ]);

    $this->actingAs($user)
        ->deleteJson(route('quotes.destroy', $entry))
        ->assertOk()
        ->assertJson(['success' => true]);

    expect($entry->fresh()->archived_at)->not->toBeNull();
});
