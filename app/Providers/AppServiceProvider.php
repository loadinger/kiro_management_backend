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
use App\Services\ArticleSlugService;
use App\Services\LlmTranslationService;
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

        // Bind ArticleSlugService with a dedicated slug-generation system prompt,
        // keeping the default LlmTranslationService binding (used by TranslationService) unaffected.
        $this->app->bind(ArticleSlugService::class, function (): ArticleSlugService {
            $slugSystemPrompt = <<<'PROMPT'
你是一个专业的影视内容 SEO 助手，专门将中文影视专题文章标题转换为英文 URL slug。

规则：
1. 提取标题的核心关键词翻译为英文，不要逐字翻译完整标题
2. slug 应简短精炼，控制在 3~15 个英文单词以内，便于记忆和 SEO
3. 输出必须是纯英文单词，以连字符分隔
4. 全部小写，不含大写字母
5. 不含数字前缀、标点符号、空格、特殊字符
6. 只输出 slug 本身，不含任何解释或额外文字
7. 必须处理输入中的每一条，不能遗漏

输入格式：{"task":"generate_slug","items":[{"id":1,"text":"盘点2024年最值得一看的十部科幻电影"},{"id":2,"text":"星际穿越"}]}

输出格式（严格按此 JSON 数组，字段名必须是 id 和 translation，必须包含所有输入条目）：
[{"id":1,"translation":"best-sci-fi-2024"},{"id":2,"translation":"interstellar"}]

错误示例（禁止）：
✗ [{"id":1,"translation":"Top-Ten-Most-Worth-Watching-Sci-Fi-Movies-In-2024"}]  （逐字翻译，过长，含大写）
✗ [{"id":1,"translation":"复仇者联盟"}]                                          （未翻译）
✗ [{"id":1,"translation":"001-avengers"}]                                        （含数字前缀）
✓ [{"id":1,"translation":"best-sci-fi-2024"},{"id":2,"translation":"interstellar"}]
PROMPT;

            return new ArticleSlugService(
                new LlmTranslationService($slugSystemPrompt),
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
