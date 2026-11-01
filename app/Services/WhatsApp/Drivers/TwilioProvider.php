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

class TwilioProvider implements WhatsAppProvider
{
    public function driver(): WhatsAppDriver
    {
        return WhatsAppDriver::Twilio;
    }

    public function authorize(Request $request): bool
    {
        $token = (string) config('whatsapp.twilio.auth_token');
        $signature = (string) $request->header('X-Twilio-Signature');

        if ($token === '' || $signature === '') {
            return false;
        }

        $params = $request->post();
        ksort($params);

        $data = $request->fullUrl();

        foreach ($params as $key => $value) {
            $data .= $key.(is_array($value) ? implode('', $value) : $value);
        }

        return hash_equals(base64_encode(hash_hmac('sha1', $data, $token, true)), $signature);
    }

    public function parse(Request $request): array
    {
        $from = (string) $request->input('From', '');
        $body = trim((string) $request->input('Body', ''));
        $option = $request->input('ListId') ?? $request->input('ButtonPayload') ?? ($body === '' ? null : $body);

        if (! str_starts_with($from, 'whatsapp:') || ($body === '' && $option === null)) {
            return [];
        }

        return [new IncomingMessage(
            driver: WhatsAppDriver::Twilio,
            phone: Phone::digits($from),
            name: $request->input('ProfileName'),
            messageId: $request->input('MessageSid'),
            messageType: $request->input('MessageType'),
            text: $body === '' ? null : $body,
            option: $option === null ? null : (string) $option,
            instance: $request->input('To'),
            event: 'incoming',
        )];
    }

    public function sendText(string $phone, string $text): SendResult
    {
        $config = (array) config('whatsapp.twilio');

        if (empty($config['account_sid']) || empty($config['auth_token']) || empty($config['from'])) {
            return new SendResult(false, error: 'Configuração do Twilio ausente.');
        }

        $from = str_starts_with((string) $config['from'], 'whatsapp:') ? $config['from'] : 'whatsapp:'.$config['from'];

        try {
            $response = Http::withBasicAuth((string) $config['account_sid'], (string) $config['auth_token'])
                ->asForm()
                ->acceptJson()
                ->post("https://api.twilio.com/2010-04-01/Accounts/{$config['account_sid']}/Messages.json", [
                    'From' => $from,
                    'To' => 'whatsapp:+'.Phone::digits($phone),
                    'Body' => $text,
                ]);
        } catch (Throwable $e) {
            return new SendResult(false, error: $e->getMessage());
        }

        if ($response->failed()) {
            return new SendResult(false, error: 'Twilio retornou '.$response->status().': '.$response->body());
        }

        return new SendResult(true, $response->json('sid'));
    }
}
