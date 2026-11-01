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

class EvolutionProvider implements WhatsAppProvider
{
    public function driver(): WhatsAppDriver
    {
        return WhatsAppDriver::Evolution;
    }

    public function authorize(Request $request): bool
    {
        $expected = (string) config('whatsapp.evolution.webhook_token');

        if ($expected === '') {
            return false;
        }

        $received = (string) ($request->header('apikey') ?? $request->header('x-webhook-token') ?? $request->query('token') ?? '');

        return hash_equals($expected, $received);
    }

    public function parse(Request $request): array
    {
        if ($request->input('event') !== 'messages.upsert') {
            return [];
        }

        $data = (array) $request->input('data', []);
        $key = (array) ($data['key'] ?? []);
        $remoteJid = $key['remoteJid'] ?? null;

        if (($key['fromMe'] ?? false) || $remoteJid === null || str_contains((string) $remoteJid, '@g.us')) {
            return [];
        }

        $message = (array) ($data['message'] ?? []);
        $selectedRowId = $message['listResponseMessage']['singleSelectReply']['selectedRowId'] ?? null;
        $text = $message['conversation'] ?? $message['extendedTextMessage']['text'] ?? null;
        $text = $text === null ? null : trim((string) $text);
        $text = $text === '' ? null : $text;

        if ($selectedRowId === null && $text === null) {
            return [];
        }

        return [new IncomingMessage(
            driver: WhatsAppDriver::Evolution,
            phone: Phone::digits((string) $remoteJid),
            name: $data['pushName'] ?? null,
            messageId: $key['id'] ?? null,
            messageType: $data['messageType'] ?? null,
            text: $text,
            option: $selectedRowId ?? $text,
            instance: $request->input('instance'),
            event: 'messages.upsert',
        )];
    }

    public function sendText(string $phone, string $text): SendResult
    {
        $config = (array) config('whatsapp.evolution');

        if (empty($config['base_url']) || empty($config['api_key']) || empty($config['instance'])) {
            return new SendResult(false, error: 'Configuração da Evolution API ausente.');
        }

        $url = rtrim((string) $config['base_url'], '/')."/message/sendText/{$config['instance']}";

        try {
            $response = Http::withHeaders(['apikey' => (string) $config['api_key']])->acceptJson()->post($url, [
                'number' => Phone::digits($phone),
                'text' => $text,
            ]);
        } catch (Throwable $e) {
            return new SendResult(false, error: $e->getMessage());
        }

        if ($response->failed()) {
            return new SendResult(false, error: 'Evolution retornou '.$response->status().': '.$response->body());
        }

        return new SendResult(true, $response->json('key.id'));
    }
}
