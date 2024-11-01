<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SignupRequest extends FormRequest
{
    public function rules()
    {
        return [
            'full_name' => 'required|string|max:255',
            'user_name' => 'required|string|max:255|unique:users,user_name',
            'password' => 'required|string|min:8|max:15',
            'type_id' => 'required|integer|exists:types,id',
            'email'   =>'required'
        ];
    }

    public function authorize()
    {
        return true;
    }

}
