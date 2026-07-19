<?php

declare(strict_types=1);

namespace AiTranslator\AiTranslator\Tests;

use AiTranslator\AiTranslator\AiTranslatorServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            AiTranslatorServiceProvider::class,
        ];
    }
}
