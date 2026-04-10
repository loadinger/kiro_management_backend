<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListTvShowImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tv_show_id' => ['required', 'integer', 'min:1'],
            'image_type' => ['nullable', 'string', Rule::in(['poster', 'backdrop', 'logo'])],
            'page' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'tv_show_id.required' => 'tv_show_id 不能为空',
            'tv_show_id.integer' => 'tv_show_id 必须是整数',
            'tv_show_id.min' => 'tv_show_id 最小值为 1',
            'image_type.in' => 'image_type 必须是 poster、backdrop 或 logo',
            'page.integer' => 'page 必须是整数',
            'page.min' => 'page 最小值为 1',
            'page.max' => 'page 不能超过 1000',
            'per_page.integer' => 'per_page 必须是整数',
            'per_page.min' => 'per_page 最小值为 1',
            'per_page.max' => 'per_page 不能超过 100',
        ];
    }
}
