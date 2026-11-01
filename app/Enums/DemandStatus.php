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

    public function hex(): string
    {
        return match ($this) {
            self::Open => '#3b82f6',
            self::InProgress => '#f59e0b',
            self::Waiting => '#8b5cf6',
            self::Paused => '#9ca3af',
            self::Done => '#10b981',
        };
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
