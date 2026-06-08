<?php

namespace Tests\Feature\Domains\Capture;

use App\Domains\Capture\Services\CaptureService;
use App\Models\Project;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CaptureServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_captures_a_task_with_tags_project_priority_due(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $entry = app(CaptureService::class)->capture('task review auth PR due:tomorrow !high #security @sideproject');

        $this->assertSame('task', $entry->type->value);
        $this->assertSame('review auth PR', $entry->title);
        $this->assertSame('open', $entry->status);
        $this->assertNotNull($entry->project_id);
        $this->assertSame('sideproject', Project::query()->find($entry->project_id)->slug);
        $this->assertSame(['security'], $entry->tags()->pluck('name')->all());
        $this->assertNotNull($entry->taskDetails);
        $this->assertSame(3, $entry->taskDetails->priority);
        $this->assertNotNull($entry->taskDetails->due_at);
    }

    public function test_captures_a_bookmark_from_bare_url(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $entry = app(CaptureService::class)->capture('https://redis.io/streams #redis');

        $this->assertSame('bookmark', $entry->type->value);
        $this->assertNotNull($entry->bookmarkDetails);
        $this->assertSame('https://redis.io/streams', $entry->bookmarkDetails->url);
        $this->assertSame('redis.io', $entry->bookmarkDetails->site);
        $this->assertSame('unread', $entry->bookmarkDetails->review_state);
        $this->assertSame(['redis'], $entry->tags()->pluck('name')->all());
    }

    public function test_reuses_existing_tag(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        Tag::factory()->for($user)->create(['name' => 'redis', 'slug' => 'redis']);

        app(CaptureService::class)->capture('note pubsub thoughts #redis');

        $this->assertSame(1, Tag::query()->where('name', 'redis')->count());
    }

    public function test_no_verb_defaults_to_note(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $entry = app(CaptureService::class)->capture('a random thought');

        $this->assertSame('note', $entry->type->value);
        $this->assertSame('a random thought', $entry->title);
    }
}
