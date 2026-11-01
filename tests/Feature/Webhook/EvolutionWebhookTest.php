<?php

namespace Tests\Feature\Webhook;

use App\Enums\WebhookLogStatus;
use App\Models\WhatsAppWebhookLog;
use Illuminate\Support\Facades\Http;

class EvolutionWebhookTest extends WebhookTestCase
{
    public function test_requires_the_webhook_token(): void
    {
        $this->postJson('/api/webhooks/evolution', $this->evolutionPayload('5585988880000@s.whatsapp.net', 'oi'))->assertForbidden();
        $this->postJson('/api/webhooks/evolution', $this->evolutionPayload('5585988880000@s.whatsapp.net', 'oi'), ['apikey' => 'wrong'])->assertForbidden();

        $this->assertDatabaseCount('whatsapp_webhook_logs', 0);
    }

    public function test_is_rejected_when_token_is_not_configured(): void
    {
        config(['whatsapp.evolution.webhook_token' => '']);

        $this->postJson('/api/webhooks/evolution', $this->evolutionPayload('5585988880000@s.whatsapp.net', 'oi'), ['apikey' => ''])->assertForbidden();
    }

    public function test_message_is_processed_and_replied_through_evolution(): void
    {
        $this->postJson('/api/webhooks/evolution', $this->evolutionPayload('5585988880000@s.whatsapp.net', 'oi'), ['apikey' => 'evo-token'])
            ->assertOk()
            ->assertJson(['queued' => 1]);

        $log = WhatsAppWebhookLog::firstOrFail();
        $this->assertSame(WebhookLogStatus::Processed, $log->status);
        $this->assertSame('5585988880000', $log->phone);

        Http::assertSent(fn ($request) => $request->url() === 'https://evo.test/message/sendText/inst'
            && $request['number'] === '5585988880000'
            && $request->hasHeader('apikey', 'k'));
    }

    public function test_token_can_also_come_in_the_query_string(): void
    {
        $this->postJson('/api/webhooks/evolution?token=evo-token', $this->evolutionPayload('5585988880000@s.whatsapp.net', 'oi'))->assertOk();
    }

    public function test_own_messages_groups_and_other_events_are_ignored(): void
    {
        $headers = ['apikey' => 'evo-token'];

        $this->postJson('/api/webhooks/evolution', $this->evolutionPayload('5585988880000@s.whatsapp.net', 'oi', 'A', true), $headers)->assertOk()->assertJson(['received' => 0]);
        $this->postJson('/api/webhooks/evolution', $this->evolutionPayload('1203630@g.us', 'oi', 'B'), $headers)->assertOk()->assertJson(['received' => 0]);
        $this->postJson('/api/webhooks/evolution', ['event' => 'connection.update'], $headers)->assertOk()->assertJson(['received' => 0]);

        Http::assertNothingSent();
    }

    public function test_apikey_is_not_stored_in_the_raw_payload(): void
    {
        $payload = $this->evolutionPayload('5585988880000@s.whatsapp.net', 'oi') + ['apikey' => 'segredo'];

        $this->postJson('/api/webhooks/evolution', $payload, ['apikey' => 'evo-token'])->assertOk();

        $this->assertArrayNotHasKey('apikey', WhatsAppWebhookLog::firstOrFail()->raw_payload);
    }
}
