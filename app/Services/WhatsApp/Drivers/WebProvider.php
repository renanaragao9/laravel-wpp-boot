<?php

declare(strict_types=1);

namespace App\Services\WhatsApp\Drivers;

use App\Contracts\WhatsAppProvider;
use App\Enums\MessageDirection;
use App\Enums\WhatsAppDriver;
use App\Models\ChatSession;
use App\Models\Message;
use App\Support\WhatsApp\SendResult;
use Illuminate\Http\Request;

class WebProvider implements WhatsAppProvider
{
    public function driver(): WhatsAppDriver
    {
        return WhatsAppDriver::Web;
    }

    public function authorize(Request $request): bool
    {
        return false;
    }

    public function parse(Request $request): array
    {
        return [];
    }

    public function sendText(string $phone, string $text): SendResult
    {
        $session = ChatSession::query()->where('identifier', $phone)->first();

        if ($session === null) {
            return new SendResult(false, error: 'Chat web inexistente.');
        }

        $message = Message::create([
            'chat_session_id' => $session->id,
            'direction' => MessageDirection::Outbound,
            'body' => $text,
        ]);

        return new SendResult(true, (string) $message->id);
    }
}
