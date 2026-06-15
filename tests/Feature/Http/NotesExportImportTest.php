<?php

namespace Tests\Feature\Http;

use App\Enums\EntryType;
use App\Models\Entry;
use App\Models\Folder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;
use ZipArchive;

class NotesExportImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_export_or_import_notes(): void
    {
        $entry = Entry::factory()->type(EntryType::Note)->create();

        $this->get(route('notes.export.single', $entry))
            ->assertRedirect(route('auth.login'));

        $this->get(route('notes.export.bulk'))
            ->assertRedirect(route('auth.login'));

        $this->post(route('notes.import'))
            ->assertRedirect(route('auth.login'));
    }

    public function test_export_single_downloads_markdown_file(): void
    {
        $user = User::factory()->create();
        $entry = Entry::factory()->for($user)->type(EntryType::Note)->create([
            'title' => 'Sample Note',
            'body' => '# Sample Header',
        ]);

        $response = $this->actingAs($user)
            ->get(route('notes.export.single', $entry));

        $response->assertOk();
        $response->assertHeader('Content-Disposition', 'attachment; filename="sample-note.md"');
        $this->assertEquals('# Sample Header', $response->content());
    }

    public function test_export_bulk_downloads_zip_file(): void
    {
        $user = User::factory()->create();

        $folder = Folder::create([
            'user_id' => $user->id,
            'name' => 'Tech',
        ]);

        Entry::factory()->for($user)->type(EntryType::Note)->create([
            'title' => 'Dev Log',
            'body' => 'Developer log content',
            'folder_id' => $folder->id,
        ]);

        Entry::factory()->for($user)->type(EntryType::Note)->create([
            'title' => 'Inbox Note',
            'body' => 'Inbox content',
            'folder_id' => null,
        ]);

        $response = $this->actingAs($user)
            ->get(route('notes.export.bulk'));

        $response->assertOk();
        $response->assertHeader('Content-Disposition', 'attachment; filename=notes_backup_'.now()->format('Y-m-d').'.zip');
    }

    public function test_import_single_markdown_file_creates_note(): void
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->createWithContent('imported-note.md', '# Imported Title\n\nSome imported body.');

        $this->actingAs($user)
            ->post(route('notes.import'), [
                'file' => $file,
            ])
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Successfully imported 1 note(s).',
            ]);

        $this->assertDatabaseHas('entries', [
            'user_id' => $user->id,
            'type' => 'note',
            'title' => 'imported-note',
            'body' => '# Imported Title\n\nSome imported body.',
        ]);
    }

    public function test_import_zip_file_recreates_folders_and_notes(): void
    {
        $user = User::factory()->create();

        // Create a temporary zip file
        $zipPath = tempnam(sys_get_temp_dir(), 'test_zip').'.zip';
        $zip = new ZipArchive;
        $zip->open($zipPath, ZipArchive::CREATE);
        $zip->addEmptyDir('Personal');
        $zip->addFromString('Personal/Ideas.md', 'Some personal ideas');
        $zip->addFromString('Work Note.txt', 'Direct work note');
        $zip->close();

        $file = new UploadedFile($zipPath, 'backup.zip', 'application/zip', null, true);

        $this->actingAs($user)
            ->post(route('notes.import'), [
                'file' => $file,
            ])
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Successfully imported 2 note(s).',
            ]);

        // Check folder exists
        $this->assertDatabaseHas('folders', [
            'user_id' => $user->id,
            'name' => 'Personal',
        ]);

        $folder = Folder::where('name', 'Personal')->first();

        // Check notes exist
        $this->assertDatabaseHas('entries', [
            'user_id' => $user->id,
            'type' => 'note',
            'title' => 'Ideas',
            'body' => 'Some personal ideas',
            'folder_id' => $folder->id,
        ]);

        $this->assertDatabaseHas('entries', [
            'user_id' => $user->id,
            'type' => 'note',
            'title' => 'Work Note',
            'body' => 'Direct work note',
            'folder_id' => null,
        ]);

        @unlink($zipPath);
    }
}
