<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SiteRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'url' => 'required|url',
            'key' => 'required|string|min:32|max:64',
        ];
    }
}
