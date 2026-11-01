<?php

declare(strict_types=1);

namespace App\Services\WhatsApp;

use App\Enums\WhatsAppDriver;
use App\Support\WhatsApp\SendResult;
use Illuminate\Support\Facades\Log;

class SendMessageService
{
    public function __construct(private readonly ProviderManager $providers) {}

    public function run(WhatsAppDriver $driver, string $phone, string $text): SendResult
    {
        $result = $this->providers->for($driver)->sendText($phone, $text);

        if (! $result->success) {
            Log::warning('WhatsApp: falha ao enviar mensagem.', [
                'driver' => $driver->value,
                'phone' => $phone,
                'error' => $result->error,
            ]);
        }

        return $result;
    }
}
