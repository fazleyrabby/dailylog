<?php

use App\Enums\EntryType;
use App\Models\Entry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('guest cannot access journal', function () {
    $this->get(route('journal.index'))
        ->assertRedirect(route('auth.login'));
});

test('index shows user journals', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    Entry::factory()->for($user)->type(EntryType::Journal)->create([
        'occurred_on' => '2026-06-08',
        'body' => json_encode([
            'learned' => 'A learned thing',
            'worked' => 'A worked thing',
            'wins' => 'A win',
            'ideas' => 'An idea',
        ]),
    ]);

    Entry::factory()->for($otherUser)->type(EntryType::Journal)->create([
        'occurred_on' => '2026-06-07',
        'body' => json_encode([
            'learned' => 'Other learned thing',
            'worked' => 'Other worked thing',
            'wins' => 'Other win',
            'ideas' => 'Other idea',
        ]),
    ]);

    $this->actingAs($user)
        ->get(route('journal.index'))
        ->assertOk()
        ->assertViewHas('journalEntries', function ($entries) {
            return count($entries) === 1 &&
                isset($entries['2026-06-08']) &&
                $entries['2026-06-08']['learned'] === 'A learned thing';
        });
});

test('store creates blank journal for date', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson(route('journal.store'), [
            'occurred_on' => '2026-06-09',
        ])
        ->assertStatus(201)
        ->assertJsonPath('entry.occurred_on', '2026-06-09')
        ->assertJsonPath('entry.learned', '');

    $this->assertDatabaseHas('entries', [
        'user_id' => $user->id,
        'occurred_on' => '2026-06-09',
        'type' => 'journal',
        'status' => 'active',
    ]);
});

test('update serializes sections to body', function () {
    $user = User::factory()->create();
    $entry = Entry::factory()->for($user)->type(EntryType::Journal)->create([
        'occurred_on' => '2026-06-08',
        'body' => json_encode([
            'learned' => '',
            'worked' => '',
            'wins' => '',
            'ideas' => '',
            'mood' => '',
        ]),
    ]);

    $this->actingAs($user)
        ->putJson(route('journal.update', $entry), [
            'learned' => 'Learned Laravel 12',
            'worked' => 'Wired up controllers',
            'wins' => '100% tests passed',
            'ideas' => 'Automate everything',
            'mood' => 'focused',
        ])
        ->assertOk()
        ->assertJsonPath('entry.learned', 'Learned Laravel 12')
        ->assertJsonPath('entry.worked', 'Wired up controllers')
        ->assertJsonPath('entry.wins', '100% tests passed')
        ->assertJsonPath('entry.ideas', 'Automate everything')
        ->assertJsonPath('entry.mood', 'focused');

    $decodedBody = json_decode($entry->fresh()->body, true);
    expect($decodedBody['learned'])->toEqual('Learned Laravel 12');
    expect($decodedBody['worked'])->toEqual('Wired up controllers');
    expect($decodedBody['wins'])->toEqual('100% tests passed');
    expect($decodedBody['ideas'])->toEqual('Automate everything');
    expect($decodedBody['mood'])->toEqual('focused');
});
