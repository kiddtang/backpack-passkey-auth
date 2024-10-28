<?php

use App\Http\Controllers\Admin\Auth\LoginController;
use App\Http\Controllers\Admin\PasskeyController;
use Illuminate\Support\Facades\Route;

// --------------------------
// Passkey Sign-In Routes
// --------------------------
Route::group([
    'prefix' => config('backpack.base.route_prefix', 'admin'),
    'middleware' => array_merge(
        (array) config('backpack.base.web_middleware', 'web'),
        ['throttle:5,1'] // 5 attempts per minute
    ),
    'namespace'  => 'App\Http\Controllers\Admin\Auth',
], function () {
    Route::post('passkey/login', [LoginController::class, 'authenticateOptions'])->name('passkey.login');
    Route::post('passkeys/authenticate', [LoginController::class, 'authenticatePasskey'])->name('passkey.authenticate');
});

// --------------------------
// Custom Backpack Routes
// --------------------------
// This route file is loaded automatically by Backpack\CRUD.
// Routes you generate using Backpack\Generators will be placed here.

Route::group([
    'prefix' => config('backpack.base.route_prefix', 'admin'),
    'middleware' => array_merge(
        (array) config('backpack.base.web_middleware', 'web'),
        (array) config('backpack.base.middleware_key', 'admin')
    ),
    'namespace' => 'App\Http\Controllers\Admin',
], function () { // custom admin routes
    Route::post('passkey/create', [PasskeyController::class, 'create'])->name('backpack.passkey.create');
    Route::delete('passkey/{id}', [PasskeyController::class, 'destroy'])->name('backpack.passkey.delete');
}); // this should be the absolute last line of this file

/**
 * DO NOT ADD ANYTHING HERE.
 */
