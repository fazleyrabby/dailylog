<?php

namespace Tests\Feature\Domains\Inbox;

use App\Domains\Inbox\Queries\InboxQuery;
use App\Enums\EntryType;
use App\Models\Entry;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InboxQueryTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_only_unfiled_unjournal_unarchived_entries(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $project = Project::factory()->for($user)->create();

        Entry::factory()->for($user)->type(EntryType::Note)->create(['title' => 'inbox-me']);
        Entry::factory()->for($user)->type(EntryType::Task)->create(['title' => 'also-inbox']);
        Entry::factory()->for($user)->type(EntryType::Note)->create(['project_id' => $project->id, 'title' => 'filed']);
        Entry::factory()->for($user)->type(EntryType::Journal)->create(['title' => 'today journal', 'occurred_on' => now()->toDateString()]);
        Entry::factory()->for($user)->type(EntryType::Note)->archived()->create(['title' => 'gone']);

        $paginated = app(InboxQuery::class)->paginate();

        $titles = collect($paginated->items())->pluck('title')->all();
        sort($titles);
        $this->assertSame(['also-inbox', 'inbox-me'], $titles);
    }
}
