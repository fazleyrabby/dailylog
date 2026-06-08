<?php

namespace Tests\Feature;

use App\Models\Entry;
use App\Models\Project;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OwnershipScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_entry_queries_filtered_by_authed_user(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();

        Entry::factory()->for($owner)->create(['title' => 'mine']);
        Entry::factory()->for($intruder)->create(['title' => 'theirs']);

        $this->actingAs($owner);

        $this->assertSame(1, Entry::query()->count());
        $this->assertSame('mine', Entry::query()->value('title'));
    }

    public function test_project_and_tag_queries_filtered_by_authed_user(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        Project::factory()->for($owner)->create();
        Project::factory()->for($intruder)->create();
        Tag::factory()->for($owner)->create();
        Tag::factory()->for($intruder)->create();

        $this->actingAs($owner);

        $this->assertSame(1, Project::query()->count());
        $this->assertSame(1, Tag::query()->count());
    }

    public function test_without_ownership_bypasses_scope(): void
    {
        $a = User::factory()->create();
        $b = User::factory()->create();
        Entry::factory()->for($a)->create();
        Entry::factory()->for($b)->create();

        $this->actingAs($a);

        $this->assertSame(2, Entry::query()->withoutOwnership()->count());
    }
}
