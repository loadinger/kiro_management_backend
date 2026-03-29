<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListJobRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:100'],
            'department_id' => ['nullable', 'integer', 'min:1'],
            'sort' => ['nullable', 'string', Rule::in(['id', 'name', 'department_id'])],
            'order' => ['nullable', 'string', Rule::in(['asc', 'desc'])],
            'page' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'q.max' => 'q 参数长度不能超过 100 个字符',
            'department_id.integer' => 'department_id 必须是整数',
            'department_id.min' => 'department_id 必须是正整数',
            'sort.in' => 'sort 必须是 id、name、department_id 之一',
            'order.in' => 'order 必须是 asc 或 desc',
            'page.max' => 'page 不能超过 1000',
            'per_page.max' => 'per_page 不能超过 100',
        ];
    }
}
