<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CollectionController;
use App\Http\Controllers\Api\CountryController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DepartmentController;
use App\Http\Controllers\Api\GenreController;
use App\Http\Controllers\Api\JobController;
use App\Http\Controllers\Api\KeywordController;
use App\Http\Controllers\Api\LanguageController;
use App\Http\Controllers\Api\MovieController;
use App\Http\Controllers\Api\MovieCreditController;
use App\Http\Controllers\Api\MovieGenreController;
use App\Http\Controllers\Api\MovieImageController;
use App\Http\Controllers\Api\MovieKeywordController;
use App\Http\Controllers\Api\MovieProductionCompanyController;
use App\Http\Controllers\Api\ProductionCompanyController;
use App\Http\Controllers\Api\TvEpisodeController;
use App\Http\Controllers\Api\TvEpisodeCreditController;
use App\Http\Controllers\Api\TvEpisodeImageController;
use App\Http\Controllers\Api\TvNetworkController;
use App\Http\Controllers\Api\TvSeasonController;
use App\Http\Controllers\Api\TvSeasonImageController;
use App\Http\Controllers\Api\TvShowController;
use App\Http\Controllers\Api\TvShowCreatorController;
use App\Http\Controllers\Api\TvShowGenreController;
use App\Http\Controllers\Api\TvShowImageController;
use App\Http\Controllers\Api\TvShowKeywordController;
use App\Http\Controllers\Api\TvShowNetworkController;
use App\Http\Controllers\Api\TvShowProductionCompanyController;
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
    // refresh 不走 auth:api，允许携带已过期 token 来换新 token
    Route::post('refresh', [AuthController::class, 'refresh']);
    Route::middleware('auth:api')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
    });
});

// 受保护路由（所有业务接口放这里）
Route::middleware('auth:api')->group(function () {
    // 轻量参考数据（仅列表）
    Route::get('countries/all', [CountryController::class, 'all']);
    Route::get('countries', [CountryController::class, 'index']);
    Route::get('departments/all', [DepartmentController::class, 'all']);
    Route::get('departments', [DepartmentController::class, 'index']);
    Route::get('genres/all', [GenreController::class, 'all']);
    Route::get('genres', [GenreController::class, 'index']);
    Route::get('jobs/all', [JobController::class, 'all']);
    Route::get('jobs', [JobController::class, 'index']);
    Route::get('keywords/all', [KeywordController::class, 'all']);
    Route::get('keywords', [KeywordController::class, 'index']);
    Route::get('languages/all', [LanguageController::class, 'all']);
    Route::get('languages', [LanguageController::class, 'index']);

    // 富参考数据（列表 + 详情）
    Route::get('production-companies', [ProductionCompanyController::class, 'index']);
    Route::get('production-companies/{id}', [ProductionCompanyController::class, 'show']);
    Route::get('tv-networks', [TvNetworkController::class, 'index']);
    Route::get('tv-networks/{id}', [TvNetworkController::class, 'show']);

    // Dashboard 统计接口
    Route::get('dashboard/stats', [DashboardController::class, 'stats']);
    Route::get('dashboard/trends', [DashboardController::class, 'trends']);

    // 合集
    Route::get('collections', [CollectionController::class, 'index']);
    Route::get('collections/{id}', [CollectionController::class, 'show']);

    // 电影主资源
    Route::get('movies', [MovieController::class, 'index']);
    Route::get('movies/{id}', [MovieController::class, 'show']);

    // 电影子资源
    Route::get('movie-credits', [MovieCreditController::class, 'index']);
    Route::get('movie-images', [MovieImageController::class, 'index']);
    Route::get('movie-genres', [MovieGenreController::class, 'index']);
    Route::get('movie-keywords', [MovieKeywordController::class, 'index']);
    Route::get('movie-production-companies', [MovieProductionCompanyController::class, 'index']);

    // 电视剧主资源
    Route::get('tv-shows', [TvShowController::class, 'index']);
    Route::get('tv-shows/{id}', [TvShowController::class, 'show']);

    // 电视剧子资源（全量，不分页）
    Route::get('tv-show-genres', [TvShowGenreController::class, 'index']);
    Route::get('tv-show-keywords', [TvShowKeywordController::class, 'index']);
    Route::get('tv-show-networks', [TvShowNetworkController::class, 'index']);
    Route::get('tv-show-production-companies', [TvShowProductionCompanyController::class, 'index']);
    Route::get('tv-show-creators', [TvShowCreatorController::class, 'index']);

    // 电视剧子资源（分页）
    Route::get('tv-show-images', [TvShowImageController::class, 'index']);

    // 电视剧季
    Route::get('tv-seasons', [TvSeasonController::class, 'index']);
    Route::get('tv-seasons/{id}', [TvSeasonController::class, 'show']);
    Route::get('tv-season-images', [TvSeasonImageController::class, 'index']);

    // 电视剧集
    Route::get('tv-episodes', [TvEpisodeController::class, 'index']);
    Route::get('tv-episodes/{id}', [TvEpisodeController::class, 'show']);
    Route::get('tv-episode-credits', [TvEpisodeCreditController::class, 'index']);
    Route::get('tv-episode-images', [TvEpisodeImageController::class, 'index']);

    // persons 等接口后续在此添加
});
