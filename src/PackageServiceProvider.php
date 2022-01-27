<?php

namespace OpenDialogAi\Webchat;

use Illuminate\Support\ServiceProvider;
use OpenDialogAi\Core\Console\Commands\ComponentSettings;

class PackageServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../public' => public_path('vendor/webchat'),
            __DIR__ . '/../resources/images' => public_path('vendor/webchat/images'),
            __DIR__ . '/../resources/fonts' => public_path('vendor/webchat/fonts'),
        ], 'public');

        $this->publishes([
            __DIR__ . '/../resources/scripts' => app_path('../')
        ], 'scripts');

        $this->publishes([
            __DIR__ . '/../database/migrations' => database_path(config('opendialog.webchat.migration_publish_dir'))
        ], 'od-webchat-migrations');

        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');

        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'webchat');

        if ($this->app->runningUnitTests()) {
            $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        }

        if ($this->app->runningInConsole()) {
            $this->commands([
                ComponentSettings::class,
            ]);
        }
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/webchat.php', 'opendialog.webchat');
    }
}
