<?php

namespace App\Filament\Filters\MenuNode;

use Filament\Tables\Filters\TernaryFilter;

class ActiveFilter
{
    public static function make(): TernaryFilter
    {
        return TernaryFilter::make('active')
            ->label('Ativo')
            ->placeholder('Todos')
            ->trueLabel('Ativos')
            ->falseLabel('Inativos');
    }
}
