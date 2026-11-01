<?php

declare(strict_types=1);

namespace App\Support\WhatsApp;

use App\Enums\WhatsAppDriver;

final readonly class IncomingMessage
{
    public function __construct(
        public WhatsAppDriver $driver,
        public string $phone,
        public ?string $name,
        public ?string $messageId,
        public ?string $messageType,
        public ?string $text,
        public ?string $option,
        public ?string $instance = null,
        public ?string $event = null,
    ) {}
}
