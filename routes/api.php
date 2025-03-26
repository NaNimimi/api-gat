<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use App\Http\Middleware\ApiAuthMiddleware;
use Illuminate\Support\Facades\Route;

Route::post('/auth/login', [AuthController::class, 'login'])->name('auth.login')->middleware('throttle:login');


Route::group([
    'middleware' => [
       ApiAuthMiddleware::class,
    ],
], static function () {

    Route::get('/auth/check', static function () {
        return response()->json([
            'ok' => true,
            'message' => 'Authorized',
            'status' => 200
        ]);
    });

    Route::group([
        'prefix' => 'auth',
        'as' => 'auth.',
        'controller' => AuthController::class,
    ], static function () {
        Route::post('/logout', 'logout')->name('logout');
        Route::post('/me', 'me')->name('me');
    });

    Route::group([
        'prefix' => 'users',
        'as' => 'users.',
        'controller' => UserController::class,
    ], static function () {
        Route::get('/', 'index')->name('index')->middleware('permission:show-Users');
        Route::get('/all', 'all')->name('all');
        Route::get('/{user}', 'show')->name('show')->middleware('permission:show-Users');
        Route::post('/', 'store')->name('store')->middleware('permission:create-User');
        Route::match(['PUT', 'PATCH'], '/{user}', 'update')->name('update')->middleware('permission:update-User');
        Route::delete('/{user}', 'destroy')->name('destroy')->middleware('permission:delete-User');
    });


    Route::group([
        'prefix' => 'roles',
        'as' => 'roles.',
        'controller' => RoleController::class,
    ], static function () {
        Route::get('/', 'index')->name('index')->middleware('permission:show-Users');
        Route::get('/all', 'all')->name('all');
        Route::get('/{role}', 'show')->name('show')->middleware('permission:show-Users');
        Route::post('/', 'store')->name('store')->middleware('permission:create-User');
        Route::match(['PUT', 'PATCH'], '/{role}', 'update')->name('update')->middleware('permission:update-User');
        Route::delete('/{role}', 'destroy')->name('destroy')->middleware('permission:delete-User');
    });
});
