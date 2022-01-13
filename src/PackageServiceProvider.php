<?php

namespace OpenDialogAi\Webchat;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use OpenDialogAi\Core\Console\Commands\ComponentSettings;
use OpenDialogAi\Core\ComponentSetting;

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

        $this->loadWebchatConfig();

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

    /**
     * Pulls in the webchat settings from the database and loads into Laravel config.
     * Will only load the settings if not running from the console
     */
    private function loadWebchatConfig()
    {
        if (!app()->runningInConsole()) {
            // Sets the url to the app url
            app()['config']->set('webchat.' . ComponentSetting::URL, config("APP_URL"));

            /** @var ComponentSetting $componentSetting */
            foreach (ComponentSetting::all() as $componentSetting) {
                if ($componentSetting->value !== null) {
                    if ($componentSetting->type == ComponentSetting::MAP) {
                        $value = json_decode($componentSetting->value);
                    } elseif ($componentSetting->type === ComponentSetting::NUMBER) {
                        $value = (float) $componentSetting->value;
                    } elseif ($componentSetting->type === ComponentSetting::BOOLEAN) {
                        $value = (bool) $componentSetting->value;
                    } else {
                        $value = $componentSetting->value;
                    }

                    app()['config']->set('webchat.' . $componentSetting->name, $value);
                }
            }
        }
    }
}
