<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;
use ZipArchive;

#[Signature('portal:backup {--retention=14 : Jumlah hari penyimpanan backup}')]
#[Description('Backup database PostgreSQL dan media portal ke storage privat')]
class BackupPortal extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (config('database.default') !== 'pgsql') {
            $this->error('Perintah backup ini memerlukan PostgreSQL.');

            return self::FAILURE;
        }
        $directory = storage_path('app/private/backups');
        File::ensureDirectoryExists($directory);
        $stamp = now()->format('Ymd-His');
        $connection = config('database.connections.pgsql');
        $process = new Process(['pg_dump', '--format=custom', '--no-owner', '--host='.$connection['host'], '--port='.$connection['port'], '--username='.$connection['username'], '--file='.$directory.'/database-'.$stamp.'.dump', $connection['database']], base_path(), ['PGPASSWORD' => (string) $connection['password']]);
        $process->setTimeout(600)->mustRun();
        if (class_exists(ZipArchive::class) && File::isDirectory(storage_path('app/public'))) {
            $zip = new ZipArchive;
            $zip->open($directory.'/media-'.$stamp.'.zip', ZipArchive::CREATE | ZipArchive::OVERWRITE);
            foreach (File::allFiles(storage_path('app/public')) as $file) {
                $zip->addFile($file->getRealPath(), $file->getRelativePathname());
            }
            $zip->close();
        }
        $retentionDays = max(1, (int) $this->option('retention'));
        foreach (File::files($directory) as $backup) {
            if ($backup->getMTime() < now()->subDays($retentionDays)->getTimestamp()) {
                File::delete($backup->getRealPath());
            }
        }
        $this->info('Backup tersimpan di '.$directory);

        return self::SUCCESS;
    }
}
