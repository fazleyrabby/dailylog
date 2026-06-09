<?php

namespace Tests\Feature\Http;

use App\Enums\EntryType;
use App\Models\Entry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotesControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_notes(): void
    {
        $this->get(route('notes.index'))
            ->assertRedirect(route('auth.login'));
    }

    public function test_index_shows_user_notes(): void
    {
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
    }

    public function test_store_creates_blank_note(): void
    {
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
    }

    public function test_update_modifies_title_and_body(): void
    {
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

        $this->assertEquals('New Awesome Title', $entry->fresh()->title);
        $this->assertEquals('New Awesome Body', $entry->fresh()->body);
    }

    public function test_destroy_archives_note(): void
    {
        $user = User::factory()->create();
        $entry = Entry::factory()->for($user)->type(EntryType::Note)->create([
            'title' => 'Archivable Note',
        ]);

        $this->actingAs($user)
            ->deleteJson(route('notes.destroy', $entry))
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertNotNull($entry->fresh()->archived_at);
    }
}
