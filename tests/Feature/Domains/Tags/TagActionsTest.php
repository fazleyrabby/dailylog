<?php

namespace Tests\Feature\Domains\Tags;

use App\Domains\Tags\Actions\AttachTags;
use App\Domains\Tags\Actions\CreateTag;
use App\Domains\Tags\Actions\DetachTag;
use App\Domains\Tags\Actions\MergeTags;
use App\Domains\Tags\Actions\RenameTag;
use App\Models\Entry;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TagActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_tag_is_idempotent_by_name(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $a = app(CreateTag::class)->execute('Postgres');
        $b = app(CreateTag::class)->execute('Postgres');

        $this->assertSame($a->id, $b->id);
        $this->assertSame(1, Tag::query()->count());
    }

    public function test_rename_tag_updates_slug(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $tag = Tag::factory()->for($user)->create(['name' => 'redis', 'slug' => 'redis']);

        app(RenameTag::class)->execute($tag, 'Redis Cluster');

        $this->assertSame('Redis Cluster', $tag->fresh()->name);
        $this->assertSame('redis-cluster', $tag->fresh()->slug);
    }

    public function test_merge_moves_pivots_and_deletes_source(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $source = Tag::factory()->for($user)->create(['name' => 'pg', 'slug' => 'pg']);
        $target = Tag::factory()->for($user)->create(['name' => 'postgres', 'slug' => 'postgres']);

        $e1 = Entry::factory()->for($user)->create();
        $e2 = Entry::factory()->for($user)->create();
        $e1->tags()->attach($source->id);
        $e2->tags()->attach([$source->id, $target->id]);

        app(MergeTags::class)->execute($source, $target);

        $this->assertNull(Tag::query()->find($source->id));
        $this->assertSame(2, $target->fresh()->entries()->count());
    }

    public function test_attach_creates_missing_tags_and_pivots(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $entry = Entry::factory()->for($user)->create();

        app(AttachTags::class)->execute($entry, ['Docker', 'security', 'security']);

        $names = $entry->tags()->pluck('name')->sort()->values()->all();
        $this->assertSame(['Docker', 'security'], $names);
    }

    public function test_detach_removes_pivot(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $entry = Entry::factory()->for($user)->create();
        $tag = Tag::factory()->for($user)->create();
        $entry->tags()->attach($tag->id);

        app(DetachTag::class)->execute($entry, $tag);

        $this->assertSame(0, $entry->tags()->count());
    }
}
