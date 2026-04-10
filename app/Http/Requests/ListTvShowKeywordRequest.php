<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListTvShowKeywordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tv_show_id' => ['required', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'tv_show_id.required' => 'tv_show_id 不能为空',
            'tv_show_id.integer' => 'tv_show_id 必须是整数',
            'tv_show_id.min' => 'tv_show_id 最小值为 1',
        ];
    }
}
