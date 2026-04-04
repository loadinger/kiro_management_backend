<?php

declare(strict_types=1);

namespace App\Providers;

use App\Repositories\Contracts\CountryRepositoryInterface;
use App\Repositories\Contracts\DepartmentRepositoryInterface;
use App\Repositories\Contracts\GenreRepositoryInterface;
use App\Repositories\Contracts\JobRepositoryInterface;
use App\Repositories\Contracts\KeywordRepositoryInterface;
use App\Repositories\Contracts\LanguageRepositoryInterface;
use App\Repositories\Contracts\ProductionCompanyRepositoryInterface;
use App\Repositories\Contracts\DashboardRepositoryInterface;
use App\Repositories\Contracts\TvNetworkRepositoryInterface;
use App\Repositories\CountryRepository;
use App\Repositories\DashboardRepository;
use App\Repositories\DepartmentRepository;
use App\Repositories\GenreRepository;
use App\Repositories\JobRepository;
use App\Repositories\KeywordRepository;
use App\Repositories\LanguageRepository;
use App\Repositories\ProductionCompanyRepository;
use App\Repositories\TvNetworkRepository;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(CountryRepositoryInterface::class, CountryRepository::class);
        $this->app->bind(DepartmentRepositoryInterface::class, DepartmentRepository::class);
        $this->app->bind(GenreRepositoryInterface::class, GenreRepository::class);
        $this->app->bind(JobRepositoryInterface::class, JobRepository::class);
        $this->app->bind(KeywordRepositoryInterface::class, KeywordRepository::class);
        $this->app->bind(LanguageRepositoryInterface::class, LanguageRepository::class);
        $this->app->bind(ProductionCompanyRepositoryInterface::class, ProductionCompanyRepository::class);
        $this->app->bind(TvNetworkRepositoryInterface::class, TvNetworkRepository::class);
        $this->app->bind(DashboardRepositoryInterface::class, DashboardRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
