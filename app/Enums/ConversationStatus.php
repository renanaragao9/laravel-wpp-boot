<?php

namespace App\Enums;

enum ConversationStatus: string
{
    case Navigating = 'navigating';
    case WithAgent = 'with_agent';
    case Finished = 'finished';

    public static function active(): array
    {
        return [self::Navigating, self::WithAgent];
    }

    public function label(): string
    {
        return match ($this) {
            self::Navigating => 'Navegando',
            self::WithAgent => 'Com atendente',
            self::Finished => 'Finalizada',
        };
    }
}
