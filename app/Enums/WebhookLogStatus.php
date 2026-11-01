<?php

namespace App\Enums;

enum WebhookLogStatus: string
{
    case Received = 'received';
    case Processed = 'processed';
    case Ignored = 'ignored';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Received => 'Recebido',
            self::Processed => 'Processado',
            self::Ignored => 'Ignorado',
            self::Failed => 'Falhou',
        };
    }
}
