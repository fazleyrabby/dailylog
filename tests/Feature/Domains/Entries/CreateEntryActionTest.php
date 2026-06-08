<?php

namespace Tests\Feature\Domains\Entries;

use App\Domains\Entries\Actions\CreateEntry;
use App\Domains\Entries\DTOs\EntryAttributes;
use App\Enums\CapturedVia;
use App\Enums\EntryType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateEntryActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_a_task_with_default_status(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $entry = app(CreateEntry::class)->execute(new EntryAttributes(
            type: EntryType::Task,
            title: 'Wire up auth',
            body: 'Use Laravel Breeze',
            capturedVia: CapturedVia::Palette,
        ));

        $this->assertSame('task', $entry->type->value);
        $this->assertSame('open', $entry->status);
        $this->assertSame($user->id, $entry->user_id);
        $this->assertNotNull($entry->last_activity_at);
        $this->assertSame('palette', $entry->captured_via);
    }

    public function test_creates_an_idea_with_spark_status(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $entry = app(CreateEntry::class)->execute(new EntryAttributes(
            type: EntryType::Idea,
            title: 'Personal LLM index',
        ));

        $this->assertSame('spark', $entry->status);
    }

    public function test_user_id_resolves_from_auth(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $entry = app(CreateEntry::class)->execute(new EntryAttributes(
            type: EntryType::Note,
            title: 'A note',
        ));

        $this->assertSame($user->id, $entry->user_id);
    }
}
