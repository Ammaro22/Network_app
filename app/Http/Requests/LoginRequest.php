<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{

    public function rules()
    {
        return [
            'user_name' => 'required|string',
            'password' => 'required|string',
        ];
    }

    public function authorize()
    {
        return true;
    }
}
