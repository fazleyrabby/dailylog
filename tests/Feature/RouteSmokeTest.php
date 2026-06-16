<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

dataset('authedRoutes', function () {
    return [
        ['dashboard.index'],
        ['inbox.index'],
        ['search.index'],
        ['tasks.index'],
        ['notes.index'],
        ['journal.index'],
        ['bookmarks.index'],
        ['learning.index'],
        ['projects.index'],
        ['quotes.index'],
        ['resources.index'],
        ['slipping.index'],
        ['settings.profile'],
    ];
});

test('guest redirected to login', function (string $name) {
    $this->get(route($name))->assertRedirect(route('auth.login'));
})->with('authedRoutes');

test('authed user sees page', function (string $name) {
    $user = User::factory()->create();
    $this->actingAs($user)->get(route($name))->assertOk();
})->with('authedRoutes');
