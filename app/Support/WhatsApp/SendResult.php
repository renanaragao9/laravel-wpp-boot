<?php

declare(strict_types=1);

namespace App\Support\WhatsApp;

final readonly class SendResult
{
    public function __construct(
        public bool $success,
        public ?string $messageId = null,
        public ?string $error = null,
    ) {}
}
