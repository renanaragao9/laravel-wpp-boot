<?php

namespace App\Http\Requests\Webhook;

use App\Enums\WhatsAppDriver;
use App\Services\WhatsApp\ProviderManager;
use Illuminate\Foundation\Http\FormRequest;

class WhatsAppWebhookRequest extends FormRequest
{
    public function authorize(): bool
    {
        $driver = WhatsAppDriver::tryFrom((string) $this->route('driver'));

        return $driver !== null && app(ProviderManager::class)->for($driver)->authorize($this);
    }

    public function rules(): array
    {
        return [
            'event' => ['nullable', 'string'],
            'instance' => ['nullable', 'string'],
            'data' => ['nullable', 'array'],
            'entry' => ['nullable', 'array'],
        ];
    }

    protected function failedAuthorization(): void
    {
        abort(403, 'Webhook não autorizado.');
    }
}
