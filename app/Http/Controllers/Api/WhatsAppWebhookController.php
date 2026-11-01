<?php

namespace App\Http\Controllers\Api;

use App\Enums\WhatsAppDriver;
use App\Http\Controllers\Controller;
use App\Services\Webhook\HandleWebhookService;
use App\Services\Webhook\VerifyWebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class WhatsAppWebhookController extends Controller
{
    public function verify(Request $request, string $driver, VerifyWebhookService $service): Response
    {
        abort_unless(WhatsAppDriver::from($driver) === WhatsAppDriver::Meta, 404);

        $challenge = $service->run($request);

        abort_if($challenge === null, 403, 'Token de verificação inválido.');

        return response($challenge, 200, ['Content-Type' => 'text/plain']);
    }

    public function handle(Request $request, string $driver, HandleWebhookService $service): JsonResponse|Response
    {
        $driverEnum = WhatsAppDriver::from($driver);

        $result = $service->run($driverEnum, $request);

        if ($driverEnum === WhatsAppDriver::Twilio) {
            return response('<Response></Response>', 200, ['Content-Type' => 'text/xml']);
        }

        return response()->json($result);
    }
}
