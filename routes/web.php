<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CaptureController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\InboxController;
use App\Http\Controllers\JournalController;
use App\Http\Controllers\NotesController;
use App\Http\Controllers\Partials\SearchSuggestController;
use App\Http\Controllers\Partials\TagAutocompleteController;
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

    Route::get('/dashboard', fn () => view('pages.dashboard', ['data' => MockData::all()]))->name('dashboard.index');
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
    Route::get('/bookmarks', fn () => view('pages.bookmarks', ['data' => MockData::all()]))->name('bookmarks.index');
    Route::get('/learning', fn () => view('pages.learning', ['data' => MockData::all()]))->name('learning.index');
    Route::get('/projects', fn () => view('pages.projects', ['data' => MockData::all()]))->name('projects.index');
    Route::get('/quotes', fn () => view('pages.quotes', ['data' => MockData::all()]))->name('quotes.index');
    Route::get('/resources', fn () => view('pages.resources', ['data' => MockData::all()]))->name('resources.index');
    Route::get('/slipping', fn () => view('pages.slipping', ['data' => MockData::all()]))->name('slipping.index');
    Route::get('/settings', fn () => view('pages.settings', ['data' => MockData::all()]))->name('settings.profile');
});
