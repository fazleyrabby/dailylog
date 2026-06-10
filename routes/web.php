<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\BookmarksController;
use App\Http\Controllers\CaptureController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\InboxController;
use App\Http\Controllers\JournalController;
use App\Http\Controllers\LabController;
use App\Http\Controllers\LearningController;
use App\Http\Controllers\NotesController;
use App\Http\Controllers\Partials\SearchSuggestController;
use App\Http\Controllers\Partials\TagAutocompleteController;
use App\Http\Controllers\ProjectsController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\TasksController;
use App\Support\MockData;
use Illuminate\Support\Facades\Route;

Route::get('/health', HealthController::class)->name('health.check');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('auth.login');
    Route::post('/login', [LoginController::class, 'store'])->name('auth.attempt');
});

Route::post('/logout', [LoginController::class, 'destroy'])
    ->middleware('auth')
    ->name('auth.logout');

Route::middleware('auth')->group(function () {
    Route::post('/capture', [CaptureController::class, 'store'])->name('capture.store');
    Route::get('/partials/search/suggest', SearchSuggestController::class)->name('partials.search.suggest');
    Route::get('/partials/tags/autocomplete', TagAutocompleteController::class)->name('partials.tags.autocomplete');

    // Mocked-prototype pass-through. Real controllers replace these per Build Order.
    Route::get('/', fn () => redirect()->route('dashboard.index'));

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');
    Route::patch('/entries/{entry}/toggle-pin', [DashboardController::class, 'togglePin'])->name('entries.toggle-pin');
    Route::get('/inbox', [InboxController::class, 'index'])->name('inbox.index');
    Route::get('/search', [SearchController::class, 'index'])->name('search.index');

    Route::get('/tasks', [TasksController::class, 'index'])->name('tasks.index');
    Route::post('/tasks', [TasksController::class, 'store'])->name('tasks.store');
    Route::patch('/tasks/{entry}/toggle', [TasksController::class, 'toggle'])->name('tasks.toggle');
    Route::put('/tasks/{entry}', [TasksController::class, 'update'])->name('tasks.update');
    Route::delete('/tasks/{entry}', [TasksController::class, 'destroy'])->name('tasks.destroy');

    Route::get('/notes', [NotesController::class, 'index'])->name('notes.index');
    Route::post('/notes', [NotesController::class, 'store'])->name('notes.store');
    Route::put('/notes/{entry}', [NotesController::class, 'update'])->name('notes.update');
    Route::delete('/notes/{entry}', [NotesController::class, 'destroy'])->name('notes.destroy');

    Route::get('/journal', [JournalController::class, 'index'])->name('journal.index');
    Route::post('/journal', [JournalController::class, 'store'])->name('journal.store');
    Route::put('/journal/{entry}', [JournalController::class, 'update'])->name('journal.update');

    Route::get('/bookmarks', [BookmarksController::class, 'index'])->name('bookmarks.index');
    Route::post('/bookmarks', [BookmarksController::class, 'store'])->name('bookmarks.store');
    Route::patch('/bookmarks/{entry}/reviewed', [BookmarksController::class, 'markReviewed'])->name('bookmarks.reviewed');
    Route::delete('/bookmarks/{entry}', [BookmarksController::class, 'destroy'])->name('bookmarks.destroy');

    Route::get('/learning', [LearningController::class, 'index'])->name('learning.index');
    Route::patch('/learning/{entry}/complete-unit', [LearningController::class, 'completeUnit'])->name('learning.complete-unit');
    Route::get('/projects', [ProjectsController::class, 'index'])->name('projects.index');
    Route::post('/projects', [ProjectsController::class, 'store'])->name('projects.store');
    Route::put('/projects/{project}', [ProjectsController::class, 'update'])->name('projects.update');
    Route::delete('/projects/{project}', [ProjectsController::class, 'destroy'])->name('projects.destroy');
    Route::get('/quotes', fn () => view('pages.quotes', ['data' => MockData::all()]))->name('quotes.index');
    Route::get('/resources', fn () => view('pages.resources', ['data' => MockData::all()]))->name('resources.index');
    Route::get('/slipping', fn () => view('pages.slipping', ['data' => MockData::all()]))->name('slipping.index');
    Route::get('/settings', fn () => view('pages.settings', ['data' => MockData::all()]))->name('settings.profile');

    // Lab Routes
    Route::get('/lab', [LabController::class, 'index'])->name('lab.index');
    Route::post('/lab', [LabController::class, 'store'])->name('lab.store');
    Route::get('/lab/{entry}', [LabController::class, 'show'])->name('lab.show');
    Route::patch('/lab/{entry}', [LabController::class, 'update'])->name('lab.update');
    Route::delete('/lab/{entry}', [LabController::class, 'destroy'])->name('lab.destroy');
    Route::patch('/lab/{entry}/items', [LabController::class, 'updateItems'])->name('lab.items.update');
    Route::post('/lab/items/{item}/graduate', [LabController::class, 'graduate'])->name('lab.items.graduate');
});
