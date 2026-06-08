<?php

use Illuminate\Support\Facades\Route;

// Seed realistic mock data representing a developer's Workspace
$mockData = [
    'user' => [
        'name' => 'Developer User',
        'email' => 'developer@local.net',
        'avatar_initial' => 'R'
    ],
    'tasks' => [
        'inbox' => [
            ['id' => 1, 'title' => 'Docker production config review', 'priority' => 'medium', 'project' => 'DevOps', 'tags' => ['docker', 'ops'], 'due_at' => null, 'completed' => false],
            ['id' => 2, 'title' => 'Buy SSL cert for client sandbox', 'priority' => 'low', 'project' => 'Freelancing', 'tags' => ['security'], 'due_at' => null, 'completed' => false]
        ],
        'today' => [
            ['id' => 3, 'title' => 'Review pull request for auth service rewrite', 'priority' => 'high', 'project' => 'DailyLOG', 'tags' => ['security', 'auth'], 'due_at' => 'Today', 'completed' => false],
            ['id' => 4, 'title' => 'Setup Redis cluster local configurations', 'priority' => 'high', 'project' => 'DailyLOG', 'tags' => ['redis', 'scaling'], 'due_at' => 'Today', 'completed' => false],
            ['id' => 5, 'title' => 'Write daily log reflection', 'priority' => 'low', 'project' => 'Self', 'tags' => ['journal'], 'due_at' => 'Today', 'completed' => true]
        ],
        'upcoming' => [
            ['id' => 6, 'title' => 'Optimize PostgreSQL full text search tsvector indexes', 'priority' => 'medium', 'project' => 'DailyLOG', 'tags' => ['postgres', 'db'], 'due_at' => 'Tomorrow', 'completed' => false],
            ['id' => 7, 'title' => 'Deploy staging app onto AWS ECS Cluster', 'priority' => 'high', 'project' => 'DevOps', 'tags' => ['aws', 'ecs'], 'due_at' => 'In 3 days', 'completed' => false],
            ['id' => 8, 'title' => 'Setup backups script with AWS S3 integration', 'priority' => 'low', 'project' => 'DailyLOG', 'tags' => ['backups'], 'due_at' => 'In 5 days', 'completed' => false]
        ],
        'completed' => [
            ['id' => 9, 'title' => 'Configure Vite with Tailwind v4 engine', 'priority' => 'medium', 'project' => 'DailyLOG', 'tags' => ['tailwind', 'frontend'], 'due_at' => 'Yesterday', 'completed' => true]
        ]
    ],
    'notes' => [
        'pinned' => [
            [
                'id' => 10, 
                'title' => 'Laravel Optimization Notes', 
                'body' => "## Production Configs\n\nOptimizing Laravel 12 monoliths at personal scale. Ensure the following configurations are set:\n\n- Enable **OPcache** in `php.ini`.\n- Run `php artisan config:cache`, `route:cache`, and `view:cache`.\n- Configure **Redis** as cache and session driver.\n- Use **Octane** with FrankenPHP for maximum response efficiency.",
                'tags' => ['laravel', 'performance'], 
                'project' => 'DailyLOG', 
                'status' => 'active', 
                'updated_at' => '2 hours ago'
            ],
            [
                'id' => 11, 
                'title' => 'PostgreSQL Full Text Search Configuration', 
                'body' => "## Postgres FTS setup\n\nUsing Postgres instead of Elasticsearch for simple local projects:\n\n- Map a tsvector column for matching keywords.\n- Build a custom database trigger to refresh vectors on save.\n- Run matches with dynamic `tsquery` input variables.",
                'tags' => ['postgres', 'db', 'search'], 
                'project' => 'DailyLOG', 
                'status' => 'active', 
                'updated_at' => 'Yesterday'
            ]
        ],
        'recent' => [
            [
                'id' => 12, 
                'title' => 'Redis Streams Pub/Sub Architecture', 
                'body' => "Detailed research on how Redis Streams can act as a message broker for async jobs without overhead. Discuss consumer group logic, stream trimming, and XACK acknowledge logic.",
                'tags' => ['redis', 'architecture'], 
                'project' => 'DailyLOG', 
                'status' => 'active', 
                'updated_at' => '5 hours ago'
            ],
            [
                'id' => 13, 
                'title' => 'Docker Container Security Checklist', 
                'body' => "Security guidelines for production containers:\n- Use non-root user execution\n- Read-only file system configurations\n- Block port scans using strict firewalls.",
                'tags' => ['docker', 'security'], 
                'project' => 'DevOps', 
                'status' => 'active', 
                'updated_at' => '3 days ago'
            ],
            [
                'id' => 14, 
                'title' => 'AWS ECS Deployment Guide', 
                'body' => "Deploying to ECS Fargate container instances. Map VPC configurations, launch types, and subnets. Handle task definitions mapping secret keys from AWS Parameter Store.",
                'tags' => ['aws', 'ecs'], 
                'project' => 'DevOps', 
                'status' => 'active', 
                'updated_at' => '5 days ago'
            ]
        ]
    ],
    'projects' => [
        ['id' => 15, 'name' => 'DailyLOG', 'desc' => 'Personal Life OS single source of truth dashboard', 'status' => 'active', 'tasks_count' => 4, 'notes_count' => 5, 'color' => 'orange', 'last_active' => '2h ago'],
        ['id' => 16, 'name' => 'DevOps', 'desc' => 'Infrastructure configurations and deployment setups', 'status' => 'active', 'tasks_count' => 2, 'notes_count' => 2, 'color' => 'blue', 'last_active' => '3d ago'],
        ['id' => 17, 'name' => 'Freelancing', 'desc' => 'Client works and miscellaneous freelance projects', 'status' => 'active', 'tasks_count' => 1, 'notes_count' => 1, 'color' => 'emerald', 'last_active' => '6d ago'],
        ['id' => 18, 'name' => 'Self', 'desc' => 'Personal development, health, and logs', 'status' => 'active', 'tasks_count' => 1, 'notes_count' => 3, 'color' => 'violet', 'last_active' => 'Today']
    ],
    'learning' => [
        ['id' => 19, 'title' => 'AWS ECS Container Deployments', 'kind' => 'course', 'provider' => 'Acme Cloud Academy', 'progress' => 45, 'status' => 'active', 'tags' => ['aws', 'devops'], 'last_active' => '34 days ago', 'slipping' => true],
        ['id' => 20, 'title' => 'PostgreSQL Advanced Indexing', 'kind' => 'topic', 'provider' => 'PG Mastery', 'progress' => 70, 'status' => 'active', 'tags' => ['postgres', 'db'], 'last_active' => 'Today', 'slipping' => false],
        ['id' => 21, 'title' => 'Laravel Octane Monolith Optimization', 'kind' => 'course', 'provider' => 'Laracasts', 'progress' => 15, 'status' => 'active', 'tags' => ['laravel', 'performance'], 'last_active' => '2 days ago', 'slipping' => false]
    ],
    'bookmarks' => [
        'unread' => [
            ['id' => 22, 'title' => 'PostHog Developer Dashboard UI Design Best Practices', 'url' => 'https://posthog.com/blog/ui-design-principles', 'site' => 'posthog.com', 'desc' => 'Learn how PostHog designs high-density developer interfaces...', 'tags' => ['design', 'ux'], 'added' => '60 days ago', 'slipping' => true],
            ['id' => 23, 'title' => 'Redis Streams Deep-dive Guide', 'url' => 'https://redis.io/docs/manual/data-types/streams', 'site' => 'redis.io', 'desc' => 'The complete guide to understanding Redis streams logic...', 'tags' => ['redis', 'research'], 'added' => '30 days ago', 'slipping' => true]
        ],
        'reviewed' => [
            ['id' => 24, 'title' => 'Vite v8 Release Notes', 'url' => 'https://vite.dev/blog/vite-8', 'site' => 'vite.dev', 'desc' => 'Read about the performance improvements and Tailwind v4 integrations in Vite v8...', 'tags' => ['vite', 'frontend'], 'added' => 'Yesterday', 'slipping' => false]
        ]
    ],
    'resources' => [
        ['id' => 25, 'title' => 'Designing Data-Intensive Applications', 'type' => 'book', 'author' => 'Martin Kleppmann', 'consume_state' => 'done', 'url' => 'https://dataintensive.net', 'tags' => ['architecture', 'databases']],
        ['id' => 26, 'title' => 'Laravel 12 Deep Dive Video Series', 'type' => 'video', 'author' => 'Laracasts', 'consume_state' => 'consuming', 'url' => 'https://laracasts.com/series/laravel-12-deep-dive', 'tags' => ['laravel']],
        ['id' => 27, 'title' => 'Refactoring UI', 'type' => 'book', 'author' => 'Adam Wathan & Steve Schoger', 'consume_state' => 'done', 'url' => 'https://refactoringui.com', 'tags' => ['design', 'ux']]
    ],
    'quotes' => [
        ['id' => 28, 'body' => 'Simple things should be simple, complex things should be possible.', 'author' => 'Alan Kay', 'source' => 'Computer History Archives', 'tags' => ['design-philosophy']],
        ['id' => 29, 'body' => 'The computer is a bicycle for the mind.', 'author' => 'Steve Jobs', 'source' => '1980 interview', 'tags' => ['inspiration']]
    ],
    'slipping' => [
        ['id' => 19, 'title' => 'AWS ECS Container Deployments', 'type' => 'Learning', 'days' => 34, 'severity' => 'high'],
        ['id' => 23, 'title' => 'Redis Streams Deep-dive Guide', 'type' => 'Bookmark', 'days' => 30, 'severity' => 'medium'],
        ['id' => 17, 'title' => 'Freelancing Project Container', 'type' => 'Project', 'days' => 21, 'severity' => 'low'],
        ['id' => 1, 'title' => 'Docker production config review', 'type' => 'Task', 'days' => 30, 'severity' => 'low']
    ]
];

// Map routes
Route::get('/', function () {
    return redirect('/dashboard');
});

Route::get('/dashboard', function () use ($mockData) {
    return view('pages.dashboard', ['data' => $mockData]);
});

Route::get('/notes', function () use ($mockData) {
    return view('pages.notes', ['data' => $mockData]);
});

Route::get('/tasks', function () use ($mockData) {
    return view('pages.tasks', ['data' => $mockData]);
});

Route::get('/journal', function () use ($mockData) {
    return view('pages.journal', ['data' => $mockData]);
});

Route::get('/bookmarks', function () use ($mockData) {
    return view('pages.bookmarks', ['data' => $mockData]);
});

Route::get('/learning', function () use ($mockData) {
    return view('pages.learning', ['data' => $mockData]);
});

Route::get('/projects', function () use ($mockData) {
    return view('pages.projects', ['data' => $mockData]);
});

Route::get('/quotes', function () use ($mockData) {
    return view('pages.quotes', ['data' => $mockData]);
});

Route::get('/resources', function () use ($mockData) {
    return view('pages.resources', ['data' => $mockData]);
});

Route::get('/slipping', function () use ($mockData) {
    return view('pages.slipping', ['data' => $mockData]);
});

Route::get('/settings', function () use ($mockData) {
    return view('pages.settings', ['data' => $mockData]);
});

Route::get('/search', function () use ($mockData) {
    return view('pages.search', ['data' => $mockData]);
});

Route::get('/inbox', function () use ($mockData) {
    return view('pages.inbox', ['data' => $mockData]);
});
