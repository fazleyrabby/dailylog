<?php

namespace App\Http\Controllers;

use App\Domains\Entries\Actions\UpdateEntry;
use App\Enums\BodyFormat;
use App\Enums\EntryType;
use App\Models\Entry;
use App\Models\Folder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

class NotesController extends Controller
{
    public function __construct(private readonly UpdateEntry $updateEntry) {}

    public function index(): View
    {
        $entries = Entry::query()
            ->notes()
            ->active()
            ->with(['tags', 'project', 'backlinks'])
            ->orderByDesc('updated_at')
            ->get();

        $formatted = $entries->map(fn (Entry $entry) => $this->formatNote($entry))->toArray();

        $folders = cache()->remember('user:'.auth()->id().':folders', now()->addDay(), function () {
            return Folder::query()
                ->orderBy('name')
                ->get()
                ->map(fn (Folder $folder) => [
                    'id' => $folder->id,
                    'name' => $folder->name,
                    'parent_id' => $folder->parent_id,
                ])
                ->toArray();
        });

        return view('pages.notes', [
            'notes' => $formatted,
            'folders' => $folders,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'folder_id' => ['nullable', 'integer', 'exists:folders,id'],
        ]);

        $entry = Entry::create([
            'type' => EntryType::Note,
            'title' => 'Untitled Note',
            'body' => '# Untitled Note\n\nStart writing notes in markdown here...',
            'body_format' => BodyFormat::Markdown,
            'status' => 'active',
            'folder_id' => $validated['folder_id'] ?? null,
            'last_activity_at' => now(),
        ]);

        return response()->json([
            'note' => $this->formatNote($entry->load(['tags', 'project', 'backlinks'])),
        ], 201);
    }

    public function update(Request $request, Entry $entry): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string'],
            'body' => ['required', 'string'],
        ]);

        $entry = $this->updateEntry->execute($entry, $validated);

        return response()->json([
            'note' => $this->formatNote($entry->load(['tags', 'project', 'backlinks'])),
        ]);
    }

    public function move(Request $request, Entry $entry): JsonResponse
    {
        $validated = $request->validate([
            'folder_id' => ['nullable', 'integer', 'exists:folders,id'],
        ]);

        $entry->update(['folder_id' => $validated['folder_id'] ?? null]);

        return response()->json([
            'note' => $this->formatNote($entry->load(['tags', 'project', 'backlinks'])),
        ]);
    }

    public function destroy(Entry $entry): JsonResponse
    {
        $entry->update(['archived_at' => now()]);

        return response()->json([
            'success' => true,
        ]);
    }

    private function formatNote(Entry $entry): array
    {
        return [
            'id' => $entry->id,
            'folder_id' => $entry->folder_id,
            'title' => $entry->title ?? 'Untitled Note',
            'body' => $entry->body ?? '',
            'tags' => $entry->tags->pluck('name')->toArray(),
            'project' => $entry->project?->name ?? 'None',
            'updated' => $entry->updated_at ? $entry->updated_at->diffForHumans() : 'Just now',
            'month' => $entry->updated_at ? $entry->updated_at->format('F Y') : 'Drafts',
            'backlinks' => $entry->backlinks->pluck('title')->toArray(),
        ];
    }

    public function exportSingle(Entry $entry): Response
    {
        if ($entry->type !== EntryType::Note) {
            abort(404);
        }

        $filename = Str::slug($entry->title ?: 'untitled-note').'.md';

        return response($entry->body ?? '', 200, [
            'Content-Type' => 'text/markdown',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    public function exportBulk(): BinaryFileResponse
    {
        $zipPath = tempnam(sys_get_temp_dir(), 'notes_export').'.zip';
        $zip = new ZipArchive;

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            abort(500, 'Could not create ZIP file.');
        }

        $allFolders = Folder::all()->keyBy('id');
        $folderPaths = [];

        $getFolderPath = function ($folderId) use ($allFolders, &$folderPaths, &$getFolderPath) {
            if (! $folderId || ! isset($allFolders[$folderId])) {
                return '';
            }
            if (isset($folderPaths[$folderId])) {
                return $folderPaths[$folderId];
            }

            $folder = $allFolders[$folderId];
            $parentPath = $getFolderPath($folder->parent_id);
            $path = $parentPath ? $parentPath.'/'.$folder->name : $folder->name;
            $folderPaths[$folderId] = $path;

            return $path;
        };

        // Add empty folder structures first
        foreach ($allFolders as $folder) {
            $path = $getFolderPath($folder->id);
            $zip->addEmptyDir($path);
        }

        $notes = Entry::notes()->active()->get();
        $addedPaths = [];

        foreach ($notes as $note) {
            $folderPath = $getFolderPath($note->folder_id);
            $baseFilename = Str::slug($note->title ?: 'untitled-note');
            $filename = $baseFilename.'.md';
            $zipPathInZip = $folderPath ? $folderPath.'/'.$filename : $filename;

            $counter = 1;
            while (in_array($zipPathInZip, $addedPaths, true)) {
                $filename = $baseFilename.'-'.$counter.'.md';
                $zipPathInZip = $folderPath ? $folderPath.'/'.$filename : $filename;
                $counter++;
            }

            $addedPaths[] = $zipPathInZip;
            $zip->addFromString($zipPathInZip, $note->body ?? '');
        }

        $zip->close();

        return response()->download($zipPath, 'notes_backup_'.now()->format('Y-m-d').'.zip')->deleteFileAfterSend(true);
    }

    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:txt,md,zip'],
        ]);

        $file = $request->file('file');
        $ext = strtolower($file->getClientOriginalExtension());
        $count = 0;

        if (in_array($ext, ['md', 'txt'], true)) {
            $title = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $body = file_get_contents($file->getRealPath());

            Entry::create([
                'type' => EntryType::Note,
                'title' => $title,
                'body' => $body,
                'body_format' => BodyFormat::Markdown,
                'status' => 'active',
                'last_activity_at' => now(),
            ]);
            $count = 1;
        } elseif ($ext === 'zip') {
            $zip = new ZipArchive;
            if ($zip->open($file->getRealPath()) === true) {
                $folderMap = [];

                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $stat = $zip->statIndex($i);
                    $name = $stat['name'];

                    if (str_contains($name, '__MACOSX') || str_contains($name, '.DS_Store')) {
                        continue;
                    }

                    if (str_ends_with($name, '/')) {
                        $pathParts = explode('/', rtrim($name, '/'));
                        $parentId = null;
                        $currentPath = '';

                        foreach ($pathParts as $part) {
                            $currentPath = $currentPath ? $currentPath.'/'.$part : $part;

                            if (! isset($folderMap[$currentPath])) {
                                $folder = Folder::firstOrCreate([
                                    'name' => $part,
                                    'parent_id' => $parentId,
                                ]);
                                $folderMap[$currentPath] = $folder->id;
                            }

                            $parentId = $folderMap[$currentPath];
                        }

                        continue;
                    }

                    $fileExt = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                    if (in_array($fileExt, ['md', 'txt'], true)) {
                        $dirname = pathinfo($name, PATHINFO_DIRNAME);
                        $filename = pathinfo($name, PATHINFO_FILENAME);
                        $content = $zip->getFromIndex($i);

                        $folderId = null;
                        if ($dirname && $dirname !== '.') {
                            $pathParts = explode('/', $dirname);
                            $parentId = null;
                            $currentPath = '';
                            foreach ($pathParts as $part) {
                                $currentPath = $currentPath ? $currentPath.'/'.$part : $part;
                                if (! isset($folderMap[$currentPath])) {
                                    $folder = Folder::firstOrCreate([
                                        'name' => $part,
                                        'parent_id' => $parentId,
                                    ]);
                                    $folderMap[$currentPath] = $folder->id;
                                }
                                $parentId = $folderMap[$currentPath];
                            }
                            $folderId = $parentId;
                        }

                        Entry::create([
                            'type' => EntryType::Note,
                            'title' => $filename,
                            'body' => $content,
                            'body_format' => BodyFormat::Markdown,
                            'status' => 'active',
                            'folder_id' => $folderId,
                            'last_activity_at' => now(),
                        ]);
                        $count++;
                    }
                }
                $zip->close();
            }
        }

        // Clear folder cache so the UI updates
        cache()->forget('user:'.auth()->id().':folders');

        return response()->json([
            'success' => true,
            'message' => "Successfully imported {$count} note(s).",
        ]);
    }
}
