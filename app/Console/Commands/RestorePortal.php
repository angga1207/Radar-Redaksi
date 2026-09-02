<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;
use ZipArchive;

#[Signature('portal:restore {databaseBackup : Nama file dump di direktori backup} {--media= : Nama file ZIP media} {--force : Konfirmasi pemulihan destruktif}')]
#[Description('Pulihkan database PostgreSQL dan media dari backup privat')]
class RestorePortal extends Command
{
    public function handle(): int
    {
        if (! $this->option('force')) {
            $this->error('Restore mengganti data aktif. Jalankan kembali dengan --force setelah memastikan target backup benar.');

            return self::FAILURE;
        }
        if (config('database.default') !== 'pgsql') {
            $this->error('Perintah restore ini memerlukan PostgreSQL.');

            return self::FAILURE;
        }
        $directory = storage_path('app/private/backups');
        $databaseBackup = $this->backupPath((string) $this->argument('databaseBackup'), $directory, '.dump');
        if (! $databaseBackup) {
            $this->error('File backup database tidak valid atau tidak ditemukan.');

            return self::FAILURE;
        }
        $connection = config('database.connections.pgsql');
        $process = new Process(['pg_restore', '--clean', '--if-exists', '--no-owner', '--host='.$connection['host'], '--port='.$connection['port'], '--username='.$connection['username'], '--dbname='.$connection['database'], $databaseBackup], base_path(), ['PGPASSWORD' => (string) $connection['password']]);
        $process->setTimeout(600)->mustRun();
        if ($mediaName = $this->option('media')) {
            $mediaBackup = $this->backupPath((string) $mediaName, $directory, '.zip');
            if (! $mediaBackup || ! class_exists(ZipArchive::class)) {
                $this->error('Backup media tidak valid atau ekstensi ZIP tidak tersedia.');

                return self::FAILURE;
            }
            File::ensureDirectoryExists(storage_path('app/public'));
            $zip = new ZipArchive;
            if ($zip->open($mediaBackup) !== true || ! $zip->extractTo(storage_path('app/public'))) {
                $this->error('Backup media gagal diekstrak.');

                return self::FAILURE;
            }
            $zip->close();
        }
        $this->info('Portal berhasil dipulihkan dari backup.');

        return self::SUCCESS;
    }

    private function backupPath(string $filename, string $directory, string $extension): ?string
    {
        if (basename($filename) !== $filename || ! str_ends_with($filename, $extension)) {
            return null;
        }
        $path = $directory.DIRECTORY_SEPARATOR.$filename;

        return File::isFile($path) ? $path : null;
    }
}
