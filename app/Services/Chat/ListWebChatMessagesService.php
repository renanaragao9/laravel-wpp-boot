<?php

declare(strict_types=1);

namespace App\Services\Chat;

use App\Models\ChatSession;
use Illuminate\Database\Eloquent\Collection;

class ListWebChatMessagesService
{
    public function run(ChatSession $session, int $afterId): Collection
    {
        $session->update(['last_seen_at' => now()]);

        return $session->messages()->where('id', '>', $afterId)->orderBy('id')->get();
    }
}
