<?php

declare(strict_types=1);

namespace InstaRequest\InstaTranslate;

use InstaRequest\InstaTranslate\Console\Commands\TranslationGenerateCommand;
use Illuminate\Support\ServiceProvider;

class InstaTranslateServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/insta-translate.php', 'insta-translate');
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__.'/../config/insta-translate.php' => config_path('insta-translate.php'),
        ], ['insta-translate', 'insta-translate-config']);

        $this->commands([
            TranslationGenerateCommand::class,
        ]);
    }
}
