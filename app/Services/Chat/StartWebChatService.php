<?php

declare(strict_types=1);

namespace App\Services\Chat;

use App\Models\ChatSession;
use Illuminate\Support\Str;

class StartWebChatService
{
    public function __construct(private readonly ProcessWebChatMessageService $process) {}

    public function run(array $data): ChatSession
    {
        $session = ChatSession::create([
            'token' => Str::random(48),
            'identifier' => 'web-'.Str::lower(Str::random(16)),
            'name' => $data['name'],
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'last_seen_at' => now(),
        ]);

        $this->process->run($session, null);

        return $session;
    }
}
