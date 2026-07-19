<?php

declare(strict_types=1);

use InstaRequest\InstaTranslate\InstaTranslateServiceProvider;

it('loads the service provider', function () {
    $providers = app()->getLoadedProviders();
    expect(array_key_exists(InstaTranslateServiceProvider::class, $providers))->toBeTrue();
});
