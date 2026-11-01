<?php

declare(strict_types=1);

namespace App\Services\Chat;

use App\Enums\MessageDirection;
use App\Models\ChatSession;
use App\Models\Message;

class SendWebChatMessageService
{
    public function __construct(private readonly ProcessWebChatMessageService $process) {}

    public function run(ChatSession $session, array $data): Message
    {
        $message = Message::create([
            'chat_session_id' => $session->id,
            'direction' => MessageDirection::Inbound,
            'body' => $data['message'],
        ]);

        $session->update(['last_seen_at' => now()]);

        $this->process->run($session, $message);

        return $message;
    }
}
