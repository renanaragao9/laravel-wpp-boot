<?php

namespace App\Http\Requests\Chat;

use Illuminate\Foundation\Http\FormRequest;

class ListWebChatMessagesRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'after' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
