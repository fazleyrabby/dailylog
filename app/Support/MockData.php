<?php

namespace App\Support;

/**
 * Visual prototype fixture. Per-page mock data, served until each module
 * is wired to real domain queries. Delete keys as modules ship.
 */
class MockData
{
    public static function all(): array
    {
        return [
            'tasks' => [
                'inbox' => [
                    ['id' => 1, 'title' => 'Docker production config review', 'priority' => 'medium', 'project' => 'DevOps', 'tags' => ['docker', 'ops'], 'due_at' => null, 'completed' => false],
                    ['id' => 2, 'title' => 'Buy SSL cert for client sandbox', 'priority' => 'low', 'project' => 'Freelancing', 'tags' => ['security'], 'due_at' => null, 'completed' => false],
                ],
                'today' => [
                    ['id' => 3, 'title' => 'Review pull request for auth service rewrite', 'priority' => 'high', 'project' => 'DailyLOG', 'tags' => ['security', 'auth'], 'due_at' => 'Today', 'completed' => false],
                    ['id' => 4, 'title' => 'Setup Redis cluster local configurations', 'priority' => 'high', 'project' => 'DailyLOG', 'tags' => ['redis', 'scaling'], 'due_at' => 'Today', 'completed' => false],
                    ['id' => 5, 'title' => 'Write daily log reflection', 'priority' => 'low', 'project' => 'Self', 'tags' => ['journal'], 'due_at' => 'Today', 'completed' => true],
                ],
                'upcoming' => [
                    ['id' => 6, 'title' => 'Optimize PostgreSQL full text search tsvector indexes', 'priority' => 'medium', 'project' => 'DailyLOG', 'tags' => ['postgres', 'db'], 'due_at' => 'Tomorrow', 'completed' => false],
                    ['id' => 7, 'title' => 'Deploy staging app onto AWS ECS Cluster', 'priority' => 'high', 'project' => 'DevOps', 'tags' => ['aws', 'ecs'], 'due_at' => 'In 3 days', 'completed' => false],
                    ['id' => 8, 'title' => 'Setup backups script with AWS S3 integration', 'priority' => 'low', 'project' => 'DailyLOG', 'tags' => ['backups'], 'due_at' => 'In 5 days', 'completed' => false],
                ],
                'completed' => [
                    ['id' => 9, 'title' => 'Configure Vite with Tailwind v4 engine', 'priority' => 'medium', 'project' => 'DailyLOG', 'tags' => ['tailwind', 'frontend'], 'due_at' => 'Yesterday', 'completed' => true],
                ],
            ],
            'notes' => [
                'pinned' => [
                    ['id' => 10, 'title' => 'Laravel Optimization Notes', 'body' => "## Production Configs\n\nOptimizing Laravel 12 monoliths at personal scale.", 'tags' => ['laravel', 'performance'], 'project' => 'DailyLOG', 'status' => 'active', 'updated_at' => '2 hours ago'],
                    ['id' => 11, 'title' => 'PostgreSQL Full Text Search Configuration', 'body' => "## Postgres FTS setup", 'tags' => ['postgres', 'db', 'search'], 'project' => 'DailyLOG', 'status' => 'active', 'updated_at' => 'Yesterday'],
                ],
                'recent' => [
                    ['id' => 12, 'title' => 'Redis Streams Pub/Sub Architecture', 'body' => 'Detailed research on Redis Streams as broker.', 'tags' => ['redis', 'architecture'], 'project' => 'DailyLOG', 'status' => 'active', 'updated_at' => '5 hours ago'],
                    ['id' => 13, 'title' => 'Docker Container Security Checklist', 'body' => 'Security guidelines for production containers.', 'tags' => ['docker', 'security'], 'project' => 'DevOps', 'status' => 'active', 'updated_at' => '3 days ago'],
                    ['id' => 14, 'title' => 'AWS ECS Deployment Guide', 'body' => 'Deploying to ECS Fargate container instances.', 'tags' => ['aws', 'ecs'], 'project' => 'DevOps', 'status' => 'active', 'updated_at' => '5 days ago'],
                ],
            ],
            'projects' => [
                ['id' => 15, 'name' => 'DailyLOG', 'desc' => 'Personal Life OS single source of truth dashboard', 'status' => 'active', 'tasks_count' => 4, 'notes_count' => 5, 'color' => 'orange', 'last_active' => '2h ago'],
                ['id' => 16, 'name' => 'DevOps', 'desc' => 'Infrastructure and deployment', 'status' => 'active', 'tasks_count' => 2, 'notes_count' => 2, 'color' => 'blue', 'last_active' => '3d ago'],
                ['id' => 17, 'name' => 'Freelancing', 'desc' => 'Client works', 'status' => 'active', 'tasks_count' => 1, 'notes_count' => 1, 'color' => 'emerald', 'last_active' => '6d ago'],
                ['id' => 18, 'name' => 'Self', 'desc' => 'Personal development', 'status' => 'active', 'tasks_count' => 1, 'notes_count' => 3, 'color' => 'violet', 'last_active' => 'Today'],
            ],
            'learning' => [
                ['id' => 19, 'title' => 'AWS ECS Container Deployments', 'kind' => 'course', 'provider' => 'Acme Cloud Academy', 'progress' => 45, 'status' => 'active', 'tags' => ['aws', 'devops'], 'last_active' => '34 days ago', 'slipping' => true],
                ['id' => 20, 'title' => 'PostgreSQL Advanced Indexing', 'kind' => 'topic', 'provider' => 'PG Mastery', 'progress' => 70, 'status' => 'active', 'tags' => ['postgres', 'db'], 'last_active' => 'Today', 'slipping' => false],
                ['id' => 21, 'title' => 'Laravel Octane Monolith Optimization', 'kind' => 'course', 'provider' => 'Laracasts', 'progress' => 15, 'status' => 'active', 'tags' => ['laravel', 'performance'], 'last_active' => '2 days ago', 'slipping' => false],
            ],
            'bookmarks' => [
                'unread' => [
                    ['id' => 22, 'title' => 'PostHog Developer Dashboard UI', 'url' => 'https://posthog.com/blog/ui-design-principles', 'site' => 'posthog.com', 'desc' => 'PostHog UI principles.', 'tags' => ['design', 'ux'], 'added' => '60 days ago', 'slipping' => true],
                    ['id' => 23, 'title' => 'Redis Streams Deep-dive Guide', 'url' => 'https://redis.io/docs/manual/data-types/streams', 'site' => 'redis.io', 'desc' => 'Redis streams.', 'tags' => ['redis', 'research'], 'added' => '30 days ago', 'slipping' => true],
                ],
                'reviewed' => [
                    ['id' => 24, 'title' => 'Vite v8 Release Notes', 'url' => 'https://vite.dev/blog/vite-8', 'site' => 'vite.dev', 'desc' => 'Performance + Tailwind v4.', 'tags' => ['vite', 'frontend'], 'added' => 'Yesterday', 'slipping' => false],
                ],
            ],
            'resources' => [
                ['id' => 25, 'title' => 'Designing Data-Intensive Applications', 'type' => 'book', 'author' => 'Martin Kleppmann', 'consume_state' => 'done', 'url' => 'https://dataintensive.net', 'tags' => ['architecture', 'databases']],
                ['id' => 26, 'title' => 'Laravel 12 Deep Dive Video Series', 'type' => 'video', 'author' => 'Laracasts', 'consume_state' => 'consuming', 'url' => 'https://laracasts.com/series/laravel-12-deep-dive', 'tags' => ['laravel']],
                ['id' => 27, 'title' => 'Refactoring UI', 'type' => 'book', 'author' => 'Adam Wathan & Steve Schoger', 'consume_state' => 'done', 'url' => 'https://refactoringui.com', 'tags' => ['design', 'ux']],
            ],
            'quotes' => [
                ['id' => 28, 'body' => 'Simple things should be simple, complex things should be possible.', 'author' => 'Alan Kay', 'source' => 'Computer History Archives', 'tags' => ['design-philosophy']],
                ['id' => 29, 'body' => 'The computer is a bicycle for the mind.', 'author' => 'Steve Jobs', 'source' => '1980 interview', 'tags' => ['inspiration']],
            ],
            'slipping' => [
                ['id' => 19, 'title' => 'AWS ECS Container Deployments', 'type' => 'Learning', 'days' => 34, 'severity' => 'high'],
                ['id' => 23, 'title' => 'Redis Streams Deep-dive Guide', 'type' => 'Bookmark', 'days' => 30, 'severity' => 'medium'],
                ['id' => 17, 'title' => 'Freelancing Project Container', 'type' => 'Project', 'days' => 21, 'severity' => 'low'],
                ['id' => 1, 'title' => 'Docker production config review', 'type' => 'Task', 'days' => 30, 'severity' => 'low'],
            ],
        ];
    }
}
