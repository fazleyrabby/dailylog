<?php

namespace Tests\Feature;

use Tests\TestCase;

class SyncAppleNotesTest extends TestCase
{
    public function test_it_fails_without_remote_dir(): void
    {
        $this->artisan('notes:sync-apple')
            ->expectsOutputToContain('--remote-dir is required')
            ->assertExitCode(1);
    }
}
