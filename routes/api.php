<?php

use Illuminate\Support\Facades\Route;
use Refatbd\LaravelFreeFire\Http\Controllers\HealthController;
use Refatbd\LaravelFreeFire\Http\Controllers\MediaController;
use Refatbd\LaravelFreeFire\Http\Controllers\PlayerController;

$middleware = config('freefire.routes.middleware', ['api', 'throttle:freefire']);

Route::prefix(config('freefire.routes.prefix', 'api/free-fire/v1'))->middleware($middleware)->group(function () {
    Route::get('/health', HealthController::class)->name('freefire.health');
    Route::get('/players/{uid}', [PlayerController::class, 'show'])->whereNumber('uid')->name('freefire.player');
    Route::get('/players/{uid}/avatar', [MediaController::class, 'avatar'])->whereNumber('uid')->name('freefire.avatar');
    Route::get('/players/{uid}/banner', [MediaController::class, 'banner'])->whereNumber('uid')->name('freefire.banner');
});

if (config('freefire.routes.compatibility', true)) {
    Route::middleware($middleware)->get('/player-info', [PlayerController::class, 'legacy'])->name('freefire.player.compat');
}

if (config('freefire.media.compatibility_routes', true)) {
    Route::middleware($middleware)->group(function () {
        Route::get('/api/avatar/avatar_{uid}.webp', [MediaController::class, 'avatar'])->whereNumber('uid')->name('freefire.avatar.compat');
        Route::get('/api/banner/banner_{uid}.webp', [MediaController::class, 'banner'])->whereNumber('uid')->name('freefire.banner.compat');
    });
}
