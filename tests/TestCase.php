<?php

declare(strict_types=1);

namespace JanakKapadia\InstaTranslate\Tests;

use JanakKapadia\InstaTranslate\InstaTranslateServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            InstaTranslateServiceProvider::class,
        ];
    }
}
