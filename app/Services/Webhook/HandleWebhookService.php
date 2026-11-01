<?php

declare(strict_types=1);

namespace App\Services\Webhook;

use App\Enums\WebhookLogStatus;
use App\Enums\WhatsAppDriver;
use App\Jobs\ProcessIncomingMessageJob;
use App\Models\WhatsAppWebhookLog;
use App\Services\WhatsApp\ProviderManager;
use Illuminate\Http\Request;

class HandleWebhookService
{
    public function __construct(private readonly ProviderManager $providers) {}

    public function run(WhatsAppDriver $driver, Request $request): array
    {
        $provider = $this->providers->for($driver);

        $messages = $provider->parse($request);
        $payload = $this->sanitize($request->all());

        if ($messages === []) {
            WhatsAppWebhookLog::create([
                'driver' => $driver,
                'event' => $request->input('event') ?? 'ignored',
                'status' => WebhookLogStatus::Ignored,
                'raw_payload' => $payload,
            ]);

            return ['received' => 0, 'queued' => 0, 'duplicated' => 0];
        }

        $queued = 0;
        $duplicated = 0;

        foreach ($messages as $message) {
            if ($message->messageId !== null && $this->alreadyReceived($driver, $message->messageId)) {
                $duplicated++;

                continue;
            }

            $log = WhatsAppWebhookLog::create([
                'driver' => $driver,
                'event' => $message->event,
                'instance' => $message->instance,
                'phone' => $message->phone,
                'push_name' => $message->name,
                'message_id' => $message->messageId,
                'message_type' => $message->messageType,
                'option' => $message->option,
                'text' => $message->text,
                'status' => WebhookLogStatus::Received,
                'raw_payload' => $payload,
            ]);

            ProcessIncomingMessageJob::dispatch($log->id);
            $queued++;
        }

        return ['received' => count($messages), 'queued' => $queued, 'duplicated' => $duplicated];
    }

    private function alreadyReceived(WhatsAppDriver $driver, string $messageId): bool
    {
        return WhatsAppWebhookLog::query()
            ->where('driver', $driver->value)
            ->where('message_id', $messageId)
            ->exists();
    }

    private function sanitize(array $payload): array
    {
        unset($payload['apikey']);

        return $payload;
    }
}
