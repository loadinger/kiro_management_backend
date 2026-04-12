<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\ArticleStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListArticleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['nullable', 'string', Rule::enum(ArticleStatus::class)],
            'sort' => ['nullable', 'string', Rule::in(['sort_order', 'created_at', 'published_at'])],
            'order' => ['nullable', 'string', Rule::in(['asc', 'desc'])],
            'page' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.string' => 'status 必须是字符串',
            'status.enum' => 'status 必须是 draft、published 或 archived',
            'sort.string' => 'sort 必须是字符串',
            'sort.in' => 'sort 必须是 sort_order、created_at 或 published_at',
            'order.string' => 'order 必须是字符串',
            'order.in' => 'order 必须是 asc 或 desc',
            'page.integer' => 'page 必须是整数',
            'page.min' => 'page 最小值为 1',
            'page.max' => 'page 不能超过 1000',
            'per_page.integer' => 'per_page 必须是整数',
            'per_page.min' => 'per_page 最小值为 1',
            'per_page.max' => 'per_page 不能超过 100',
        ];
    }
}
