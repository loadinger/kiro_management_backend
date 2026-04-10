<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListTvSeasonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tv_show_id' => ['required', 'integer', 'min:1'],
            'sort' => ['nullable', 'string', Rule::in(['season_number', 'air_date', 'vote_average', 'id'])],
            'order' => ['nullable', 'string', Rule::in(['asc', 'desc'])],
        ];
    }

    public function messages(): array
    {
        return [
            'tv_show_id.required' => 'tv_show_id 不能为空',
            'tv_show_id.integer' => 'tv_show_id 必须是整数',
            'tv_show_id.min' => 'tv_show_id 最小值为 1',
            'sort.in' => 'sort 必须是 season_number、air_date、vote_average 或 id',
            'order.in' => 'order 必须是 asc 或 desc',
        ];
    }
}
