<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CountryController;
use App\Http\Controllers\Api\DepartmentController;
use App\Http\Controllers\Api\GenreController;
use App\Http\Controllers\Api\JobController;
use App\Http\Controllers\Api\KeywordController;
use App\Http\Controllers\Api\LanguageController;
use App\Http\Controllers\Api\ProductionCompanyController;
use App\Http\Controllers\Api\TvNetworkController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// 认证（无需 token）
Route::prefix('auth')->group(function () {
    Route::middleware('throttle:10,1')->group(function () {
        Route::post('login', [AuthController::class, 'login']);
    });
    Route::middleware('auth:api')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('refresh', [AuthController::class, 'refresh']);
        Route::get('me', [AuthController::class, 'me']);
    });
});

// 受保护路由（所有业务接口放这里）
Route::middleware('auth:api')->group(function () {
    // 轻量参考数据（仅列表）
    Route::get('countries', [CountryController::class, 'index']);
    Route::get('departments', [DepartmentController::class, 'index']);
    Route::get('genres', [GenreController::class, 'index']);
    Route::get('jobs', [JobController::class, 'index']);
    Route::get('keywords', [KeywordController::class, 'index']);
    Route::get('languages', [LanguageController::class, 'index']);

    // 富参考数据（列表 + 详情）
    Route::get('production-companies', [ProductionCompanyController::class, 'index']);
    Route::get('production-companies/{id}', [ProductionCompanyController::class, 'show']);
    Route::get('tv-networks', [TvNetworkController::class, 'index']);
    Route::get('tv-networks/{id}', [TvNetworkController::class, 'show']);

    // movies, tv-shows, persons 等接口后续在此添加
});
