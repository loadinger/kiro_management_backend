<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\ArticleStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreArticleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:500'],
            'slug' => ['nullable', 'string', 'regex:/^[a-z0-9-]+$/', 'max:255'],
            'content' => ['required', 'string'],
            'cover_path' => ['nullable', 'string', 'max:500'],
            'status' => ['nullable', 'string', Rule::enum(ArticleStatus::class)],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'published_at' => ['nullable', 'date'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if (
                    $this->input('status') === ArticleStatus::Published->value
                    && ! $this->filled('slug')
                ) {
                    $validator->errors()->add('slug', '发布专题必须填写 slug');
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'title 不能为空',
            'title.string' => 'title 必须是字符串',
            'title.max' => 'title 长度不能超过 500 个字符',
            'slug.string' => 'slug 必须是字符串',
            'slug.regex' => 'slug 只能包含小写字母、数字和连字符',
            'slug.max' => 'slug 长度不能超过 255 个字符',
            'content.required' => 'content 不能为空',
            'content.string' => 'content 必须是字符串',
            'cover_path.string' => 'cover_path 必须是字符串',
            'cover_path.max' => 'cover_path 长度不能超过 500 个字符',
            'status.string' => 'status 必须是字符串',
            'status.enum' => 'status 必须是 draft、published 或 archived',
            'sort_order.integer' => 'sort_order 必须是整数',
            'sort_order.min' => 'sort_order 最小值为 0',
            'published_at.date' => 'published_at 必须是合法的日期格式',
        ];
    }
}
