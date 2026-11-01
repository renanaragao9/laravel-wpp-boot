<?php

namespace App\Filament\Resources\MenuNodes\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class WhatsAppMenuNodeInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Opção do menu')
                    ->columns(2)
                    ->collapsible()
                    ->collapsed(false)
                    ->schema([
                        TextEntry::make('label')
                            ->label('Título'),

                        TextEntry::make('parent.label')
                            ->label('Dentro de')
                            ->placeholder('Menu inicial (raiz)'),

                        TextEntry::make('option_number')
                            ->label('Número da opção')
                            ->placeholder('-'),

                        TextEntry::make('type')
                            ->label('Tipo')
                            ->badge()
                            ->formatStateUsing(fn ($state) => $state->label()),

                        TextEntry::make('sector')
                            ->label('Setor')
                            ->placeholder('-'),

                        IconEntry::make('active')
                            ->label('Ativo')
                            ->boolean(),

                        TextEntry::make('message')
                            ->label('Mensagem')
                            ->placeholder('-')
                            ->columnSpanFull(),

                        TextEntry::make('created_at')
                            ->label('Criado em')
                            ->dateTime('d/m/Y H:i')
                            ->placeholder('-'),

                        TextEntry::make('updated_at')
                            ->label('Atualizado em')
                            ->dateTime('d/m/Y H:i')
                            ->placeholder('-'),
                    ]),
            ]);
    }
}
