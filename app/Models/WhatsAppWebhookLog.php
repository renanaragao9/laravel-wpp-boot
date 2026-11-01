<?php

namespace App\Models;

use App\Enums\WebhookLogStatus;
use App\Enums\WhatsAppDriver;

class WhatsAppWebhookLog extends BaseModel
{
    protected $table = 'whatsapp_webhook_logs';

    protected $fillable = [
        'driver',
        'event',
        'instance',
        'phone',
        'push_name',
        'message_id',
        'message_type',
        'option',
        'text',
        'raw_payload',
        'status',
        'error',
    ];

    protected function casts(): array
    {
        return [
            'driver' => WhatsAppDriver::class,
            'status' => WebhookLogStatus::class,
            'raw_payload' => 'array',
        ];
    }
}
