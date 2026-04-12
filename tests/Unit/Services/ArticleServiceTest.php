<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Repositories\Contracts\ArticleItemRepositoryInterface;
use App\Repositories\Contracts\ArticleRepositoryInterface;
use App\Services\ArticleService;
use Mockery;
use Tests\TestCase;

class ArticleServiceTest extends TestCase
{
    private ArticleService $service;

    private \ReflectionMethod $parsePlaceholders;

    protected function setUp(): void
    {
        parent::setUp();

        $articleRepo = Mockery::mock(ArticleRepositoryInterface::class);
        $articleItemRepo = Mockery::mock(ArticleItemRepositoryInterface::class);

        $this->service = new ArticleService($articleRepo, $articleItemRepo);

        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('parsePlaceholders');
        $method->setAccessible(true);
        $this->parsePlaceholders = $method;
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function parse(string $content): array
    {
        return $this->parsePlaceholders->invoke($this->service, $content);
    }

    public function test_returns_empty_array_for_empty_content(): void
    {
        $result = $this->parse('');

        $this->assertSame([], $result);
    }

    public function test_ignores_invalid_entity_type(): void
    {
        $result = $this->parse('::media{type="invalid_type" id="1"}');

        $this->assertSame([], $result);
    }

    public function test_ignores_negative_id(): void
    {
        $result = $this->parse('::media{type="movie" id="-1"}');

        $this->assertSame([], $result);
    }

    public function test_ignores_zero_id(): void
    {
        $result = $this->parse('::media{type="movie" id="0"}');

        $this->assertSame([], $result);
    }

    public function test_ignores_non_numeric_id(): void
    {
        $result = $this->parse('::media{type="movie" id="abc"}');

        $this->assertSame([], $result);
    }

    public function test_deduplicates_same_entity(): void
    {
        $content = '::media{type="movie" id="1"} ::media{type="movie" id="1"}';

        $result = $this->parse($content);

        $this->assertCount(1, $result);
        $this->assertSame('movie', $result[0]['entity_type']);
        $this->assertSame(1, $result[0]['entity_id']);
    }

    public function test_ignores_incomplete_placeholder(): void
    {
        // Missing id attribute
        $result = $this->parse('::media{type="movie"}');

        $this->assertSame([], $result);
    }
}
