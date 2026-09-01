<?php

declare(strict_types=1);

namespace Bestuniverse\LaravelToolkit\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;

final class MysqlBackupCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'db:backup {--full}';

    /**
     * The console command description.
     */
    protected $description = 'Creates a partial database backup of specific live tables like blog posts, articles, menus and email templates';


    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $disk = config('laravel-toolkit.disk', 'local');
        $directory = trim((string) config('laravel-toolkit.directory', 'mysql-backup'), '/');

        $full = $this->option('full');
        $full = true;

        // načítaj priamo hlavnú connection mysql
        $conn = DB::connection('mysql')->getConfig();

        $host = $conn['host'] ?? '127.0.0.1';
        $port = $conn['port'] ?? 3306;
        $db = $conn['database'] ?? null;
        $user = $conn['username'] ?? null;
        $pass = $conn['password'] ?? '';

        if(!$db || !$user) {
            $this->error('Chýba konfigurácia DB (database/username).');
            return self::FAILURE;
        }

        if($full) {
            $fileName = ($directory . '/live_' . now()->format('Y_m_d_His') . '_full.sql');
            $tables = [];
        } else {
            $fileName = ($directory . '/live_' . now()->format('Y_m_d_His') . '_partial.sql');
        }

        // Uistíme sa, že adresár existuje
        Storage::disk($disk)->makeDirectory(dirname($fileName));
        $fullPath = Storage::disk($disk)->path($fileName);

        // Vybuduj príkaz mysqldump
        $cmd = [
            'mysqldump',
            "--host={$host}",
            "--port={$port}",
            "--user={$user}",
            "--password={$pass}",
            '--single-transaction',
            '--quick',
            '--skip-lock-tables',
            "--result-file={$fullPath}",
            $db,
            // tabuľky
            ...$tables,
        ];

        $process = Process::run(
            implode(' ', array_map('escapeshellarg', $cmd))
        );

        if(!$process->successful()) {
            $this->error('Backup zlyhal: ' . $process->errorOutput());
            return self::FAILURE;
        }

        $this->info("Backup hotový: {$fullPath}");
        return self::SUCCESS;
    }
}
