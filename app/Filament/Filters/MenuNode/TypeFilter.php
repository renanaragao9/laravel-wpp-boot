<?php

namespace App\Filament\Filters\MenuNode;

use App\Enums\MenuNodeType;
use Filament\Tables\Filters\SelectFilter;

class TypeFilter
{
    public static function make(): SelectFilter
    {
        return SelectFilter::make('type')
            ->label('Tipo')
            ->options(collect(MenuNodeType::cases())->mapWithKeys(fn (MenuNodeType $s) => [$s->value => $s->label()]));
    }
}
