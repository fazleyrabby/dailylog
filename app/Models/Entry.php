<?php

namespace App\Models;

use App\Enums\BodyFormat;
use App\Enums\EntryType;
use App\Models\Concerns\OwnedByUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable([
    'user_id', 'type', 'title', 'body', 'body_format', 'status',
    'project_id', 'folder_id', 'pinned', 'captured_via', 'occurred_on', 'last_activity_at', 'archived_at',
])]
class Entry extends Model
{
    use HasFactory, OwnedByUser;

    protected function casts(): array
    {
        return [
            'type' => \App\Enums\EntryType::class,
            'body_format' => BodyFormat::class,
            'pinned' => 'boolean',
            'occurred_on' => 'date',
            'last_activity_at' => 'datetime',
            'archived_at' => 'datetime',
        ];
    }

    // Scopes for specific types
    public function scopeTasks(Builder $query): Builder { return $query->where('type', 'task'); }
    public function scopeNotes(Builder $query): Builder { return $query->where('type', 'note'); }
    public function scopeJournals(Builder $query): Builder { return $query->where('type', 'journal'); }
    public function scopeBookmarks(Builder $query): Builder { return $query->where('type', 'bookmark'); }
    public function scopeQuotes(Builder $query): Builder { return $query->where('type', 'quote'); }
    public function scopeResources(Builder $query): Builder { return $query->where('type', 'resource'); }
    public function scopeLearnings(Builder $query): Builder { return $query->where('type', 'learning'); }
    public function scopeIdeas(Builder $query): Builder { return $query->where('type', 'idea'); }
    public function scopeLabs(Builder $query): Builder { return $query->where('type', 'lab'); }
    public function scopeWallets(Builder $query): Builder { return $query->where('type', 'wallet'); }

    // Scopes for status filtering
    public function scopeActive(Builder $query): Builder { return $query->whereNull('archived_at'); }
    public function scopeArchived(Builder $query): Builder { return $query->whereNotNull('archived_at'); }

    // Core relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function folder(): BelongsTo
    {
        return $this->belongsTo(Folder::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'entry_tag');
    }

    // 1:1 Detail extensions
    public function taskDetails(): HasOne { return $this->hasOne(TaskDetails::class, 'entry_id'); }
    public function bookmarkDetails(): HasOne { return $this->hasOne(BookmarkDetails::class, 'entry_id'); }
    public function resourceDetails(): HasOne { return $this->hasOne(ResourceDetails::class, 'entry_id'); }
    public function learningDetails(): HasOne { return $this->hasOne(LearningDetails::class, 'entry_id'); }
    public function quoteDetails(): HasOne { return $this->hasOne(QuoteDetails::class, 'entry_id'); }
    public function walletDetails(): HasOne { return $this->hasOne(WalletDetails::class, 'entry_id'); }
    
    public function labItems(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(LabItem::class, 'entry_id');
    }

    public function walletTransactions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(WalletTransaction::class, 'wallet_id');
    }

    public function incomingTransfers(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(WalletTransaction::class, 'target_wallet_id');
    }

    // Self-referential graph links
    public function links(): BelongsToMany
    {
        return $this->belongsToMany(Entry::class, 'entry_links', 'source_id', 'target_id')
                    ->withPivot('relation');
    }

    public function backlinks(): BelongsToMany
    {
        return $this->belongsToMany(Entry::class, 'entry_links', 'target_id', 'source_id')
                    ->withPivot('relation');
    }
}
