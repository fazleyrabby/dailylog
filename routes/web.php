<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\BookmarksController;
use App\Http\Controllers\CaptureController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\InboxController;
use App\Http\Controllers\JournalController;
use App\Http\Controllers\LabController;
use App\Http\Controllers\LearningController;
use App\Http\Controllers\FolderController;
use App\Http\Controllers\NotesController;
use App\Http\Controllers\Partials\SearchSuggestController;
use App\Http\Controllers\Partials\TagAutocompleteController;
use App\Http\Controllers\ProjectsController;
use App\Http\Controllers\QuotesController;
use App\Http\Controllers\ResourcesController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SlippingController;
use App\Http\Controllers\TasksController;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\SpeedtestController;
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
    Route::patch('/inbox/{entry}/triage', [InboxController::class, 'triage'])->name('inbox.triage');
    Route::get('/search', [SearchController::class, 'index'])->name('search.index');

    Route::get('/tasks', [TasksController::class, 'index'])->name('tasks.index');
    Route::post('/tasks', [TasksController::class, 'store'])->name('tasks.store');
    Route::patch('/tasks/{entry}/toggle', [TasksController::class, 'toggle'])->name('tasks.toggle');
    Route::put('/tasks/{entry}', [TasksController::class, 'update'])->name('tasks.update');
    Route::delete('/tasks/{entry}', [TasksController::class, 'destroy'])->name('tasks.destroy');

    Route::get('/notes', [NotesController::class, 'index'])->name('notes.index');
    Route::post('/notes', [NotesController::class, 'store'])->name('notes.store');
    Route::put('/notes/{entry}', [NotesController::class, 'update'])->name('notes.update');
    Route::patch('/notes/{entry}/move', [NotesController::class, 'move'])->name('notes.move');
    Route::delete('/notes/{entry}', [NotesController::class, 'destroy'])->name('notes.destroy');

    Route::post('/folders', [FolderController::class, 'store'])->name('folders.store');
    Route::put('/folders/{folder}', [FolderController::class, 'update'])->name('folders.update');
    Route::delete('/folders/{folder}', [FolderController::class, 'destroy'])->name('folders.destroy');

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
    Route::get('/quotes', [QuotesController::class, 'index'])->name('quotes.index');
    Route::post('/quotes', [QuotesController::class, 'store'])->name('quotes.store');
    Route::put('/quotes/{entry}', [QuotesController::class, 'update'])->name('quotes.update');
    Route::delete('/quotes/{entry}', [QuotesController::class, 'destroy'])->name('quotes.destroy');
    Route::get('/resources', [ResourcesController::class, 'index'])->name('resources.index');
    Route::post('/resources', [ResourcesController::class, 'store'])->name('resources.store');
    Route::put('/resources/{entry}', [ResourcesController::class, 'update'])->name('resources.update');
    Route::delete('/resources/{entry}', [ResourcesController::class, 'destroy'])->name('resources.destroy');
    Route::get('/slipping', [SlippingController::class, 'index'])->name('slipping.index');
    Route::post('/slipping/{snapshot}/resume', [SlippingController::class, 'resume'])->name('slipping.resume');
    Route::post('/slipping/{snapshot}/schedule', [SlippingController::class, 'schedule'])->name('slipping.schedule');
    Route::post('/slipping/{snapshot}/snooze', [SlippingController::class, 'snooze'])->name('slipping.snooze');
    Route::post('/slipping/{snapshot}/let-go', [SlippingController::class, 'letGo'])->name('slipping.let-go');
    Route::get('/settings', fn () => view('pages.settings', [
        'data' => MockData::all(),
        'lastBackupAt' => cache('supabase_backup_last_at'),
    ]))->name('settings.profile');
    Route::post('/settings/backup-supabase', [BackupController::class, 'toSupabase'])->name('settings.backup.supabase');

    // Lab Routes
    Route::get('/lab', [LabController::class, 'index'])->name('lab.index');
    Route::post('/lab', [LabController::class, 'store'])->name('lab.store');
    Route::get('/lab/{entry}', [LabController::class, 'show'])->name('lab.show');
    Route::patch('/lab/{entry}', [LabController::class, 'update'])->name('lab.update');
    Route::delete('/lab/{entry}', [LabController::class, 'destroy'])->name('lab.destroy');
    Route::patch('/lab/{entry}/items', [LabController::class, 'updateItems'])->name('lab.items.update');
    Route::post('/lab/items/{item}/graduate', [LabController::class, 'graduate'])->name('lab.items.graduate');

    // Wallet Routes
    Route::get('/wallet', [WalletController::class, 'index'])->name('wallet.index');
    Route::post('/wallet', [WalletController::class, 'storeWallet'])->name('wallet.store');
    Route::put('/wallet/{entry}', [WalletController::class, 'updateWallet'])->name('wallet.update');
    Route::post('/wallet/transaction', [WalletController::class, 'storeTransaction'])->name('wallet.transaction.store');
    Route::put('/wallet/transaction/{transaction}', [WalletController::class, 'updateTransaction'])->name('wallet.transaction.update');
    Route::delete('/wallet/transaction/{transaction}', [WalletController::class, 'destroyTransaction'])->name('wallet.transaction.destroy');
    Route::delete('/wallet/{entry}', [WalletController::class, 'destroyWallet'])->name('wallet.destroy');
    Route::post('/wallet/recurring', [WalletController::class, 'storeRecurring'])->name('wallet.recurring.store');
    Route::delete('/wallet/recurring/{recurring}', [WalletController::class, 'destroyRecurring'])->name('wallet.recurring.destroy');
    Route::post('/wallet/recurring/{recurring}/post', [WalletController::class, 'postRecurring'])->name('wallet.recurring.post');
    Route::post('/wallet/budget', [WalletController::class, 'storeBudget'])->name('wallet.budget.store');
    Route::post('/wallet/settings', [WalletController::class, 'updateCurrencySettings'])->name('wallet.settings.update');

    // Speedtest Routes
    Route::get('/speedtest', [SpeedtestController::class, 'index'])->name('speedtest.index');
    Route::get('/speedtest/ping', [SpeedtestController::class, 'ping'])->name('speedtest.ping');
    Route::get('/speedtest/download', [SpeedtestController::class, 'download'])->name('speedtest.download');
    Route::post('/speedtest/upload', [SpeedtestController::class, 'upload'])->name('speedtest.upload');
    Route::post('/speedtest/log', [SpeedtestController::class, 'logResult'])->name('speedtest.log');
    Route::get('/speedtest/history', [SpeedtestController::class, 'history'])->name('speedtest.history');
});
