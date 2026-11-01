<?php

declare(strict_types=1);

namespace App\Services\Webhook;

use App\Services\WhatsApp\Drivers\MetaProvider;
use Illuminate\Http\Request;

class VerifyWebhookService
{
    public function __construct(private readonly MetaProvider $meta) {}

    public function run(Request $request): ?string
    {
        return $this->meta->verifyChallenge($request);
    }
}
