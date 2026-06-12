<?php

namespace Tests\Feature\Http;

use App\Enums\EntryType;
use App\Models\Entry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuotesControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_quotes(): void
    {
        $this->get(route('quotes.index'))
            ->assertRedirect(route('auth.login'));
    }

    public function test_index_shows_user_quotes(): void
    {
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
    }

    public function test_store_creates_quote_with_details_and_tags(): void
    {
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
        $this->assertCount(2, $entry->tags);
    }

    public function test_update_modifies_quote_and_syncs_tags(): void
    {
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
            'slug' => 'oldtag'
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

        $this->assertEquals('New Quote Body', $entry->fresh()->body);
        $this->assertEquals('New Author', $entry->quoteDetails->fresh()->author);
        $this->assertCount(1, $entry->fresh()->tags);
        $this->assertEquals('newtag', $entry->fresh()->tags->first()->name);
    }

    public function test_destroy_archives_quote(): void
    {
        $user = User::factory()->create();
        $entry = Entry::factory()->for($user)->type(EntryType::Quote)->create([
            'body' => 'Quote to delete',
        ]);

        $this->actingAs($user)
            ->deleteJson(route('quotes.destroy', $entry))
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertNotNull($entry->fresh()->archived_at);
    }
}
