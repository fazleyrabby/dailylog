<?php

namespace Tests\Feature\Policies;

use App\Models\Entry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EntryPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_view_update_delete(): void
    {
        $owner = User::factory()->create();
        $entry = Entry::factory()->for($owner)->create();

        $this->assertTrue($owner->can('view', $entry));
        $this->assertTrue($owner->can('update', $entry));
        $this->assertTrue($owner->can('delete', $entry));
    }

    public function test_other_users_cannot_view_update_delete(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $entry = Entry::factory()->for($owner)->create();

        $this->assertFalse($stranger->can('view', $entry));
        $this->assertFalse($stranger->can('update', $entry));
        $this->assertFalse($stranger->can('delete', $entry));
    }
}
