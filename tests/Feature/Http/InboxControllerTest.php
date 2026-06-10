<?php

namespace Tests\Feature\Http;

use App\Enums\EntryType;
use App\Models\Entry;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InboxControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_inbox(): void
    {
        $this->get(route('inbox.index'))
            ->assertRedirect(route('auth.login'));
    }

    public function test_index_shows_database_inbox_items_and_projects(): void
    {
        $user = User::factory()->create();
        $project = Project::create([
            'user_id' => $user->id,
            'name' => 'Design Project',
            'slug' => 'design-project',
            'status' => 'active',
        ]);

        // Unfiled note (lands in inbox)
        Entry::factory()->for($user)->type(EntryType::Note)->create([
            'title' => 'Triage this note',
        ]);

        // Filed note (does not land in inbox)
        Entry::factory()->for($user)->type(EntryType::Note)->create([
            'title' => 'Filed note',
            'project_id' => $project->id,
        ]);

        $this->actingAs($user)
            ->get(route('inbox.index'))
            ->assertOk()
            ->assertViewHas('entries', function ($entries) {
                return count($entries) === 1 && $entries[0]['text'] === 'Triage this note';
            })
            ->assertViewHas('projects', function ($projects) {
                return count($projects) === 1 && $projects[0]['name'] === 'Design Project';
            });
    }

    public function test_triage_archives_entry(): void
    {
        $user = User::factory()->create();
        $entry = Entry::factory()->for($user)->type(EntryType::Note)->create();

        $this->actingAs($user)
            ->patchJson(route('inbox.triage', $entry), [
                'action' => 'archive',
            ])
            ->assertOk();

        $this->assertNotNull($entry->fresh()->archived_at);
    }

    public function test_triage_files_as_task_with_project(): void
    {
        $user = User::factory()->create();
        $project = Project::create([
            'user_id' => $user->id,
            'name' => 'Test Project',
            'slug' => 'test-project',
            'status' => 'active',
        ]);
        $entry = Entry::factory()->for($user)->type(EntryType::Note)->create();

        $this->actingAs($user)
            ->patchJson(route('inbox.triage', $entry), [
                'action' => 'task',
                'project_id' => $project->id,
            ])
            ->assertOk();

        $fresh = $entry->fresh();
        $this->assertEquals(EntryType::Task, $fresh->type);
        $this->assertEquals($project->id, $fresh->project_id);
        $this->assertTrue($fresh->taskDetails()->exists());
    }
}
