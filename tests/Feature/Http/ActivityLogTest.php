<?php

use App\Enums\EntryType;
use App\Models\Entry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

test('guest cannot access activity log', function () {
    $this->get(route('activity-log.index'))
        ->assertRedirect(route('auth.login'));
});

test('activity log shows created, updated, and archived events', function () {
    $user = User::factory()->create();

    // Create a note that was created, updated, and archived on specific times
    $note = Entry::factory()->for($user)->type(EntryType::Note)->create([
        'title' => 'Timeline Note',
    ]);

    DB::table('entries')
        ->where('id', $note->id)
        ->update([
            'created_at' => now()->subHours(2),
            'updated_at' => now()->subHour(),
            'archived_at' => now(),
        ]);

    $this->actingAs($user);

    $response = $this->get(route('activity-log.index'));
    $response->assertOk();

    $grouped = $response->viewData('groupedEvents');
    $this->assertNotEmpty($grouped);

    $actions = collect($grouped)->flatMap(fn ($g) => $g['items'])->pluck('action')->toArray();
    $this->assertContains('created', $actions);
    $this->assertContains('updated', $actions);
    $this->assertContains('archived', $actions);
});
