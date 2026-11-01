<?php

namespace App\Filament\Filters\Demand;

use App\Enums\DemandStatus;
use Filament\Tables\Filters\SelectFilter;

class StatusFilter
{
    public static function make(): SelectFilter
    {
        return SelectFilter::make('status')
            ->label('Status')
            ->options(collect(DemandStatus::cases())->mapWithKeys(fn (DemandStatus $s) => [$s->value => $s->label()]));
    }
}
