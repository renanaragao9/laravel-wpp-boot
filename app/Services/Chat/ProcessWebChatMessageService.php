<?php

declare(strict_types=1);

namespace App\Services\Chat;

use App\Enums\WhatsAppDriver;
use App\Models\ChatSession;
use App\Models\Message;
use App\Services\Menu\NavigateMenuService;
use App\Support\WhatsApp\IncomingMessage;

class ProcessWebChatMessageService
{
    public function __construct(private readonly NavigateMenuService $navigate) {}

    public function run(ChatSession $session, ?Message $message): void
    {
        $text = $message?->body;

        $this->navigate->run(new IncomingMessage(
            driver: WhatsAppDriver::Web,
            phone: $session->identifier,
            name: $session->name,
            messageId: $message === null ? null : (string) $message->id,
            messageType: 'text',
            text: $text,
            option: $text,
            instance: 'web',
            event: 'chat.message',
        ));
    }
}
