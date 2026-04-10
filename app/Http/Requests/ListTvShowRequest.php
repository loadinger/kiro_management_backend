<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListTvShowRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:100'],
            'genre_id' => ['nullable', 'integer', 'min:1'],
            'status' => ['nullable', 'string', 'max:50'],
            'first_air_year' => ['nullable', 'integer', 'digits:4', 'min:1900', 'max:2100'],
            'in_production' => ['nullable', 'integer', Rule::in([0, 1])],
            'adult' => ['nullable', 'integer', Rule::in([0, 1])],
            'sort' => ['nullable', 'string', Rule::in(['popularity', 'first_air_date', 'vote_average', 'vote_count', 'id'])],
            'order' => ['nullable', 'string', Rule::in(['asc', 'desc'])],
            'page' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'q.max' => 'q 参数长度不能超过 100 个字符',
            'genre_id.integer' => 'genre_id 必须是整数',
            'genre_id.min' => 'genre_id 最小值为 1',
            'status.max' => 'status 参数长度不能超过 50 个字符',
            'first_air_year.integer' => 'first_air_year 必须是整数',
            'first_air_year.digits' => 'first_air_year 必须是 4 位数字',
            'first_air_year.min' => 'first_air_year 最小值为 1900',
            'first_air_year.max' => 'first_air_year 最大值为 2100',
            'in_production.integer' => 'in_production 必须是整数',
            'in_production.in' => 'in_production 必须是 0 或 1',
            'adult.integer' => 'adult 必须是整数',
            'adult.in' => 'adult 必须是 0 或 1',
            'sort.in' => 'sort 必须是 popularity、first_air_date、vote_average、vote_count 或 id',
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
