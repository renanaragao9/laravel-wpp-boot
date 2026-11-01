<?php

declare(strict_types=1);

namespace App\Services\Demand;

use App\Enums\ConversationStatus;
use App\Enums\DemandStatus;
use App\Models\WhatsAppConversation;
use App\Models\WhatsAppDemand;

class CloseDemandService
{
    public function run(WhatsAppDemand $demand): WhatsAppDemand
    {
        $demand->update(['status' => DemandStatus::Done]);

        $conversation = $demand->conversation_id ? WhatsAppConversation::find($demand->conversation_id) : null;

        if ($conversation?->status === ConversationStatus::WithAgent) {
            $conversation->update(['status' => ConversationStatus::Finished]);
        }

        return $demand->refresh();
    }
}
