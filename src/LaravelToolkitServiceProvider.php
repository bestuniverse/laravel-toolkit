<?php

declare(strict_types=1);

namespace Bestuniverse\LaravelToolkit;

use Bestuniverse\LaravelToolkit\Commands\MySqlImportCommand;
use Bestuniverse\LaravelToolkit\Commands\MysqlBackupCommand;
use Illuminate\Support\ServiceProvider;

final class LaravelToolkitServiceProvider extends ServiceProvider
{
    /**
     * Register package services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/laravel-toolkit.php',
            'laravel-toolkit',
        );

    }

    /**
     * Bootstrap package services.
     */
    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/laravel-toolkit.php' => config_path('laravel-toolkit.php'),
        ], ['laravel-toolkit-config', 'config']);

        $this->displayConfigPublishNotice();

        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'laravel-toolkit');

        if ($this->app->runningInConsole()) {
            $this->commands([
                MysqlBackupCommand::class,
                MySqlImportCommand::class,
            ]);
        }
    }

    /**
     * Show the configuration command after Composer runs Laravel's package discovery.
     */
    private function displayConfigPublishNotice(): void
    {
        if (
            ! $this->app->runningInConsole()
            || is_file(config_path('laravel-toolkit.php'))
            || ! in_array('package:discover', $_SERVER['argv'] ?? [], true)
        ) {
            return;
        }

        echo "Laravel Toolkit: publish the configuration with php artisan vendor:publish --tag=laravel-toolkit-config\n";
    }
}
