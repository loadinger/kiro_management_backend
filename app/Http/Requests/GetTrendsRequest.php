<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GetTrendsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Split the comma-separated entities string into an array before validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('entities') && is_string($this->entities)) {
            $this->merge([
                'entities' => array_filter(array_map('trim', explode(',', $this->entities))),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'days' => ['nullable', 'integer', 'in:7,30,90'],
            'entities' => ['nullable', 'array'],
            'entities.*' => ['string', 'in:movies,tv_shows,persons'],
        ];
    }

    public function messages(): array
    {
        return [
            'days.in' => '参数错误：days 只允许 7、30 或 90',
            'days.integer' => '参数错误：days 必须是整数',
            'entities.array' => '参数错误：entities 格式不正确',
            'entities.*.in' => '参数错误：entities 包含不支持的实体类型',
        ];
    }

    /**
     * Apply default values for optional parameters after validation passes.
     */
    public function validated($key = null, $default = null): mixed
    {
        $data = parent::validated($key, $default);

        if ($key !== null) {
            return $data;
        }

        $data['days'] = (int) ($data['days'] ?? 30);
        $data['entities'] = $data['entities'] ?? ['movies', 'tv_shows', 'persons'];

        return $data;
    }
}
