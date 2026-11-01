<?php

namespace App\Filament\Filters\Demand;

use Filament\Tables\Filters\SelectFilter;

class AssignedUserFilter
{
    public static function make(): SelectFilter
    {
        return SelectFilter::make('assigned_user_id')
            ->label('Atendente')
            ->relationship('assignedUser', 'name')
            ->searchable()
            ->preload();
    }
}
