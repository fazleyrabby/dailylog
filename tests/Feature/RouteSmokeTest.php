<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RouteSmokeTest extends TestCase
{
    use RefreshDatabase;

    public static function authedRoutes(): array
    {
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
    }

    #[DataProvider('authedRoutes')]
    public function test_guest_redirected_to_login(string $name): void
    {
        $this->get(route($name))->assertRedirect(route('auth.login'));
    }

    #[DataProvider('authedRoutes')]
    public function test_authed_user_sees_page(string $name): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get(route($name))->assertOk();
    }
}
