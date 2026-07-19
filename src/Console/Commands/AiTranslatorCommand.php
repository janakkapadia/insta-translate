<?php

declare(strict_types=1);

namespace AiTranslator\AiTranslator\Console\Commands;

use Illuminate\Console\Command;

class AiTranslatorCommand extends Command
{
    /**
     * The command signature.
     */
    protected $signature = 'ai-translator:placeholder';

    /**
     * The command description.
     */
    protected $description = 'Placeholder Artisan command shipped by the package ai-translator.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->line('AiTranslator placeholder command executed.');

        return self::SUCCESS;
    }
}
