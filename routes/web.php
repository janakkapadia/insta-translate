<?php

use Illuminate\Support\Facades\Route;
use InstaRequest\InstaTranslate\Http\Controllers\DashboardController;
use InstaRequest\InstaTranslate\Http\Middleware\Authorize;

Route::group([
    'domain' => config('insta-translate.domain', null),
    'prefix' => config('insta-translate.path', 'insta-translate'),
    'middleware' => array_merge(
        config('insta-translate.middleware', ['web']),
        [Authorize::class],
    ),
], function () {
    Route::get('/', [DashboardController::class, 'index'])->name('insta-translate.dashboard');
    Route::post('/api/generate', [DashboardController::class, 'generate'])->name('insta-translate.api.generate');
    Route::post('/api/generate-batch', [DashboardController::class, 'generateBatch'])->name('insta-translate.api.generate-batch');
    Route::post('/api/generate-multi-lang', [DashboardController::class, 'generateMultiLang'])->name('insta-translate.api.generate-multi-lang');
    Route::post('/api/save', [DashboardController::class, 'save'])->name('insta-translate.api.save');
    Route::post('/api/save-multi-lang', [DashboardController::class, 'saveMultiLang'])->name('insta-translate.api.save-multi-lang');
});
