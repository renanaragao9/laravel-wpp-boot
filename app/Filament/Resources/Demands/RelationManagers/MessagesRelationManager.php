<?php

namespace App\Filament\Resources\Demands\RelationManagers;

use App\Enums\MessageDirection;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MessagesRelationManager extends RelationManager
{
    protected static string $relationship = 'messages';

    protected static ?string $title = 'Mensagens';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Data')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable(),

                TextColumn::make('direction')
                    ->label('Sentido')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state->label())
                    ->color(fn (MessageDirection $state) => $state === MessageDirection::Inbound ? 'gray' : 'success'),

                TextColumn::make('body')
                    ->label('Mensagem')
                    ->wrap(),

                TextColumn::make('user.name')
                    ->label('Atendente')
                    ->placeholder('-'),
            ])
            ->defaultSort('created_at', 'asc');
    }
}
