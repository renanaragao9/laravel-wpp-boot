<?php

namespace Tests\Unit\Models;

use App\Enums\WebhookLogStatus;
use App\Models\WhatsAppWebhookLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WhatsAppWebhookLogTest extends TestCase
{
    use RefreshDatabase;

    protected function make(array $attributes = []): WhatsAppWebhookLog
    {
        return WhatsAppWebhookLog::create(array_merge(['driver' => 'twilio', 'raw_payload' => ['a' => 1]], $attributes));
    }

    public function test_can_create(): void
    {
        $record = $this->make();

        $this->assertDatabaseHas('whatsapp_webhook_logs', ['id' => $record->id]);
    }

    public function test_can_read(): void
    {
        $this->assertNotNull(WhatsAppWebhookLog::find($this->make()->id));
    }

    public function test_can_update(): void
    {
        $record = $this->make();

        $record->update(['phone' => '5585']);

        $this->assertDatabaseHas('whatsapp_webhook_logs', ['id' => $record->id] + ['phone' => '5585']);
    }

    public function test_can_delete(): void
    {
        $record = $this->make();

        $record->delete();

        $this->assertSoftDeleted('whatsapp_webhook_logs', ['id' => $record->id]);
        $this->assertNull(WhatsAppWebhookLog::find($record->id));
    }

    public function test_raw_payload_is_array_and_status_defaults_to_received(): void
    {
        $log = $this->make()->fresh();

        $this->assertSame(['a' => 1], $log->raw_payload);
        $this->assertSame(WebhookLogStatus::Received, $log->status);
    }
}
