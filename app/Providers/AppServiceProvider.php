<?php

declare(strict_types=1);

namespace App\Providers;

use App\Repositories\ArticleItemRepository;
use App\Repositories\ArticleRepository;
use App\Repositories\CollectionRepository;
use App\Repositories\Contracts\ArticleItemRepositoryInterface;
use App\Repositories\Contracts\ArticleRepositoryInterface;
use App\Repositories\Contracts\CollectionRepositoryInterface;
use App\Repositories\Contracts\CountryRepositoryInterface;
use App\Repositories\Contracts\DashboardRepositoryInterface;
use App\Repositories\Contracts\DepartmentRepositoryInterface;
use App\Repositories\Contracts\GenreRepositoryInterface;
use App\Repositories\Contracts\JobRepositoryInterface;
use App\Repositories\Contracts\KeywordRepositoryInterface;
use App\Repositories\Contracts\LanguageRepositoryInterface;
use App\Repositories\Contracts\MediaListSnapshotRepositoryInterface;
use App\Repositories\Contracts\MovieCreditRepositoryInterface;
use App\Repositories\Contracts\MovieGenreRepositoryInterface;
use App\Repositories\Contracts\MovieImageRepositoryInterface;
use App\Repositories\Contracts\MovieKeywordRepositoryInterface;
use App\Repositories\Contracts\MovieProductionCompanyRepositoryInterface;
use App\Repositories\Contracts\MovieRepositoryInterface;
use App\Repositories\Contracts\PersonMovieRepositoryInterface;
use App\Repositories\Contracts\PersonRepositoryInterface;
use App\Repositories\Contracts\PersonTvShowRepositoryInterface;
use App\Repositories\Contracts\ProductionCompanyRepositoryInterface;
use App\Repositories\Contracts\TvEpisodeCreditRepositoryInterface;
use App\Repositories\Contracts\TvEpisodeImageRepositoryInterface;
use App\Repositories\Contracts\TvEpisodeRepositoryInterface;
use App\Repositories\Contracts\TvNetworkRepositoryInterface;
use App\Repositories\Contracts\TvSeasonImageRepositoryInterface;
use App\Repositories\Contracts\TvSeasonRepositoryInterface;
use App\Repositories\Contracts\TvShowCreatorRepositoryInterface;
use App\Repositories\Contracts\TvShowGenreRepositoryInterface;
use App\Repositories\Contracts\TvShowImageRepositoryInterface;
use App\Repositories\Contracts\TvShowKeywordRepositoryInterface;
use App\Repositories\Contracts\TvShowNetworkRepositoryInterface;
use App\Repositories\Contracts\TvShowProductionCompanyRepositoryInterface;
use App\Repositories\Contracts\TvShowRepositoryInterface;
use App\Repositories\CountryRepository;
use App\Repositories\DashboardRepository;
use App\Repositories\DepartmentRepository;
use App\Repositories\GenreRepository;
use App\Repositories\JobRepository;
use App\Repositories\KeywordRepository;
use App\Repositories\LanguageRepository;
use App\Repositories\MediaListSnapshotRepository;
use App\Repositories\MovieCreditRepository;
use App\Repositories\MovieGenreRepository;
use App\Repositories\MovieImageRepository;
use App\Repositories\MovieKeywordRepository;
use App\Repositories\MovieProductionCompanyRepository;
use App\Repositories\MovieRepository;
use App\Repositories\PersonMovieRepository;
use App\Repositories\PersonRepository;
use App\Repositories\PersonTvShowRepository;
use App\Repositories\ProductionCompanyRepository;
use App\Repositories\TvEpisodeCreditRepository;
use App\Repositories\TvEpisodeImageRepository;
use App\Repositories\TvEpisodeRepository;
use App\Repositories\TvNetworkRepository;
use App\Repositories\TvSeasonImageRepository;
use App\Repositories\TvSeasonRepository;
use App\Repositories\TvShowCreatorRepository;
use App\Repositories\TvShowGenreRepository;
use App\Repositories\TvShowImageRepository;
use App\Repositories\TvShowKeywordRepository;
use App\Repositories\TvShowNetworkRepository;
use App\Repositories\TvShowProductionCompanyRepository;
use App\Repositories\TvShowRepository;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(CollectionRepositoryInterface::class, CollectionRepository::class);
        $this->app->bind(MediaListSnapshotRepositoryInterface::class, MediaListSnapshotRepository::class);
        $this->app->bind(CountryRepositoryInterface::class, CountryRepository::class);
        $this->app->bind(DepartmentRepositoryInterface::class, DepartmentRepository::class);
        $this->app->bind(GenreRepositoryInterface::class, GenreRepository::class);
        $this->app->bind(JobRepositoryInterface::class, JobRepository::class);
        $this->app->bind(KeywordRepositoryInterface::class, KeywordRepository::class);
        $this->app->bind(LanguageRepositoryInterface::class, LanguageRepository::class);
        $this->app->bind(ProductionCompanyRepositoryInterface::class, ProductionCompanyRepository::class);
        $this->app->bind(TvNetworkRepositoryInterface::class, TvNetworkRepository::class);
        $this->app->bind(DashboardRepositoryInterface::class, DashboardRepository::class);
        $this->app->bind(MovieRepositoryInterface::class, MovieRepository::class);
        $this->app->bind(PersonRepositoryInterface::class, PersonRepository::class);
        $this->app->bind(PersonMovieRepositoryInterface::class, PersonMovieRepository::class);
        $this->app->bind(PersonTvShowRepositoryInterface::class, PersonTvShowRepository::class);
        $this->app->bind(MovieCreditRepositoryInterface::class, MovieCreditRepository::class);
        $this->app->bind(MovieImageRepositoryInterface::class, MovieImageRepository::class);
        $this->app->bind(MovieGenreRepositoryInterface::class, MovieGenreRepository::class);
        $this->app->bind(MovieKeywordRepositoryInterface::class, MovieKeywordRepository::class);
        $this->app->bind(MovieProductionCompanyRepositoryInterface::class, MovieProductionCompanyRepository::class);

        // TV Show
        $this->app->bind(TvShowRepositoryInterface::class, TvShowRepository::class);
        $this->app->bind(TvShowGenreRepositoryInterface::class, TvShowGenreRepository::class);
        $this->app->bind(TvShowKeywordRepositoryInterface::class, TvShowKeywordRepository::class);
        $this->app->bind(TvShowNetworkRepositoryInterface::class, TvShowNetworkRepository::class);
        $this->app->bind(TvShowProductionCompanyRepositoryInterface::class, TvShowProductionCompanyRepository::class);
        $this->app->bind(TvShowImageRepositoryInterface::class, TvShowImageRepository::class);
        $this->app->bind(TvShowCreatorRepositoryInterface::class, TvShowCreatorRepository::class);

        // TV Season
        $this->app->bind(TvSeasonRepositoryInterface::class, TvSeasonRepository::class);
        $this->app->bind(TvSeasonImageRepositoryInterface::class, TvSeasonImageRepository::class);

        // TV Episode
        $this->app->bind(TvEpisodeRepositoryInterface::class, TvEpisodeRepository::class);
        $this->app->bind(TvEpisodeCreditRepositoryInterface::class, TvEpisodeCreditRepository::class);
        $this->app->bind(TvEpisodeImageRepositoryInterface::class, TvEpisodeImageRepository::class);

        // Article
        $this->app->bind(ArticleRepositoryInterface::class, ArticleRepository::class);
        $this->app->bind(ArticleItemRepositoryInterface::class, ArticleItemRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
