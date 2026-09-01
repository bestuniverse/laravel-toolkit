<?php

declare(strict_types=1);

namespace Bestuniverse\LaravelToolkit\Tests;

use Bestuniverse\LaravelToolkit\LaravelToolkitServiceProvider;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Orchestra\Testbench\TestCase;

final class MySqlImportCommandTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [LaravelToolkitServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('filesystems.disks.local', [
            'driver' => 'local',
            'root' => storage_path('app'),
        ]);
        $app['config']->set('laravel-toolkit.disk', 'local');
        $app['config']->set('laravel-toolkit.directory', 'mysql-backup');
        $app['config']->set('database.connections.mysql', [
            'database' => 'toolkit_test',
            'username' => 'root',
            'password' => '',
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->removePublishedConfig();
        Storage::fake('local');
    }

    protected function tearDown(): void
    {
        $this->removePublishedConfig();

        parent::tearDown();
    }

    public function test_configuration_is_only_published_explicitly(): void
    {
        $configPath = config_path('laravel-toolkit.php');

        self::assertFileDoesNotExist($configPath);

        $this->artisan('vendor:publish', ['--tag' => 'laravel-toolkit-config'])
            ->assertExitCode(0);

        self::assertFileExists($configPath);
    }

    public function test_import_requires_force_option(): void
    {
        $this->app['env'] = 'production';
        Storage::disk('local')->put('mysql-backup/backup.sql', 'SELECT 1;');

        $this->artisan('db:import', ['file' => 'backup.sql'])
            ->expectsOutput('Database import in production requires the --force option.')
            ->assertExitCode(1);
    }

    public function test_import_ignores_non_sql_files(): void
    {
        Storage::disk('local')->put('mysql-backup/backup.txt', 'not a SQL dump');

        $this->artisan('db:import', ['--force' => true])
            ->expectsOutput('No .sql backup files found in storage/mysql-backup.')
            ->assertExitCode(1);
    }

    public function test_import_runs_without_force_outside_production(): void
    {
        Storage::disk('local')->put('mysql-backup/backup.sql', 'SELECT 1;');
        Process::fake();

        $this->artisan('db:import', ['file' => 'backup.sql'])
            ->assertExitCode(0);

        Process::assertRan(static fn ($process): bool => str_contains($process->command, 'mysql'));
    }

    public function test_import_runs_in_production_when_force_is_given(): void
    {
        $this->app['env'] = 'production';
        Storage::disk('local')->put('mysql-backup/backup.sql', 'SELECT 1;');
        Process::fake();

        $this->artisan('db:import', [
            'file' => 'backup.sql',
            '--force' => true,
        ])->assertExitCode(0);

        Process::assertRan(static fn ($process): bool => str_contains($process->command, 'mysql'));
    }

    private function removePublishedConfig(): void
    {
        $configPath = config_path('laravel-toolkit.php');

        if (is_file($configPath)) {
            unlink($configPath);
        }
    }
}
