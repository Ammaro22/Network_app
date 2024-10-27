<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GroupRequest extends FormRequest
{

    public function authorize()
    {
    return true;
    }

    public function rules()
    {
    return [
    'user_ids' => 'required|array',
    'user_ids.*' => 'exists:users,id',
    ];
    }

}
