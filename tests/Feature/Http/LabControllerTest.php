<?php

namespace Tests\Feature\Http;

use App\Enums\EntryType;
use App\Models\Entry;
use App\Models\User;
use App\Models\LabItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LabControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_lab(): void
    {
        $this->get(route('lab.index'))
            ->assertRedirect(route('auth.login'));
    }

    public function test_index_creates_default_board_when_empty_and_redirects(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('lab.index'))
            ->assertRedirect();

        $this->assertDatabaseHas('entries', [
            'user_id' => $user->id,
            'type' => 'lab',
            'title' => 'My First Canvas',
        ]);
    }

    public function test_index_redirects_to_existing_board(): void
    {
        $user = User::factory()->create();
        $board = Entry::factory()->for($user)->type(EntryType::Lab)->create([
            'title' => 'Existing Mindmap',
        ]);

        $this->actingAs($user)
            ->get(route('lab.index'))
            ->assertRedirect(route('lab.show', $board));
    }

    public function test_show_renders_canvas_and_sends_required_view_variables(): void
    {
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
    }

    public function test_store_creates_new_board(): void
    {
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
    }

    public function test_update_items_saves_coordinates_and_prunes_deleted_items(): void
    {
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
                    ]
                ]
            ])
            ->assertOk();

        // Check first item updated
        $this->assertEquals('Keep Me Updated', $item->fresh()->title);
        $this->assertEquals(100, $item->fresh()->x);

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
    }

    public function test_graduate_converts_sticky_to_note_and_replaces_with_reference(): void
    {
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
        $this->assertEquals('reference', $item->fresh()->type);
        $this->assertEquals($newNote->id, $item->fresh()->target_entry_id);
        $this->assertNull($item->fresh()->title);
    }
}
