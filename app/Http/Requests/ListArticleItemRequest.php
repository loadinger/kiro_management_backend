<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\ArticleEntityType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListArticleItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'entity_type' => ['required', 'string', Rule::enum(ArticleEntityType::class)],
            'entity_id' => ['required', 'integer', 'min:1'],
            'page' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'entity_type.required' => 'entity_type 不能为空',
            'entity_type.string' => 'entity_type 必须是字符串',
            'entity_type.enum' => 'entity_type 必须是支持的实体类型之一',
            'entity_id.required' => 'entity_id 不能为空',
            'entity_id.integer' => 'entity_id 必须是整数',
            'entity_id.min' => 'entity_id 最小值为 1',
            'page.integer' => 'page 必须是整数',
            'page.min' => 'page 最小值为 1',
            'page.max' => 'page 不能超过 1000',
            'per_page.integer' => 'per_page 必须是整数',
            'per_page.min' => 'per_page 最小值为 1',
            'per_page.max' => 'per_page 不能超过 100',
        ];
    }
}
