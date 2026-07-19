<?php

declare(strict_types=1);

namespace InstaRequest\AiTranslator;

use InstaRequest\AiTranslator\Console\Commands\TranslationGenerateCommand;
use Illuminate\Support\ServiceProvider;

class AiTranslatorServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/ai-translator.php', 'ai-translator');

        $this->app->singleton(AiTranslator::class);
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
            __DIR__.'/../config/ai-translator.php' => config_path('ai-translator.php'),
        ], ['ai-translator', 'ai-translator-config']);

        $this->commands([
            TranslationGenerateCommand::class,
        ]);
    }
}
