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

    public function test_store_creates_project(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('projects.store'), [
                'name' => 'New Project Title',
                'description' => 'New Description details',
                'color' => 'emerald',
            ])
            ->assertStatus(201)
            ->assertJsonPath('project.name', 'New Project Title')
            ->assertJsonPath('project.color', 'emerald');

        $this->assertDatabaseHas('projects', [
            'user_id' => $user->id,
            'name' => 'New Project Title',
            'color' => 'emerald',
            'status' => 'active',
        ]);
    }

    public function test_update_modifies_project_details(): void
    {
        $user = User::factory()->create();
        $project = Project::create([
            'user_id' => $user->id,
            'name' => 'Old Name',
            'slug' => 'old-name',
            'description' => 'Old description',
            'status' => 'active',
            'color' => 'orange',
        ]);

        $this->actingAs($user)
            ->putJson(route('projects.update', $project), [
                'name' => 'Updated Name',
                'description' => 'Updated description',
                'status' => 'paused',
                'color' => 'blue',
            ])
            ->assertOk()
            ->assertJsonPath('project.name', 'Updated Name')
            ->assertJsonPath('project.status', 'paused');

        $fresh = $project->fresh();
        $this->assertEquals('Updated Name', $fresh->name);
        $this->assertEquals('paused', $fresh->status);
        $this->assertEquals('blue', $fresh->color);
    }

    public function test_destroy_archives_project(): void
    {
        $user = User::factory()->create();
        $project = Project::create([
            'user_id' => $user->id,
            'name' => 'To Archive',
            'slug' => 'to-archive',
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->deleteJson(route('projects.destroy', $project))
            ->assertOk()
            ->assertJson(['success' => true]);

        $fresh = $project->fresh();
        $this->assertEquals('paused', $fresh->status);
        $this->assertNotNull($fresh->archived_at);
    }
}
