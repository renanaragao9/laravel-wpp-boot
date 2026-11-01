<?php

namespace App\Models;

use App\Enums\MessageDirection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends BaseModel
{
    protected $fillable = [
        'chat_session_id',
        'direction',
        'body',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'direction' => MessageDirection::class,
        ];
    }

    public function chatSession(): BelongsTo
    {
        return $this->belongsTo(ChatSession::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
