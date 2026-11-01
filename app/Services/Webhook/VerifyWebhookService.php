<?php

declare(strict_types=1);

namespace App\Services\Webhook;

use App\Services\WhatsApp\Drivers\MetaProvider;

class VerifyWebhookService
{
    public function __construct(private readonly MetaProvider $meta) {}

    public function run(array $data): ?string
    {
        return $this->meta->verifyChallenge($data);
    }
}
