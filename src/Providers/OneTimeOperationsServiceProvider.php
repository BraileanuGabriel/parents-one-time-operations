<?php

namespace EBS\ParentsOneTimeOperations\Providers;

use Illuminate\Support\ServiceProvider;
use EBS\ParentsOneTimeOperations\Commands\OneTimeOperationShowCommand;
use EBS\ParentsOneTimeOperations\Commands\OneTimeOperationsMakeCommand;
use EBS\ParentsOneTimeOperations\Commands\OneTimeOperationsProcessCommand;

class OneTimeOperationsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom([__DIR__.'/../../database/migrations']);

        $this->publishes([
            __DIR__.'/../../config/one-time-operations.php' => config_path('one-time-operations.php'),
        ]);

        if ($this->app->runningInConsole()) {
            $this->commands(OneTimeOperationsMakeCommand::class);
            $this->commands(OneTimeOperationsProcessCommand::class);
            $this->commands(OneTimeOperationShowCommand::class);
        }
    }

    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../../config/one-time-operations.php', 'one-time-operations'
        );
    }
}
