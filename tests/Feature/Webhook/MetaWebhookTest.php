<?php

namespace Tests\Feature\Webhook;

use App\Enums\WebhookLogStatus;
use App\Models\WhatsAppWebhookLog;
use Illuminate\Support\Facades\Http;

class MetaWebhookTest extends WebhookTestCase
{
    public function test_verification_returns_challenge_with_correct_token(): void
    {
        $this->get('/api/webhooks/meta?hub.mode=subscribe&hub.verify_token=vt&hub.challenge=abc123')
            ->assertOk()
            ->assertSee('abc123');
    }

    public function test_verification_rejects_wrong_token(): void
    {
        $this->get('/api/webhooks/meta?hub.mode=subscribe&hub.verify_token=wrong&hub.challenge=abc123')->assertForbidden();
    }

    public function test_verification_is_only_available_for_meta(): void
    {
        $this->get('/api/webhooks/evolution?hub.mode=subscribe')->assertNotFound();
    }

    public function test_post_with_invalid_signature_is_rejected_and_nothing_is_stored(): void
    {
        $this->postMeta($this->metaPayload('5585999990000', 'oi'), 'wrong-secret')->assertForbidden();
        $this->postMeta($this->metaPayload('5585999990000', 'oi'), null)->assertForbidden();

        $this->assertDatabaseCount('whatsapp_webhook_logs', 0);
    }

    public function test_post_is_rejected_when_app_secret_is_not_configured(): void
    {
        config(['whatsapp.meta.app_secret' => '']);

        $this->postMeta($this->metaPayload('5585999990000', 'oi'), '')->assertForbidden();
    }

    public function test_valid_message_is_logged_processed_and_replied_with_the_menu(): void
    {
        $this->postMeta($this->metaPayload('5585999990000', 'oi'))->assertOk()->assertJson(['queued' => 1]);

        $log = WhatsAppWebhookLog::firstOrFail();
        $this->assertSame(WebhookLogStatus::Processed, $log->status);
        $this->assertSame('5585999990000', $log->phone);
        $this->assertSame('Maria', $log->push_name);

        Http::assertSent(fn ($request) => str_contains($request->url(), 'graph.facebook.com/v21.0/123/messages')
            && $request['to'] === '5585999990000'
            && str_contains($request['text']['body'], '1 - Comercial'));
    }

    public function test_duplicated_message_id_is_ignored(): void
    {
        $payload = $this->metaPayload('5585999990000', 'oi', 'wamid.dup');

        $this->postMeta($payload)->assertOk();
        $this->postMeta($payload)->assertOk()->assertJson(['duplicated' => 1, 'queued' => 0]);

        $this->assertDatabaseCount('whatsapp_webhook_logs', 1);
    }

    public function test_status_callbacks_without_messages_are_logged_as_ignored(): void
    {
        $payload = ['entry' => [['changes' => [['value' => ['statuses' => [['id' => 'wamid.9', 'status' => 'delivered']]]]]]]];

        $this->postMeta($payload)->assertOk()->assertJson(['received' => 0]);

        $this->assertSame(WebhookLogStatus::Ignored, WhatsAppWebhookLog::firstOrFail()->status);
    }

    public function test_interactive_list_reply_uses_the_reply_id_as_option(): void
    {
        $payload = ['entry' => [['changes' => [['value' => [
            'messages' => [['from' => '5585999990000', 'id' => 'wamid.i', 'type' => 'interactive', 'interactive' => ['list_reply' => ['id' => '2', 'title' => 'Suporte']]]],
        ]]]]]];

        $this->postMeta($payload)->assertOk();

        $this->assertSame('2', WhatsAppWebhookLog::firstOrFail()->option);
    }

    public function test_authorization_runs_before_validation_so_unsigned_garbage_gets_403_not_422(): void
    {
        $this->postMeta(['entry' => 'invalido'], 'wrong-secret')->assertForbidden();
        $this->postMeta(['entry' => 'invalido'])->assertStatus(422);
    }
}
