<?php

namespace App\Filament\Filters\Demand;

use App\Models\WhatsAppDemand;
use Filament\Tables\Filters\SelectFilter;

class SectorFilter
{
    public static function make(): SelectFilter
    {
        return SelectFilter::make('sector')
            ->label('Setor')
            ->options(fn () => WhatsAppDemand::query()->distinct()->orderBy('sector')->pluck('sector', 'sector'));
    }
}
