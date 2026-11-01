<?php

namespace Tests\Feature\Webhook;

use App\Enums\WebhookLogStatus;
use App\Models\WhatsAppWebhookLog;
use Illuminate\Support\Facades\Http;

class TwilioWebhookTest extends WebhookTestCase
{
    private function params(string $body = 'oi', string $sid = 'SM1'): array
    {
        return ['MessageSid' => $sid, 'From' => 'whatsapp:+5585977770000', 'To' => 'whatsapp:+14155238886', 'Body' => $body, 'ProfileName' => 'Ana'];
    }

    public function test_invalid_signature_is_rejected(): void
    {
        $this->postTwilio($this->params(), 'wrong')->assertForbidden();
        $this->post('http://localhost/api/webhooks/twilio', $this->params())->assertForbidden();

        $this->assertDatabaseCount('whatsapp_webhook_logs', 0);
    }

    public function test_is_rejected_when_auth_token_is_not_configured(): void
    {
        config(['whatsapp.twilio.auth_token' => '']);

        $this->postTwilio($this->params(), '')->assertForbidden();
    }

    public function test_valid_message_is_processed_and_answered_with_empty_twiml(): void
    {
        $this->postTwilio($this->params())
            ->assertOk()
            ->assertHeader('Content-Type', 'text/xml; charset=UTF-8')
            ->assertSee('<Response></Response>', false);

        $log = WhatsAppWebhookLog::firstOrFail();
        $this->assertSame(WebhookLogStatus::Processed, $log->status);
        $this->assertSame('5585977770000', $log->phone);
        $this->assertSame('Ana', $log->push_name);

        Http::assertSent(fn ($request) => str_contains($request->url(), 'api.twilio.com/2010-04-01/Accounts/AC1/Messages.json')
            && $request['To'] === 'whatsapp:+5585977770000'
            && $request['From'] === 'whatsapp:+14155238886');
    }

    public function test_non_whatsapp_senders_are_ignored(): void
    {
        $params = ['MessageSid' => 'SM2', 'From' => '+5585977770000', 'Body' => 'sms'];

        $this->postTwilio($params)->assertOk();

        Http::assertNothingSent();
    }

    public function test_duplicated_message_sid_is_ignored(): void
    {
        $this->postTwilio($this->params('oi', 'SMdup'))->assertOk();
        $this->postTwilio($this->params('oi', 'SMdup'))->assertOk();

        $this->assertDatabaseCount('whatsapp_webhook_logs', 1);
    }
}
