<?php
require __DIR__.'/vendor/autoload.php';
$app = new \Illuminate\Foundation\Application(__DIR__);
$app->register(\Laravel\Ai\AiServiceProvider::class);
$facade = new \ReflectionClass(\Laravel\Ai\Facades\Ai::class);
echo "Facade available\n";
