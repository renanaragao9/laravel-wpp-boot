<?php

declare(strict_types=1);

namespace App\Services\WhatsApp\Drivers;

use App\Contracts\WhatsAppProvider;
use App\Enums\WhatsAppDriver;
use App\Support\WhatsApp\IncomingMessage;
use App\Support\WhatsApp\Phone;
use App\Support\WhatsApp\SendResult;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Throwable;

class MetaProvider implements WhatsAppProvider
{
    public function driver(): WhatsAppDriver
    {
        return WhatsAppDriver::Meta;
    }

    public function verifyChallenge(Request $request): ?string
    {
        $token = (string) config('whatsapp.meta.verify_token');

        if ($token === '' || $request->query('hub_mode') !== 'subscribe') {
            return null;
        }

        if (! hash_equals($token, (string) $request->query('hub_verify_token'))) {
            return null;
        }

        return (string) $request->query('hub_challenge');
    }

    public function authorize(Request $request): bool
    {
        $secret = (string) config('whatsapp.meta.app_secret');
        $header = (string) $request->header('X-Hub-Signature-256');

        if ($secret === '' || ! str_starts_with($header, 'sha256=')) {
            return false;
        }

        return hash_equals('sha256='.hash_hmac('sha256', $request->getContent(), $secret), $header);
    }

    public function parse(Request $request): array
    {
        $messages = [];

        foreach ((array) $request->input('entry', []) as $entry) {
            foreach ((array) ($entry['changes'] ?? []) as $change) {
                $value = (array) ($change['value'] ?? []);
                $names = [];

                foreach ((array) ($value['contacts'] ?? []) as $contact) {
                    $names[(string) ($contact['wa_id'] ?? '')] = $contact['profile']['name'] ?? null;
                }

                foreach ((array) ($value['messages'] ?? []) as $message) {
                    $from = Phone::digits((string) ($message['from'] ?? ''));
                    $text = $this->text($message);

                    if ($from === '' || $text === null) {
                        continue;
                    }

                    $messages[] = new IncomingMessage(
                        driver: WhatsAppDriver::Meta,
                        phone: $from,
                        name: $names[$from] ?? null,
                        messageId: $message['id'] ?? null,
                        messageType: $message['type'] ?? null,
                        text: $text['text'],
                        option: $text['option'],
                        instance: $value['metadata']['phone_number_id'] ?? null,
                        event: 'messages',
                    );
                }
            }
        }

        return $messages;
    }

    public function sendText(string $phone, string $text): SendResult
    {
        $config = (array) config('whatsapp.meta');

        if (empty($config['access_token']) || empty($config['phone_number_id'])) {
            return new SendResult(false, error: 'Configuração da Cloud API (Meta) ausente.');
        }

        $url = "https://graph.facebook.com/{$config['graph_version']}/{$config['phone_number_id']}/messages";

        try {
            $response = Http::withToken((string) $config['access_token'])->acceptJson()->post($url, [
                'messaging_product' => 'whatsapp',
                'to' => Phone::digits($phone),
                'type' => 'text',
                'text' => ['body' => $text],
            ]);
        } catch (Throwable $e) {
            return new SendResult(false, error: $e->getMessage());
        }

        if ($response->failed()) {
            return new SendResult(false, error: 'Meta retornou '.$response->status().': '.$response->body());
        }

        return new SendResult(true, $response->json('messages.0.id'));
    }

    private function text(array $message): ?array
    {
        $type = $message['type'] ?? null;

        if ($type === 'text') {
            $body = trim((string) ($message['text']['body'] ?? ''));

            return $body === '' ? null : ['text' => $body, 'option' => $body];
        }

        if ($type === 'interactive') {
            $reply = $message['interactive']['list_reply'] ?? $message['interactive']['button_reply'] ?? null;

            return $reply === null ? null : ['text' => $reply['title'] ?? null, 'option' => $reply['id'] ?? null];
        }

        if ($type === 'button') {
            return ['text' => $message['button']['text'] ?? null, 'option' => $message['button']['payload'] ?? ($message['button']['text'] ?? null)];
        }

        return null;
    }
}
