<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListMovieRequest extends FormRequest
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
            'release_year' => ['nullable', 'integer', 'digits:4', 'min:1888', 'max:2100'],
            'adult' => ['nullable', 'integer', Rule::in([0, 1])],
            'sort' => ['nullable', 'string', Rule::in(['popularity', 'release_date', 'vote_average', 'vote_count', 'id'])],
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
            'release_year.integer' => 'release_year 必须是整数',
            'release_year.digits' => 'release_year 必须是 4 位数字',
            'release_year.min' => 'release_year 最小值为 1888',
            'release_year.max' => 'release_year 最大值为 2100',
            'adult.integer' => 'adult 必须是整数',
            'adult.in' => 'adult 必须是 0 或 1',
            'sort.in' => 'sort 必须是 popularity、release_date、vote_average、vote_count 或 id',
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
