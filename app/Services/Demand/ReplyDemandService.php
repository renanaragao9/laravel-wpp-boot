<?php

declare(strict_types=1);

namespace App\Services\Demand;

use App\Enums\DemandStatus;
use App\Enums\MessageDirection;
use App\Models\WhatsAppDemand;
use App\Models\WhatsAppDemandMessage;
use App\Services\WhatsApp\SendMessageService;
use App\Support\WhatsApp\SendResult;

class ReplyDemandService
{
    public function __construct(private readonly SendMessageService $sender) {}

    public function run(WhatsAppDemand $demand, string $body, ?int $userId): SendResult
    {
        $result = $this->sender->run($demand->driver, $demand->phone, $body);

        if (! $result->success) {
            return $result;
        }

        WhatsAppDemandMessage::create([
            'demand_id' => $demand->id,
            'user_id' => $userId,
            'direction' => MessageDirection::Outbound,
            'message_id' => $result->messageId,
            'body' => $body,
        ]);

        if ($demand->status === DemandStatus::Open) {
            $demand->update(['status' => DemandStatus::InProgress, 'assigned_user_id' => $demand->assigned_user_id ?? $userId]);
        }

        return $result;
    }
}
