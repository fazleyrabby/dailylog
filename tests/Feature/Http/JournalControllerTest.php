<?php

namespace Tests\Feature\Http;

use App\Enums\EntryType;
use App\Models\Entry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JournalControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_journal(): void
    {
        $this->get(route('journal.index'))
            ->assertRedirect(route('auth.login'));
    }

    public function test_index_shows_user_journals(): void
    {
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
    }

    public function test_store_creates_blank_journal_for_date(): void
    {
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
    }

    public function test_update_serializes_sections_to_body(): void
    {
        $user = User::factory()->create();
        $entry = Entry::factory()->for($user)->type(EntryType::Journal)->create([
            'occurred_on' => '2026-06-08',
            'body' => json_encode([
                'learned' => '',
                'worked' => '',
                'wins' => '',
                'ideas' => '',
            ]),
        ]);

        $this->actingAs($user)
            ->putJson(route('journal.update', $entry), [
                'learned' => 'Learned Laravel 12',
                'worked' => 'Wired up controllers',
                'wins' => '100% tests passed',
                'ideas' => 'Automate everything',
            ])
            ->assertOk()
            ->assertJsonPath('entry.learned', 'Learned Laravel 12')
            ->assertJsonPath('entry.worked', 'Wired up controllers')
            ->assertJsonPath('entry.wins', '100% tests passed')
            ->assertJsonPath('entry.ideas', 'Automate everything');

        $decodedBody = json_decode($entry->fresh()->body, true);
        $this->assertEquals('Learned Laravel 12', $decodedBody['learned']);
        $this->assertEquals('Wired up controllers', $decodedBody['worked']);
        $this->assertEquals('100% tests passed', $decodedBody['wins']);
        $this->assertEquals('Automate everything', $decodedBody['ideas']);
    }
}
