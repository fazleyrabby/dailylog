<?php

namespace App\Enums;

enum EntryType: string
{
    case Task = 'task';
    case Note = 'note';
    case Journal = 'journal';
    case Bookmark = 'bookmark';
    case Quote = 'quote';
    case Resource = 'resource';
    case Learning = 'learning';
    case Idea = 'idea';
    case Lab = 'lab';

    public function label(): string
    {
        return match ($this) {
            self::Task => 'Task',
            self::Note => 'Note',
            self::Journal => 'Journal',
            self::Bookmark => 'Bookmark',
            self::Quote => 'Quote',
            self::Resource => 'Resource',
            self::Learning => 'Learning',
            self::Idea => 'Idea',
            self::Lab => 'Lab',
        };
    }

    public function defaultStatus(): string
    {
        return match ($this) {
            self::Task => 'open',
            self::Note => 'active',
            self::Journal => 'active',
            self::Bookmark => 'active',
            self::Quote => 'active',
            self::Resource => 'to_consume',
            self::Learning => 'active',
            self::Idea => 'spark',
            self::Lab => 'active',
        };
    }

    public function hasExtension(): bool
    {
        return in_array($this, [self::Task, self::Bookmark, self::Resource, self::Learning, self::Quote], true);
    }
}
