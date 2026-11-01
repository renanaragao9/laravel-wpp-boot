<?php

namespace App\Models;

use App\Enums\MessageDirection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsAppDemandMessage extends BaseModel
{
    protected $table = 'whatsapp_demand_messages';

    protected $fillable = [
        'direction',
        'message_id',
        'body',
        'read_at',
        'demand_id',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'direction' => MessageDirection::class,
            'read_at' => 'datetime',
        ];
    }

    public function demand(): BelongsTo
    {
        return $this->belongsTo(WhatsAppDemand::class, 'demand_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
