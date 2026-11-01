<?php

namespace Tests\Feature\Webhook;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Support\SimpleMenuSeeder;
use Tests\TestCase;

abstract class WebhookTestCase extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'queue.default' => 'sync',
            'whatsapp.meta' => ['verify_token' => 'vt', 'app_secret' => 'secret', 'access_token' => 'tok', 'phone_number_id' => '123', 'graph_version' => 'v21.0'],
            'whatsapp.evolution' => ['base_url' => 'https://evo.test', 'api_key' => 'k', 'instance' => 'inst', 'webhook_token' => 'evo-token'],
            'whatsapp.twilio' => ['account_sid' => 'AC1', 'auth_token' => 'twtoken', 'from' => 'whatsapp:+14155238886'],
        ]);

        Http::fake([
            'graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.out']]]),
            'evo.test/*' => Http::response(['key' => ['id' => 'evo-out']]),
            'api.twilio.com/*' => Http::response(['sid' => 'SMout']),
        ]);

        $this->seed($this->menuSeeder());
    }

    protected function metaPayload(string $from, string $body, string $id = 'wamid.1'): array
    {
        return ['entry' => [['changes' => [['value' => [
            'metadata' => ['phone_number_id' => '123'],
            'contacts' => [['wa_id' => $from, 'profile' => ['name' => 'Maria']]],
            'messages' => [['from' => $from, 'id' => $id, 'type' => 'text', 'text' => ['body' => $body]]],
        ]]]]]];
    }

    protected function postMeta(array $payload, ?string $secret = 'secret')
    {
        $raw = json_encode($payload);
        $headers = ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'];

        if ($secret !== null) {
            $headers['HTTP_X_HUB_SIGNATURE_256'] = 'sha256='.hash_hmac('sha256', $raw, $secret);
        }

        return $this->call('POST', '/api/webhooks/meta', [], [], [], $headers, $raw);
    }

    protected function evolutionPayload(string $jid, string $text, string $id = 'EVO1', bool $fromMe = false): array
    {
        return [
            'event' => 'messages.upsert',
            'instance' => 'inst',
            'data' => ['key' => ['remoteJid' => $jid, 'fromMe' => $fromMe, 'id' => $id], 'pushName' => 'João', 'messageType' => 'conversation', 'message' => ['conversation' => $text]],
        ];
    }

    protected function postTwilio(array $params, string $token = 'twtoken')
    {
        $url = 'http://localhost/api/webhooks/twilio';
        $sorted = $params;
        ksort($sorted);
        $data = $url;

        foreach ($sorted as $k => $v) {
            $data .= $k.$v;
        }

        return $this->post($url, $params, ['X-Twilio-Signature' => base64_encode(hash_hmac('sha1', $data, $token, true))]);
    }

    protected function menuSeeder(): string
    {
        return SimpleMenuSeeder::class;
    }
}
