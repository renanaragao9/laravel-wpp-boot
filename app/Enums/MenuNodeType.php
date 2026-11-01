<?php

namespace App\Enums;

enum MenuNodeType: string
{
    case Menu = 'menu';
    case Sector = 'sector';

    public function label(): string
    {
        return match ($this) {
            self::Menu => 'Menu',
            self::Sector => 'Setor (abre demanda)',
        };
    }
}
