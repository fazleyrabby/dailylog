<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Two-way sync between the VPS DailyLOG database (type=note entries) and macOS
 * Apple Notes, scoped to a single Apple Notes folder.
 *
 * - Remote notes are read/written over SSH via `php artisan tinker` on the VPS,
 *   so whichever DB the VPS app is configured with is used (not the local one).
 * - Apple Notes are read/written via JXA (`osascript -l JavaScript`).
 * - Matching is by a hidden marker line `dailylog-id:<ID>` appended to the
 *   Apple note body. New notes on either side get created on the other.
 * - When both sides changed, the newer (by modified time) wins. If normalized
 *   content is already equal, the pair is skipped (prevents write ping-pong).
 *
 * Dry-run by default. Pass --apply to actually write.
 */
#[Signature('notes:sync-apple
    {--ssh-host=signalstack : SSH host alias for the VPS}
    {--remote-dir= : Absolute path to the DailyLOG app on the VPS (required to read DB)}
    {--folder=DailyLOG : Apple Notes folder to sync}
    {--user-id=1 : user_id assigned to notes created in the DB from Apple}
    {--apply : Write changes. Without this flag the command only reports a plan}')]
#[Description('Two-way sync VPS DailyLOG notes <-> Apple Notes (dry-run unless --apply)')]
class SyncAppleNotes extends Command
{
    private const MARKER = 'dailylog-id:';

    public function handle(): int
    {
        $sshHost = (string) $this->option('ssh-host');
        $remoteDir = (string) $this->option('remote-dir');
        $folder = (string) $this->option('folder');
        $userId = (int) $this->option('user-id');
        $apply = (bool) $this->option('apply');

        if ($remoteDir === '') {
            $this->error('--remote-dir is required (absolute path to DailyLOG on the VPS).');

            return self::FAILURE;
        }

        $this->components->info(($apply ? 'APPLY' : 'DRY-RUN').": syncing VPS notes <-> Apple Notes folder '{$folder}'");

        // 1. Pull both sides.
        try {
            $dbNotes = $this->fetchDbNotes($sshHost, $remoteDir);
        } catch (\RuntimeException $e) {
            $this->error('VPS read failed: '.$e->getMessage());

            return self::FAILURE;
        }

        try {
            $appleNotes = $this->fetchAppleNotes($folder);
        } catch (\RuntimeException $e) {
            $this->error('Apple Notes read failed: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->line(sprintf('  Found %d DB note(s), %d Apple note(s) in folder.', count($dbNotes), count($appleNotes)));

        // 2. Index Apple notes by the embedded DailyLOG id.
        $appleById = [];   // dailylog id => apple note
        $appleNew = [];    // apple notes with no marker (created on Apple side)
        foreach ($appleNotes as $a) {
            if ($a['dl_id'] !== null) {
                $appleById[$a['dl_id']] = $a;
            } else {
                $appleNew[] = $a;
            }
        }

        // 3. Reconcile.
        $appleOps = [];    // {ref, apple_id|null, title, body} -> create/update Apple
        $dbOps = [];       // {id|null, ref, title, body, user_id} -> update/create DB
        $skipped = 0;

        foreach ($dbNotes as $n) {
            $apple = $appleById[$n['id']] ?? null;

            if ($apple === null) {
                // New on DB side -> create Apple note.
                $appleOps[] = [
                    'ref' => 'db:'.$n['id'],
                    'apple_id' => null,
                    'title' => $n['title'],
                    'body' => $this->buildAppleBody($n['title'], $n['body'], $n['id']),
                ];

                continue;
            }

            unset($appleById[$n['id']]); // consumed

            if ($this->normalize($n['body']) === $this->normalize($apple['body'])
                && trim((string) $n['title']) === trim((string) $apple['name'])) {
                $skipped++;

                continue;
            }

            // Both have it but differ -> newer wins.
            $dbTime = strtotime((string) $n['updated_at']) ?: 0;
            $appleTime = strtotime((string) $apple['modified']) ?: 0;

            if ($dbTime >= $appleTime) {
                $appleOps[] = [
                    'ref' => 'db:'.$n['id'],
                    'apple_id' => $apple['id'],
                    'title' => $n['title'],
                    'body' => $this->buildAppleBody($n['title'], $n['body'], $n['id']),
                ];
            } else {
                $dbOps[] = [
                    'id' => $n['id'],
                    'ref' => 'apple:'.$apple['id'],
                    'title' => $this->appleTitle($apple),
                    'body' => $this->stripAppleBody($apple['body']),
                    'user_id' => $userId,
                ];
            }
        }

        // Apple notes that were never matched to a DB id but carried a marker:
        // the DB row is gone. Leave them alone (report only).
        $orphans = count($appleById);

        // New on Apple side (no marker) -> create in DB, then write marker back.
        foreach ($appleNew as $a) {
            $dbOps[] = [
                'id' => null,
                'ref' => 'apple:'.$a['id'],
                'title' => $this->appleTitle($a),
                'body' => $this->stripAppleBody($a['body']),
                'user_id' => $userId,
            ];
        }

        // 4. Report plan.
        $this->newLine();
        $this->line('Plan:');
        $this->line(sprintf('  -> Apple create : %d', count(array_filter($appleOps, fn ($o) => $o['apple_id'] === null))));
        $this->line(sprintf('  -> Apple update : %d', count(array_filter($appleOps, fn ($o) => $o['apple_id'] !== null))));
        $this->line(sprintf('  -> DB create    : %d', count(array_filter($dbOps, fn ($o) => $o['id'] === null))));
        $this->line(sprintf('  -> DB update    : %d', count(array_filter($dbOps, fn ($o) => $o['id'] !== null))));
        $this->line(sprintf('     unchanged    : %d', $skipped));
        if ($orphans > 0) {
            $this->warn(sprintf('  %d Apple note(s) reference a DB id that no longer exists (left untouched).', $orphans));
        }

        if (! $apply) {
            $this->newLine();
            $this->components->info('Dry-run only. Re-run with --apply to write changes.');

            return self::SUCCESS;
        }

        if (empty($appleOps) && empty($dbOps)) {
            $this->components->info('Nothing to write.');

            return self::SUCCESS;
        }

        // 5. Write DB first so new rows get ids, then map ids onto Apple notes.
        $newDbIds = [];
        if (! empty($dbOps)) {
            $newDbIds = $this->writeDbNotes($sshHost, $remoteDir, $dbOps);
            $this->line(sprintf('  Wrote %d DB op(s); %d new row(s).', count($dbOps), count($newDbIds)));
        }

        // For Apple-originated new DB rows, write the marker back into the Apple note.
        foreach ($dbOps as $op) {
            if ($op['id'] === null && isset($newDbIds[$op['ref']])) {
                $appleId = substr($op['ref'], strlen('apple:'));
                $appleOps[] = [
                    'ref' => $op['ref'],
                    'apple_id' => $appleId,
                    'title' => $op['title'],
                    'body' => $this->buildAppleBody($op['title'], $op['body'], $newDbIds[$op['ref']]),
                ];
            }
        }

        if (! empty($appleOps)) {
            $this->writeAppleNotes($folder, $appleOps);
            $this->line(sprintf('  Wrote %d Apple op(s).', count($appleOps)));
        }

        $this->components->info('Sync complete.');

        return self::SUCCESS;
    }

    /**
     * Read note entries from the VPS DB over SSH.
     *
     * @return list<array{id:int,title:?string,body:?string,updated_at:?string}>
     */
    private function fetchDbNotes(string $sshHost, string $remoteDir): array
    {
        $php = <<<'PHP'
        $notes = \App\Models\Entry::withoutGlobalScopes()->where('type', 'note')->get();
        echo "<<<DLJSON";
        echo $notes->map(fn ($n) => [
            'id' => $n->id,
            'title' => $n->title,
            'body' => $n->body,
            'updated_at' => optional($n->updated_at)->toIso8601String(),
        ])->values()->toJson();
        echo "DLJSON>>>";
        PHP;

        $out = $this->runRemoteTinker($sshHost, $remoteDir, $php);

        return $this->extractJson($out);
    }

    /**
     * Create/update note entries on the VPS DB over SSH.
     * Returns map of ref => new id for created rows.
     *
     * @param  list<array{id:?int,ref:string,title:string,body:string,user_id:int}>  $ops
     * @return array<string,int>
     */
    private function writeDbNotes(string $sshHost, string $remoteDir, array $ops): array
    {
        $payload = base64_encode(json_encode($ops));

        $php = <<<PHP
        \$ops = json_decode(base64_decode('{$payload}'), true);
        \$new = [];
        foreach (\$ops as \$op) {
            if (! empty(\$op['id'])) {
                \$e = \App\Models\Entry::withoutGlobalScopes()->find(\$op['id']);
                if (\$e) {
                    \$e->title = \$op['title'];
                    \$e->body = \$op['body'];
                    \$e->save();
                }
            } else {
                \$e = \App\Models\Entry::create([
                    'user_id' => \$op['user_id'],
                    'type' => 'note',
                    'title' => \$op['title'],
                    'body' => \$op['body'],
                    'body_format' => 'markdown',
                    'status' => 'active',
                ]);
                \$new[\$op['ref']] = \$e->id;
            }
        }
        echo "<<<DLJSON";
        echo json_encode(\$new);
        echo "DLJSON>>>";
        PHP;

        $out = $this->runRemoteTinker($sshHost, $remoteDir, $php);
        $decoded = json_decode($this->extractRaw($out), true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Pipe PHP into `php artisan tinker` on the VPS. PHP is base64-encoded so it
     * survives both the local and remote shell unscathed.
     */
    private function runRemoteTinker(string $sshHost, string $remoteDir, string $php): string
    {
        $b64 = base64_encode($php);
        $remote = sprintf(
            'cd %s && echo %s | base64 --decode | php artisan tinker',
            escapeshellarg($remoteDir),
            escapeshellarg($b64),
        );
        $cmd = 'ssh '.escapeshellarg($sshHost).' '.escapeshellarg($remote).' 2>&1';

        $out = shell_exec($cmd);
        if ($out === null) {
            throw new \RuntimeException('ssh produced no output (host unreachable?).');
        }
        if (! str_contains($out, '<<<DLJSON')) {
            throw new \RuntimeException(trim($out));
        }

        return $out;
    }

    /**
     * Read Apple Notes from the given folder via JXA.
     *
     * @return list<array{id:string,name:string,body:string,modified:string,dl_id:?int}>
     */
    private function fetchAppleNotes(string $folder): array
    {
        $jxa = <<<'JXA'
        function run(argv) {
          const folderName = argv[0];
          const app = Application('Notes');
          const out = [];
          const folders = app.folders.whose({ name: folderName });
          if (folders.length === 0) { return JSON.stringify(out); }
          const notes = folders[0].notes;
          for (let i = 0; i < notes.length; i++) {
            const n = notes[i];
            out.push({
              id: n.id(),
              name: n.name(),
              body: n.body(),
              modified: n.modificationDate().toISOString(),
            });
          }
          return JSON.stringify(out);
        }
        JXA;

        $raw = $this->runJxa($jxa, [$folder]);
        $notes = json_decode($raw, true);
        if (! is_array($notes)) {
            throw new \RuntimeException('Could not parse Apple Notes output: '.trim($raw));
        }

        return array_map(function (array $n): array {
            $n['dl_id'] = $this->parseMarker($n['body'] ?? '');

            return $n;
        }, $notes);
    }

    /**
     * Create/update Apple notes via JXA. Each op: {apple_id|null, body}.
     *
     * @param  list<array{ref:string,apple_id:?string,title:string,body:string}>  $ops
     */
    private function writeAppleNotes(string $folder, array $ops): void
    {
        $payload = base64_encode(json_encode($ops));

        $jxa = <<<JXA
        function run() {
          const folderName = '{$folder}';
          const ops = JSON.parse(\$.NSString.alloc.initWithDataEncoding(
            \$.NSData.alloc.initWithBase64EncodedStringOptions('{$payload}', 0), 4).js);
          const app = Application('Notes');
          const folders = app.folders.whose({ name: folderName });
          let folder;
          if (folders.length === 0) {
            folder = app.make({ new: 'folder', withProperties: { name: folderName } });
          } else {
            folder = folders[0];
          }
          for (let i = 0; i < ops.length; i++) {
            const op = ops[i];
            if (op.apple_id) {
              const n = app.notes.byId(op.apple_id);
              n.body = op.body;
            } else {
              app.make({ new: 'note', at: folder, withProperties: { body: op.body } });
            }
          }
          return 'ok';
        }
        JXA;

        $this->runJxa($jxa, []);
    }

    /**
     * Run a JXA script, passing argv, returning stdout.
     *
     * @param  list<string>  $argv
     */
    private function runJxa(string $jxa, array $argv): string
    {
        $tmp = tempnam(sys_get_temp_dir(), 'jxa').'.js';
        file_put_contents($tmp, $jxa);

        try {
            $cmd = 'osascript -l JavaScript '.escapeshellarg($tmp);
            foreach ($argv as $a) {
                $cmd .= ' '.escapeshellarg($a);
            }
            $cmd .= ' 2>&1';
            $out = shell_exec($cmd);
        } finally {
            @unlink($tmp);
        }

        if ($out === null) {
            throw new \RuntimeException('osascript produced no output.');
        }

        return $out;
    }

    /**
     * Extract and decode the JSON array wrapped in <<<DLJSON ... DLJSON>>>.
     *
     * @return list<array<string,mixed>>
     */
    private function extractJson(string $out): array
    {
        $decoded = json_decode($this->extractRaw($out), true);
        if (! is_array($decoded)) {
            throw new \RuntimeException('Malformed JSON from VPS: '.trim($out));
        }

        return $decoded;
    }

    private function extractRaw(string $out): string
    {
        if (preg_match('/<<<DLJSON(.*)DLJSON>>>/s', $out, $m) !== 1) {
            throw new \RuntimeException('No DLJSON marker in VPS output: '.trim($out));
        }

        return trim($m[1]);
    }

    private function parseMarker(string $body): ?int
    {
        if (preg_match('/'.preg_quote(self::MARKER, '/').'(\d+)/', $body, $m) === 1) {
            return (int) $m[1];
        }

        return null;
    }

    /**
     * Build an Apple note HTML body: title as first line, content, hidden marker.
     */
    private function buildAppleBody(?string $title, ?string $body, int $dlId): string
    {
        $titleHtml = '<div><b>'.$this->esc((string) $title).'</b></div>';
        $bodyHtml = nl2br($this->esc((string) $body));
        $marker = '<div>'.self::MARKER.$dlId.'</div>';

        return $titleHtml.'<div>'.$bodyHtml.'</div>'.$marker;
    }

    /**
     * Apple note name; falls back to first body line if name is empty.
     */
    private function appleTitle(array $apple): string
    {
        $name = trim((string) ($apple['name'] ?? ''));
        if ($name !== '') {
            return $name;
        }
        $text = $this->stripAppleBody((string) ($apple['body'] ?? ''));

        return trim(strtok($text, "\n")) ?: 'Untitled';
    }

    /**
     * Apple body (HTML) -> plain text, with the title line and marker removed.
     */
    private function stripAppleBody(string $html): string
    {
        $text = preg_replace('/<\s*br\s*\/?>/i', "\n", $html) ?? $html;
        $text = preg_replace('/<\/(div|p|h[1-6])>/i', "\n", $text) ?? $text;
        $text = strip_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5);
        // Drop the marker line.
        $text = preg_replace('/^.*'.preg_quote(self::MARKER, '/').'\d+.*$/m', '', $text) ?? $text;
        // Drop the first non-empty line (the title we prepended).
        $lines = explode("\n", $text);
        while ($lines !== [] && trim($lines[0]) === '') {
            array_shift($lines);
        }
        if ($lines !== []) {
            array_shift($lines);
        }

        return trim(implode("\n", $lines));
    }

    /**
     * Normalize text for equality comparison (strip tags, marker, whitespace).
     */
    private function normalize(?string $value): string
    {
        $text = strip_tags(preg_replace('/<\s*br\s*\/?>/i', "\n", (string) $value) ?? (string) $value);
        $text = preg_replace('/'.preg_quote(self::MARKER, '/').'\d+/', '', $text) ?? $text;
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5);

        return preg_replace('/\s+/', ' ', trim($text)) ?? '';
    }

    private function esc(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}
