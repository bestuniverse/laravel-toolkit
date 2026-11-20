<?php

declare(strict_types=1);

namespace Bestuniverse\LaravelToolkit\Tests;

use Bestuniverse\LaravelToolkit\LaravelToolkitServiceProvider;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

final class MysqlBackupCommandTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [LaravelToolkitServiceProvider::class];
    }

    public static function connections(): array
    {
        return [
            'localhost uses IPv4' => ['localhost', '', ['--protocol=TCP', '--host=127.0.0.1', '--port=3307'], ['--socket=']],
            'remote host is preserved' => ['db.example.com', '', ['--protocol=TCP', '--host=db.example.com', '--port=3307'], ['--socket=']],
            'socket takes precedence' => ['localhost', '/run/mysqld/mysqld.sock', ['--protocol=SOCKET', '--socket=/run/mysqld/mysqld.sock'], ['--host=', '--port=']],
        ];
    }

    #[DataProvider('connections')]
    public function test_backup_uses_configured_connection(string $host, string $socket, array $expected, array $excluded): void
    {
        config()->set('database.connections.mysql', [
            'driver' => 'mysql',
            'host' => $host,
            'port' => 3307,
            'unix_socket' => $socket,
            'database' => 'toolkit_test',
            'username' => 'backup_user',
            'password' => 'secret',
        ]);
        config()->set('laravel-toolkit.disk', 'local');
        Storage::fake('local');
        Process::fake();

        $this->artisan('db:backup')->assertExitCode(0);

        Process::assertRan(static function ($process) use ($expected, $excluded): bool {
            foreach ($expected as $option) {
                self::assertStringContainsString(escapeshellarg($option), $process->command);
            }
            foreach ($excluded as $option) {
                self::assertStringNotContainsString($option, $process->command);
            }

            return true;
        });
    }
}
