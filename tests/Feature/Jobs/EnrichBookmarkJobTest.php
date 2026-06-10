<?php

namespace Tests\Feature\Jobs;

use App\Enums\EntryType;
use App\Jobs\EnrichBookmarkJob;
use App\Models\Entry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class EnrichBookmarkJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_enriches_bookmark_metadata_from_html(): void
    {
        $user = User::factory()->create();
        $entry = Entry::factory()->for($user)->type(EntryType::Bookmark)->create([
            'title' => 'Untitled Bookmark',
        ]);
        $details = $entry->bookmarkDetails()->create([
            'url' => 'https://laravel.com/blog/cool-post',
            'site' => 'laravel.com',
            'review_state' => 'unread',
        ]);

        $htmlContent = <<<'HTML'
        <!DOCTYPE html>
        <html>
        <head>
            <title>Laravel Cool Blog Post</title>
            <meta name="description" content="This is a very cool blog post about Laravel 12 features.">
            <meta property="og:image" content="https://laravel.com/images/og-cool.png">
            <link rel="icon" href="/favicon-32x32.png">
        </head>
        <body>
            <h1>Hello World</h1>
        </body>
        </html>
        HTML;

        Http::fake([
            'https://laravel.com/blog/cool-post' => Http::response($htmlContent, 200),
        ]);

        $job = new EnrichBookmarkJob($entry);
        $job->handle();

        $freshDetails = $details->fresh();
        $freshEntry = $entry->fresh();

        $this->assertEquals('Laravel Cool Blog Post', $freshEntry->title);
        $this->assertEquals('laravel.com', $freshDetails->site);
        $this->assertEquals('This is a very cool blog post about Laravel 12 features.', $freshDetails->description);
        $this->assertEquals('https://laravel.com/images/og-cool.png', $freshDetails->image_url);
        $this->assertEquals('https://laravel.com/favicon-32x32.png', $freshDetails->favicon_url);
        $this->assertNotNull($freshDetails->fetched_at);
        $this->assertEquals('Laravel Cool Blog Post', $freshDetails->raw_meta['og_title']);
    }
}
