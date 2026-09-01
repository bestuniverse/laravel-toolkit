<?php

declare(strict_types=1);

namespace Bestuniverse\LaravelToolkit\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class MySqlImportCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'db:import
                            {file? : File name from storage/mysql-backup}
                            {--force : Confirm that the database import should run}
                            {--user= : MySQL username}
                            {--pass= : MySQL password}
                            {--db= : MySQL database name}';

    /**
     * The console command description.
     */
    protected $description = 'Import MySQL backup from storage/mysql-backup';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if ($this->laravel->environment('production') && ! $this->option('force')) {
            $this->error('Database import in production requires the --force option.');

            return self::FAILURE;
        }

        $disk = config('laravel-toolkit.disk', 'local');
        $directory = trim((string) config('laravel-toolkit.directory', 'mysql-backup'), '/');
        $files = array_values(array_filter(
            Storage::disk($disk)->files($directory),
            static fn (string $file): bool => Str::endsWith(Str::lower($file), '.sql'),
        ));

        if ($files === []) {
            $this->error("No .sql backup files found in storage/{$directory}.");

            return self::FAILURE;
        }

        $selected = $this->argument('file');

        if($selected) {
            $selected = trim((string) $selected);
            $selectedPath = str_starts_with($selected, $directory.'/')
                ? $selected
                : $directory.'/'.$selected;

            if(!in_array($selectedPath, $files, true)) {
                $this->error("File {$selected} was not found in storage/{$directory}.");
                return self::FAILURE;
            }
        } else {
            $fileMap = [];
            foreach($files as $file) {
                if (Str::startsWith(basename($file), '.')) {
                    continue;
                }

                $fileMap[basename($file)] = $file;
            }

            if ($fileMap === []) {
                $this->error("No .sql backup files found in storage/{$directory}.");

                return self::FAILURE;
            }

            ksort($fileMap);
            $selectedName = $this->choice('Select backup file to import', array_keys($fileMap));
            $selectedPath = $fileMap[$selectedName];
        }

        $absolutePath = Storage::disk($disk)->path($selectedPath);
        $defaultMysql = config('database.connections.mysql', []);
        $dbUser = (string) ($this->option('user') ?: ($defaultMysql['username'] ?? 'root'));
        $dbPass = (string) ($this->option('pass') ?: ($defaultMysql['password'] ?? ''));
        $dbName = (string) ($this->option('db') ?: ($defaultMysql['database'] ?? ''));

        if($dbName === '') {
            $this->error('Database name is missing. Use --db=your_database.');
            return self::FAILURE;
        }

        $command = sprintf(
            'mysql -u %s -p%s %s < %s',
            escapeshellarg($dbUser),
            escapeshellarg($dbPass),
            escapeshellarg($dbName),
            escapeshellarg($absolutePath)
        );

        $this->info("Importing: storage/{$selectedPath}");

        $process = Process::timeout(0)->run($command);

        if(!$process->successful()) {
            $this->error('Import failed.');
            if($process->errorOutput() !== '') {
                $this->line($process->errorOutput());
            }

            return self::FAILURE;
        }

        $this->info('Import finished successfully.');
        return self::SUCCESS;
    }
}
