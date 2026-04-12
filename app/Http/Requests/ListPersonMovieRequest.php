<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListPersonMovieRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'person_id' => ['required', 'integer'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'person_id.required' => 'person_id 不能为空',
            'person_id.integer' => 'person_id 必须是整数',
            'per_page.integer' => 'per_page 必须是整数',
            'per_page.min' => 'per_page 最小值为 1',
            'per_page.max' => 'per_page 不能超过 100',
            'page.integer' => 'page 必须是整数',
            'page.min' => 'page 最小值为 1',
            'page.max' => 'page 不能超过 1000',
        ];
    }
}
