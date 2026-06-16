<?php

use App\Enums\EntryType;
use App\Models\Entry;
use App\Models\Folder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;

uses(RefreshDatabase::class);

test('guest cannot export or import notes', function () {
    $entry = Entry::factory()->type(EntryType::Note)->create();

    $this->get(route('notes.export.single', $entry))
        ->assertRedirect(route('auth.login'));

    $this->get(route('notes.export.bulk'))
        ->assertRedirect(route('auth.login'));

    $this->post(route('notes.import'))
        ->assertRedirect(route('auth.login'));
});

test('export single downloads markdown file', function () {
    $user = User::factory()->create();
    $entry = Entry::factory()->for($user)->type(EntryType::Note)->create([
        'title' => 'Sample Note',
        'body' => '# Sample Header',
    ]);

    $response = $this->actingAs($user)
        ->get(route('notes.export.single', $entry));

    $response->assertOk();
    $response->assertHeader('Content-Disposition', 'attachment; filename="sample-note.md"');
    expect($response->content())->toEqual('# Sample Header');
});

test('export bulk downloads zip file', function () {
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
});

test('import single markdown file creates note', function () {
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
});

test('import zip file recreates folders and notes', function () {
    $user = User::factory()->create();

    // Create a temporary zip file
    $zipPath = sys_get_temp_dir().DIRECTORY_SEPARATOR.'test_zip_'.uniqid().'.zip';
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
});
