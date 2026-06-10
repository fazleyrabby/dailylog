<?php

namespace Tests\Feature\Http;

use App\Enums\EntryType;
use App\Models\Entry;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectsControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_projects(): void
    {
        $this->get(route('projects.index'))
            ->assertRedirect(route('auth.login'));
    }

    public function test_index_shows_database_projects_and_connected_elements(): void
    {
        $user = User::factory()->create();

        $project = Project::create([
            'user_id' => $user->id,
            'name' => 'Laravel OS',
            'slug' => 'laravel-os',
            'status' => 'active',
        ]);

        // Incomplete task
        $openTask = Entry::factory()->for($user)->type(EntryType::Task)->create([
            'title' => 'Open Task Test',
            'project_id' => $project->id,
        ]);
        $openTask->taskDetails()->create([
            'due_at' => now(),
            'priority' => 2,
        ]);

        // Completed task
        $completedTask = Entry::factory()->for($user)->type(EntryType::Task)->create([
            'title' => 'Completed Task Test',
            'project_id' => $project->id,
        ]);
        $completedTask->taskDetails()->create([
            'completed_at' => now()->subDays(2),
            'priority' => 1,
        ]);

        // Linked note
        Entry::factory()->for($user)->type(EntryType::Note)->create([
            'title' => 'Note Test',
            'project_id' => $project->id,
        ]);

        $this->actingAs($user)
            ->get(route('projects.index'))
            ->assertOk()
            ->assertViewHas('projects', function ($projects) {
                return count($projects) === 1 && 
                    $projects[0]['name'] === 'Laravel OS' &&
                    $projects[0]['progress'] === 50 && // 1 completed out of 2 total
                    count($projects[0]['tasks']) === 1 && // 1 open task
                    count($projects[0]['notes']) === 1 && // 1 note
                    count($projects[0]['activity']) === 2; // 1 note added + 1 task completed
            });
    }
}
