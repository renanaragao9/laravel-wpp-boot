<?php

namespace App\Filament\Resources\Demands\Tables;

use App\Enums\DemandStatus;
use App\Filament\Actions\CloseDemandAction;
use App\Filament\Actions\ReplyDemandAction;
use App\Filament\Filters\Common\CreatedAtFilter;
use App\Filament\Filters\Common\DriverFilter;
use App\Filament\Filters\Demand\AssignedUserFilter;
use App\Filament\Filters\Demand\SectorFilter;
use App\Filament\Filters\Demand\StatusFilter;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DemandsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Aberta em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                TextColumn::make('push_name')
                    ->label('Contato')
                    ->placeholder('-')
                    ->searchable(),

                TextColumn::make('phone')
                    ->label('Telefone')
                    ->searchable(),

                TextColumn::make('sector')
                    ->label('Setor')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state->label())
                    ->color(fn (DemandStatus $state) => match ($state) {
                        DemandStatus::Open => 'warning',
                        DemandStatus::InProgress => 'info',
                        DemandStatus::Done => 'success',
                        default => 'gray',
                    }),

                TextColumn::make('driver')
                    ->label('Provedor')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state->label())
                    ->toggleable(),

                TextColumn::make('assignedUser.name')
                    ->label('Atendente')
                    ->placeholder('-'),
            ])
            ->filters([
                StatusFilter::make(),
                SectorFilter::make(),
                DriverFilter::make(),
                AssignedUserFilter::make(),
                CreatedAtFilter::make(),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                ReplyDemandAction::make(),

                CloseDemandAction::make(),

                ViewAction::make()
                    ->icon(Heroicon::OutlinedEye),

                EditAction::make()
                    ->icon(Heroicon::OutlinedPencil),
            ]);
    }
}
