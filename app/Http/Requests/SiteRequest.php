<?php

namespace App\Http\Requests;

use App\Rules\RemoteURL;
use Illuminate\Foundation\Http\FormRequest;

class SiteRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'url' => [
                'required',
                'string',
                'url',
                'max:255',
                new RemoteURL
            ],
            'key' => 'required|string|min:32|max:64',
        ];
    }
}
