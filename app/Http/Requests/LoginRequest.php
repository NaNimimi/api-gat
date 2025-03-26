<?php

namespace App\Http\Requests;

class LoginRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'username' => 'required|string',
            'password' => 'required|string|min:3',
        ];
    }
}
