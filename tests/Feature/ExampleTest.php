<?php

declare(strict_types=1);

use AiTranslator\AiTranslator\AiTranslator;

it('resolves the singleton', function () {
    expect(app(AiTranslator::class))->toBeInstanceOf(AiTranslator::class);
});

it('returns the same instance from the container', function () {
    expect(app(AiTranslator::class))->toBe(app(AiTranslator::class));
});

it('merges the package config', function () {
    expect(config('ai-translator.placeholder'))->toBe('default');
});

it('registers the artisan command', function () {
    $this->artisan('ai-translator:placeholder')
        ->expectsOutputToContain('AiTranslator placeholder command executed.')
        ->assertSuccessful();
});
