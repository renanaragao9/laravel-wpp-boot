<?php

namespace App\Filament\Resources\WebhookLogs\Tables;

use App\Enums\WebhookLogStatus;
use App\Filament\Filters\Common\CreatedAtFilter;
use App\Filament\Filters\Common\DriverFilter;
use App\Filament\Filters\WebhookLog\StatusFilter;
use Filament\Actions\ViewAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class WebhookLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Recebido em')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable(),

                TextColumn::make('driver')
                    ->label('Provedor')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state->label()),

                TextColumn::make('phone')
                    ->label('Telefone')
                    ->placeholder('-')
                    ->searchable(),

                TextColumn::make('text')
                    ->label('Texto')
                    ->placeholder('-')
                    ->limit(50)
                    ->searchable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state->label())
                    ->color(fn (WebhookLogStatus $state) => match ($state) {
                        WebhookLogStatus::Processed => 'success',
                        WebhookLogStatus::Failed => 'danger',
                        WebhookLogStatus::Ignored => 'gray',
                        default => 'warning',
                    }),

                TextColumn::make('error')
                    ->label('Erro')
                    ->limit(40)
                    ->placeholder('-')
                    ->toggleable(),
            ])
            ->filters([
                DriverFilter::make(),
                StatusFilter::make(),
                CreatedAtFilter::make(),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                ViewAction::make()
                    ->icon(Heroicon::OutlinedEye),
            ]);
    }
}
