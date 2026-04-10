<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListMovieGenreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'movie_id' => ['required', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'movie_id.required' => 'movie_id 不能为空',
            'movie_id.integer' => 'movie_id 必须是整数',
            'movie_id.min' => 'movie_id 最小值为 1',
        ];
    }
}
