<?php

use App\Http\Controllers\Auth\LoginController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| All routes here are automatically prefixed with /api and have the
| api middleware group applied (stateless, Sanctum token auth).
|
*/

Route::prefix('auth')->group(function () {

    // Public: login does not require an existing token
    Route::post('login', [LoginController::class, 'login']);

    // Protected: require a valid Sanctum token
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [LoginController::class, 'logout']);
        Route::get('me',     [LoginController::class, 'me']);
    });

});
