<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required'    => '邮箱不能为空',
            'email.email'       => '邮箱格式不合法',
            'password.required' => '密码不能为空',
            'password.string'   => '密码必须是字符串',
        ];
    }
}
