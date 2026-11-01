<?php

namespace App\Filament\Filters\WebhookLog;

use App\Enums\WebhookLogStatus;
use Filament\Tables\Filters\SelectFilter;

class StatusFilter
{
    public static function make(): SelectFilter
    {
        return SelectFilter::make('status')
            ->label('Status')
            ->options(collect(WebhookLogStatus::cases())->mapWithKeys(fn (WebhookLogStatus $s) => [$s->value => $s->label()]));
    }
}
