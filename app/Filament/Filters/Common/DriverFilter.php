<?php

namespace App\Filament\Filters\Common;

use App\Enums\WhatsAppDriver;
use Filament\Tables\Filters\SelectFilter;

class DriverFilter
{
    public static function make(): SelectFilter
    {
        return SelectFilter::make('driver')
            ->label('Provedor')
            ->options(collect(WhatsAppDriver::cases())->mapWithKeys(fn (WhatsAppDriver $d) => [$d->value => $d->label()]));
    }
}
