<?php

namespace App\Http\Controllers\Api;

use App\Enums\WhatsAppDriver;
use App\Http\Controllers\Controller;
use App\Http\Requests\Webhook\VerifyWhatsAppWebhookRequest;
use App\Http\Requests\Webhook\WhatsAppWebhookRequest;
use App\Services\Webhook\HandleWebhookService;
use App\Services\Webhook\VerifyWebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class WhatsAppWebhookController extends Controller
{
    public function verify(
        VerifyWhatsAppWebhookRequest $verifyWhatsAppWebhookRequest,
        string $driver,
        VerifyWebhookService $verifyWebhookService
    ): Response {
        abort_unless(WhatsAppDriver::from($driver) === WhatsAppDriver::Meta, 404);

        $data = $verifyWhatsAppWebhookRequest->validated();
        $challenge = $verifyWebhookService->run($data);

        abort_if($challenge === null, 403, 'Token de verificação inválido.');

        return response($challenge, 200, ['Content-Type' => 'text/plain']);
    }

    public function handle(
        WhatsAppWebhookRequest $whatsAppWebhookRequest,
        string $driver,
        HandleWebhookService $handleWebhookService
    ): JsonResponse|Response {
        $driverEnum = WhatsAppDriver::from($driver);

        $result = $handleWebhookService->run($driverEnum, $whatsAppWebhookRequest);

        if ($driverEnum === WhatsAppDriver::Twilio) {
            return response('<Response></Response>', 200, ['Content-Type' => 'text/xml']);
        }

        return response()->json($result);
    }
}
