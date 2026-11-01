<?php

namespace App\Filament\Resources\Demands\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class WhatsAppDemandInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Demanda')
                    ->columns(2)
                    ->collapsible()
                    ->collapsed(false)
                    ->schema([
                        TextEntry::make('title')
                            ->label('Título'),

                        TextEntry::make('sector')
                            ->label('Setor'),

                        TextEntry::make('push_name')
                            ->label('Nome no WhatsApp')
                            ->placeholder('-'),

                        TextEntry::make('phone')
                            ->label('Telefone')
                            ->copyable(),

                        TextEntry::make('driver')
                            ->label('Provedor')
                            ->badge()
                            ->formatStateUsing(fn ($state) => $state->label()),

                        TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->formatStateUsing(fn ($state) => $state->label()),

                        TextEntry::make('assignedUser.name')
                            ->label('Atendente')
                            ->placeholder('Sem atendente'),

                        TextEntry::make('notes')
                            ->label('Observações internas')
                            ->placeholder('-')
                            ->columnSpanFull(),

                        TextEntry::make('created_at')
                            ->label('Criada em')
                            ->dateTime('d/m/Y H:i')
                            ->placeholder('-'),

                        TextEntry::make('updated_at')
                            ->label('Atualizada em')
                            ->dateTime('d/m/Y H:i')
                            ->placeholder('-'),
                    ]),
            ]);
    }
}
