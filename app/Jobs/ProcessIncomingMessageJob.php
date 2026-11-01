<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\WebhookLogStatus;
use App\Models\WhatsAppWebhookLog;
use App\Services\Menu\NavigateMenuService;
use App\Support\WhatsApp\IncomingMessage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class ProcessIncomingMessageJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function __construct(public readonly int $logId) {}

    public function handle(NavigateMenuService $navigate): void
    {
        $log = WhatsAppWebhookLog::query()->findOrFail($this->logId);

        try {
            $navigate->run(new IncomingMessage(
                driver: $log->driver,
                phone: (string) $log->phone,
                name: $log->push_name,
                messageId: $log->message_id,
                messageType: $log->message_type,
                text: $log->text,
                option: $log->option,
                instance: $log->instance,
                event: $log->event,
            ));

            $log->update(['status' => WebhookLogStatus::Processed]);
        } catch (Throwable $e) {
            $log->update(['status' => WebhookLogStatus::Failed, 'error' => $e->getMessage()]);
        }
    }
}
