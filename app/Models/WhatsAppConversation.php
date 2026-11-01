<?php

namespace App\Models;

use App\Enums\ConversationStatus;
use App\Enums\WhatsAppDriver;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsAppConversation extends BaseModel
{
    protected $table = 'whatsapp_conversations';

    protected $fillable = [
        'driver',
        'phone',
        'push_name',
        'status',
        'last_interaction_at',
        'current_node_id',
    ];

    protected function casts(): array
    {
        return [
            'driver' => WhatsAppDriver::class,
            'status' => ConversationStatus::class,
            'last_interaction_at' => 'datetime',
        ];
    }

    public function currentNode(): BelongsTo
    {
        return $this->belongsTo(WhatsAppMenuNode::class, 'current_node_id');
    }
}
