<?php

use App\Enums\EntryType;
use App\Models\Entry;
use App\Models\LabItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('guest cannot access lab', function () {
    $this->get(route('lab.index'))
        ->assertRedirect(route('auth.login'));
});

test('index creates default board when empty and redirects', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('lab.index'))
        ->assertRedirect();

    $this->assertDatabaseHas('entries', [
        'user_id' => $user->id,
        'type' => 'lab',
        'title' => 'My First Canvas',
    ]);
});

test('index redirects to existing board', function () {
    $user = User::factory()->create();
    $board = Entry::factory()->for($user)->type(EntryType::Lab)->create([
        'title' => 'Existing Mindmap',
    ]);

    $this->actingAs($user)
        ->get(route('lab.index'))
        ->assertRedirect(route('lab.show', $board));
});

test('show renders canvas and sends required view variables', function () {
    $user = User::factory()->create();
    $board = Entry::factory()->for($user)->type(EntryType::Lab)->create([
        'title' => 'Project Canvas',
    ]);

    $item = LabItem::create([
        'entry_id' => $board->id,
        'type' => 'sticky',
        'title' => 'Research Notes',
        'x' => 10,
        'y' => 20,
        'width' => 200,
        'height' => 150,
    ]);

    $this->actingAs($user)
        ->get(route('lab.show', $board))
        ->assertOk()
        ->assertViewHas('activeBoard', $board)
        ->assertViewHas('items', function ($items) use ($item) {
            return $items->count() === 1 && $items->first()->id === $item->id;
        });
});

test('store creates new board', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('lab.store'), [
            'title' => 'Sprint Planning',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('entries', [
        'user_id' => $user->id,
        'type' => 'lab',
        'title' => 'Sprint Planning',
    ]);
});

test('update items saves coordinates and prunes deleted items', function () {
    $user = User::factory()->create();
    $board = Entry::factory()->for($user)->type(EntryType::Lab)->create();

    $item = LabItem::create([
        'entry_id' => $board->id,
        'type' => 'sticky',
        'title' => 'Keep Me',
        'x' => 10,
        'y' => 10,
    ]);

    $item2 = LabItem::create([
        'entry_id' => $board->id,
        'type' => 'sticky',
        'title' => 'Delete Me',
        'x' => 20,
        'y' => 20,
    ]);

    $this->actingAs($user)
        ->patchJson(route('lab.items.update', $board), [
            'items' => [
                [
                    'id' => $item->id,
                    'type' => 'sticky',
                    'title' => 'Keep Me Updated',
                    'content' => 'Content here',
                    'x' => 100,
                    'y' => 200,
                    'width' => 250,
                    'height' => 200,
                ],
                [
                    'id' => null, // new item
                    'type' => 'text',
                    'title' => 'New Text label',
                    'x' => 300,
                    'y' => 400,
                    'width' => 150,
                    'height' => 80,
                ],
            ],
        ])
        ->assertOk();

    // Check first item updated
    expect($item->fresh()->title)->toEqual('Keep Me Updated');
    expect($item->fresh()->x)->toEqual(100);

    // Check new item created
    $this->assertDatabaseHas('lab_items', [
        'entry_id' => $board->id,
        'type' => 'text',
        'title' => 'New Text label',
        'x' => 300,
    ]);

    // Check second item was deleted (pruned) because it wasn't sent in the request payload
    $this->assertDatabaseMissing('lab_items', [
        'id' => $item2->id,
    ]);
});

test('graduate converts sticky to note and replaces with reference', function () {
    $user = User::factory()->create();
    $board = Entry::factory()->for($user)->type(EntryType::Lab)->create();
    $item = LabItem::create([
        'entry_id' => $board->id,
        'type' => 'sticky',
        'title' => 'Graduating Note Sticky',
        'content' => 'Graduated note body markdown text.',
        'x' => 100,
        'y' => 150,
    ]);

    $this->actingAs($user)
        ->postJson(route('lab.items.graduate', $item), [
            'to_type' => 'note',
        ])
        ->assertOk();

    // Check a new note entry was created
    $this->assertDatabaseHas('entries', [
        'user_id' => $user->id,
        'type' => 'note',
        'title' => 'Graduating Note Sticky',
        'body' => 'Graduated note body markdown text.',
    ]);

    $newNote = Entry::where('type', 'note')->first();

    // Check that the canvas item has been converted to 'reference' pointing to the new note
    expect($item->fresh()->type)->toEqual('reference');
    expect($item->fresh()->target_entry_id)->toEqual($newNote->id);
    expect($item->fresh()->title)->toBeNull();
});
