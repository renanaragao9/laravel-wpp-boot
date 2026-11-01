<?php

namespace App\Enums;

enum WhatsAppDriver: string
{
    case Meta = 'meta';
    case Evolution = 'evolution';
    case Twilio = 'twilio';
    case Web = 'web';

    public function label(): string
    {
        return match ($this) {
            self::Meta => 'WhatsApp Cloud API (Meta)',
            self::Evolution => 'Evolution API',
            self::Twilio => 'Twilio',
            self::Web => 'Web (chat do site)',
        };
    }
}
