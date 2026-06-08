<?php

namespace Tests\Feature;

use App\Enums\EntryType;
use App\Http\ViewComposers\SidebarCountsComposer;
use App\Models\Entry;
use App\Models\Project;
use App\Models\TaskDetails;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SidebarCountsTest extends TestCase
{
    use RefreshDatabase;

    public function test_counts_match_db_state_for_authed_user(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $task = Entry::factory()->for($user)->type(EntryType::Task)->create(['status' => 'open']);
        TaskDetails::query()->create(['entry_id' => $task->id, 'due_at' => now(), 'priority' => 1]);

        Entry::factory()->for($user)->type(EntryType::Learning)->create(['status' => 'active']);
        Entry::factory()->for($user)->type(EntryType::Learning)->create(['status' => 'paused']);
        Project::factory()->for($user)->create(['status' => 'active']);

        $view = view('layouts.app');
        (new SidebarCountsComposer)->compose($view);
        $counts = $view->getData()['sidebarCounts'];

        $this->assertSame(1, $counts['tasks']);
        $this->assertSame(1, $counts['learning']);
        $this->assertSame(1, $counts['projects']);
        $this->assertSame(0, $counts['slipping']);
    }

    public function test_guest_gets_zero_counts(): void
    {
        $view = view('layouts.app');
        (new SidebarCountsComposer)->compose($view);
        $counts = $view->getData()['sidebarCounts'];

        $this->assertSame([0, 0, 0, 0], array_values($counts));
    }
}
