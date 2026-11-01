<?php

declare(strict_types=1);

namespace App\Services\Demand;

use App\Enums\MessageDirection;
use App\Models\WhatsAppDemand;

class MarkDemandAsReadService
{
    public function run(WhatsAppDemand $demand): void
    {
        $now = now();

        $demand->messages()
            ->where('direction', MessageDirection::Inbound->value)
            ->whereNull('read_at')
            ->update(['read_at' => $now]);

        if ($demand->seen_at === null) {
            $demand->forceFill(['seen_at' => $now])->saveQuietly();
        }
    }
}
