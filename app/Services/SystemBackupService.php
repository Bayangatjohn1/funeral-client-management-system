<?php

namespace App\Services;

use App\Models\SystemBackup;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\Process\Process;
use ZipArchive;

class SystemBackupService
{
    public function create(string $type = 'manual', ?int $actorId = null): SystemBackup
    {
        $lock = Cache::lock('system-backup:create', 900);

        if (! $lock->get()) {
            throw new RuntimeException('A backup is already being created. Please wait for it to finish.');
        }

        try {
            return $this->createBackup($type, $actorId);
        } finally {
            $lock->release();
        }
    }

    private function createBackup(string $type, ?int $actorId): SystemBackup
    {
        $backup = SystemBackup::create([
            'created_by' => $actorId,
            'type' => $type,
            'status' => 'running',
            'disk' => config('backup.disk'),
            'expires_at' => now()->addDays(config('backup.retention_days')),
        ]);

        $work = storage_path('app/private/backup-work/'.$backup->id);
        File::ensureDirectoryExists($work);

        try {
            $dump = $work.'/database.sql';
            $this->dumpDatabase($dump);

            $manifest = [
                'backup_id' => $backup->id,
                'created_at' => now()->toIso8601String(),
                'app_env' => app()->environment(),
                'database' => DB::getDatabaseName(),
                'migrations' => DB::table('migrations')->count(),
                'includes_private_files' => true,
            ];
            File::put($work.'/manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            $zipPath = $work.'/backup.zip';
            $this->makeArchive($zipPath, $dump, $work.'/manifest.json');

            $encrypted = Crypt::encryptString(File::get($zipPath));
            $relativePath = trim(config('backup.directory'), '/').'/'.now()->format('Y/m').'/funeral-system-'.$backup->id.'-'.now()->format('Ymd-His').'.backup';
            Storage::disk(config('backup.disk'))->put($relativePath, $encrypted);
            $checksum = hash('sha256', $encrypted);

            $backup->update([
                'status' => 'completed',
                'path' => $relativePath,
                'size_bytes' => strlen($encrypted),
                'checksum' => $checksum,
                'metadata' => $manifest,
            ]);

            return $this->verify($backup);
        } catch (\Throwable $e) {
            $backup->update(['status' => 'failed', 'failure_message' => mb_substr($e->getMessage(), 0, 4000)]);
            throw $e;
        } finally {
            File::deleteDirectory($work);
        }
    }

    public function verify(SystemBackup $backup): SystemBackup
    {
        if (!$backup->path || !Storage::disk($backup->disk)->exists($backup->path)) {
            throw new RuntimeException('Backup file is missing.');
        }

        $encrypted = Storage::disk($backup->disk)->get($backup->path);
        if (!hash_equals((string) $backup->checksum, hash('sha256', $encrypted))) {
            throw new RuntimeException('Backup checksum verification failed.');
        }

        $zipData = Crypt::decryptString($encrypted);
        $temp = tempnam(sys_get_temp_dir(), 'backup-verify-');
        File::put($temp, $zipData);
        $zip = new ZipArchive();
        $opened = $zip->open($temp) === true;
        $valid = $opened && $zip->locateName('database.sql') !== false && $zip->locateName('manifest.json') !== false;
        if ($opened) $zip->close();
        File::delete($temp);

        if (!$valid) throw new RuntimeException('Backup archive is unreadable or incomplete.');

        $hasPendingRestore = $backup->restoreRequests()
            ->whereIn('status', ['pending_validation', 'validated_pending_execution', 'executing'])
            ->exists();

        $backup->update([
            'status' => $hasPendingRestore ? 'restore_pending' : 'verified',
            'verified_at' => now(),
            'failure_message' => null,
        ]);
        return $backup->refresh();
    }

    public function deleteExpired(): int
    {
        $count = 0;
        SystemBackup::whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->whereNotIn('status', ['restore_pending'])
            ->whereDoesntHave('restoreRequests', fn ($query) => $query->whereIn('status', ['pending_validation', 'validated_pending_execution', 'executing']))
            ->each(function (SystemBackup $backup) use (&$count) {
            if ($backup->path) Storage::disk($backup->disk)->delete($backup->path);
            $backup->update(['status' => 'expired', 'path' => null, 'size_bytes' => null]);
            $count++;
        });
        return $count;
    }

    private function dumpDatabase(string $target): void
    {
        $connection = config('database.default');
        $config = config("database.connections.$connection");
        if (($config['driver'] ?? null) === 'sqlite') {
            File::copy($config['database'], $target);
            return;
        }
        if (!in_array($config['driver'] ?? null, ['mysql', 'mariadb'], true)) {
            throw new RuntimeException('Only MySQL, MariaDB, and SQLite backups are supported.');
        }

        $binary = config('backup.dump_binary') ?: (PHP_OS_FAMILY === 'Windows' && File::exists('C:/xampp/mysql/bin/mysqldump.exe') ? 'C:/xampp/mysql/bin/mysqldump.exe' : 'mysqldump');
        $process = new Process([$binary, '--single-transaction', '--routines', '--triggers', '--host='.$config['host'], '--port='.(string) $config['port'], '--user='.$config['username'], '--result-file='.$target, $config['database']], null, ['MYSQL_PWD' => (string) $config['password']]);
        $process->setTimeout(600)->mustRun();
        if (!File::exists($target) || File::size($target) === 0) throw new RuntimeException('Database dump is empty.');
    }

    private function makeArchive(string $zipPath, string $dump, string $manifest): void
    {
        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) throw new RuntimeException('Unable to create backup archive.');
        $zip->addFile($dump, 'database.sql');
        $zip->addFile($manifest, 'manifest.json');
        $private = storage_path('app/private');
        if (File::isDirectory($private)) {
            foreach (File::allFiles($private) as $file) {
                $real = $file->getRealPath();
                if (str_contains(str_replace('\\', '/', $real), '/backup-work/') || str_contains(str_replace('\\', '/', $real), '/system-backups/')) continue;
                $zip->addFile($real, 'private/'.str_replace('\\', '/', $file->getRelativePathname()));
            }
        }
        $zip->close();
    }
}
