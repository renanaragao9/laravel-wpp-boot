<?php

namespace App\Filament\Resources\WebhookLogs\Pages;

use App\Filament\Resources\WebhookLogs\WhatsAppWebhookLogResource;
use Filament\Resources\Pages\ViewRecord;

class ViewWhatsAppWebhookLog extends ViewRecord
{
    protected static string $resource = WhatsAppWebhookLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
        ];
    }
}
