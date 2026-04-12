<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ArticleStatus;
use App\Models\Article;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Article>
 */
class ArticleFactory extends Factory
{
    protected $model = Article::class;

    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),
            'slug' => null,
            'cover_path' => null,
            'content' => fake()->paragraph(),
            'status' => ArticleStatus::Draft->value,
            'sort_order' => fake()->numberBetween(0, 100),
            'published_at' => null,
            'created_by' => null,
        ];
    }

    /**
     * Set the article status to published with a slug.
     */
    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ArticleStatus::Published->value,
            'slug' => fake()->unique()->slug(3),
            'published_at' => now(),
        ]);
    }
}
