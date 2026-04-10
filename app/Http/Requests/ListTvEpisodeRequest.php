<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListTvEpisodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tv_season_id' => ['required', 'integer', 'min:1'],
            'sort' => ['nullable', 'string', Rule::in(['episode_number', 'air_date', 'vote_average', 'id'])],
            'order' => ['nullable', 'string', Rule::in(['asc', 'desc'])],
            'page' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'tv_season_id.required' => 'tv_season_id 不能为空',
            'tv_season_id.integer' => 'tv_season_id 必须是整数',
            'tv_season_id.min' => 'tv_season_id 最小值为 1',
            'sort.in' => 'sort 必须是 episode_number、air_date、vote_average 或 id',
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
