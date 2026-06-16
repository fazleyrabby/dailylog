<?php

use App\Enums\EntryType;
use App\Jobs\EnrichBookmarkJob;
use App\Models\Entry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

test('it enriches bookmark metadata from html', function () {
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

    expect($freshEntry->title)->toEqual('Laravel Cool Blog Post');
    expect($freshDetails->site)->toEqual('laravel.com');
    expect($freshDetails->description)->toEqual('This is a very cool blog post about Laravel 12 features.');
    expect($freshDetails->image_url)->toEqual('https://laravel.com/images/og-cool.png');
    expect($freshDetails->favicon_url)->toEqual('https://laravel.com/favicon-32x32.png');
    expect($freshDetails->fetched_at)->not->toBeNull();
    expect($freshDetails->raw_meta['og_title'])->toEqual('Laravel Cool Blog Post');
});
