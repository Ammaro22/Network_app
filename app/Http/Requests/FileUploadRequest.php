<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FileUploadRequest extends FormRequest
{
    public function rules()
    {
        return [
            'files' => 'required|array',
            'files.*' => 'file|mimes:jpg,jpeg,png,pdf,doc,docx',
            'group_id' => 'required|exists:groups,id',
        ];
    }

    public function authorize()
    {
        return true;
    }
}
