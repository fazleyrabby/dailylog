<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Artisan;

class BackupController extends Controller
{
    /**
     * Trigger an on-demand mirror of the local database to Supabase.
     * Runs synchronously — the dataset is small and there is no queue worker.
     */
    public function toSupabase(): RedirectResponse
    {
        $exit = Artisan::call('backup:supabase');

        if ($exit === 0) {
            return back()->with('status', 'Backup to Supabase completed.');
        }

        return back()->with('error', 'Backup failed: '.trim(Artisan::output()));
    }
}
