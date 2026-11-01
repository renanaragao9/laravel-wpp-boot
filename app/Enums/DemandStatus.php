<?php

namespace App\Enums;

enum DemandStatus: string
{
    case Open = 'open';
    case InProgress = 'in_progress';
    case Waiting = 'waiting';
    case Paused = 'paused';
    case Done = 'done';

    public static function active(): array
    {
        return [self::Open, self::InProgress, self::Waiting, self::Paused];
    }

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Aberta',
            self::InProgress => 'Em andamento',
            self::Waiting => 'Aguardando',
            self::Paused => 'Pausada',
            self::Done => 'Concluída',
        };
    }
}
