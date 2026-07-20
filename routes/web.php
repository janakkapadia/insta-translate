<?php

use Illuminate\Support\Facades\Route;
use InstaRequest\InstaTranslate\Http\Controllers\DashboardController;

Route::group([
    'domain' => config('insta-translate.domain', null),
    'prefix' => config('insta-translate.path', 'insta-translate'),
    'middleware' => config('insta-translate.middleware', ['web']),
], function () {
    Route::get('/', [DashboardController::class, 'index'])->name('insta-translate.dashboard');
    Route::post('/api/generate', [DashboardController::class, 'generate'])->name('insta-translate.api.generate');
    Route::post('/api/save', [DashboardController::class, 'save'])->name('insta-translate.api.save');
});
