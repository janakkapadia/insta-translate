<?php

declare(strict_types=1);

namespace JanakKapadia\InstaTranslate;

use Illuminate\Support\ServiceProvider;
use JanakKapadia\InstaTranslate\Console\Commands\TranslationGenerateCommand;
use JanakKapadia\InstaTranslate\Console\Commands\TranslationPruneCommand;

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
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/insta-translate.php' => config_path('insta-translate.php'),
            ], ['insta-translate', 'insta-translate-config']);

            $this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/insta-translate'),
            ], 'insta-translate-views');

            $this->commands([
                TranslationGenerateCommand::class,
                TranslationPruneCommand::class,
            ]);
        }

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'insta-translate');

        $this->registerRoutes();
    }

    /**
     * Register the package routes.
     */
    protected function registerRoutes(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
    }
}
