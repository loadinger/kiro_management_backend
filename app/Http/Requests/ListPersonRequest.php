<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListPersonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
            'page' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'gender' => ['nullable', 'integer', Rule::in([0, 1, 2, 3])],
            'adult' => ['nullable', 'integer', Rule::in([0, 1])],
            'known_for_department' => ['nullable', 'string', 'max:100'],
            'q' => ['nullable', 'string', 'max:100'],
            'sort' => ['nullable', 'string', Rule::in(['id', 'popularity', 'updated_at', 'created_at'])],
            'order' => ['nullable', 'string', Rule::in(['asc', 'desc'])],
        ];
    }

    public function messages(): array
    {
        return [
            'per_page.integer' => 'per_page 必须是整数',
            'per_page.min' => 'per_page 最小值为 1',
            'per_page.max' => 'per_page 不能超过 50',
            'page.integer' => 'page 必须是整数',
            'page.min' => 'page 最小值为 1',
            'page.max' => 'page 不能超过 1000',
            'gender.integer' => 'gender 必须是整数',
            'gender.in' => 'gender 必须是 0、1、2 或 3',
            'adult.integer' => 'adult 必须是整数',
            'adult.in' => 'adult 必须是 0 或 1',
            'known_for_department.max' => 'known_for_department 长度不能超过 100 个字符',
            'q.max' => 'q 参数长度不能超过 100 个字符',
            'sort.in' => 'sort 必须是 id、popularity、updated_at 或 created_at',
            'order.in' => 'order 必须是 asc 或 desc',
        ];
    }
}
