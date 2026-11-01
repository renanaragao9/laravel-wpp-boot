<?php

namespace App\Http\Requests\Webhook;

use Illuminate\Foundation\Http\FormRequest;

class VerifyWhatsAppWebhookRequest extends FormRequest
{
    public function authorize(): true
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'hub_mode' => ['nullable', 'string'],
            'hub_verify_token' => ['nullable', 'string'],
            'hub_challenge' => ['nullable', 'string'],
        ];
    }
}
