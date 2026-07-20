<?php
require __DIR__.'/vendor/autoload.php';

$app = new \Illuminate\Foundation\Application(__DIR__);
$app->bind('config', function () {
    return new \Illuminate\Config\Repository([
        'ai' => [
            'default' => 'anthropic',
            'providers' => [
                'anthropic' => [
                    'driver' => 'anthropic',
                    'key' => 'fake_key',
                ]
            ]
        ]
    ]);
});
$app->register(\Laravel\Ai\AiServiceProvider::class);

$agent = \Laravel\Ai\AnonymousAgent::make('You are a translator', [], []);
echo "Agent created: " . get_class($agent) . "\n";
