<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListCollectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:100'],
            'order' => ['nullable', 'string', Rule::in(['asc', 'desc'])],
            'page' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'q.max' => 'q 参数长度不能超过 100 个字符',
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
