<?php

namespace App\Http\Controllers;

use App\Models\Folder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FolderController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'parent_id' => ['nullable', 'integer', 'exists:folders,id'],
        ]);

        $folder = Folder::create($validated);

        return response()->json([
            'folder' => $this->formatFolder($folder),
        ], 201);
    }

    public function update(Request $request, Folder $folder): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'parent_id' => ['nullable', 'integer', 'exists:folders,id'],
        ]);

        $folder->update($validated);

        return response()->json([
            'folder' => $this->formatFolder($folder),
        ]);
    }

    public function destroy(Folder $folder): JsonResponse
    {
        // Detach notes so they fall back to "Unfiled" instead of being deleted.
        $folder->entries()->update(['folder_id' => null]);
        $folder->delete();

        return response()->json(['success' => true]);
    }

    private function formatFolder(Folder $folder): array
    {
        return [
            'id' => $folder->id,
            'name' => $folder->name,
            'parent_id' => $folder->parent_id,
        ];
    }
}
