<?php

namespace App\Filament\Resources\WebhookLogs\Schemas;

use App\Models\WhatsAppWebhookLog;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class WhatsAppWebhookLogInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Webhook')
                    ->columns(2)
                    ->collapsible()
                    ->collapsed(false)
                    ->schema([
                        TextEntry::make('driver')
                            ->label('Provedor')
                            ->badge()
                            ->formatStateUsing(fn ($state) => $state->label()),

                        TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->formatStateUsing(fn ($state) => $state->label()),

                        TextEntry::make('event')
                            ->label('Evento')
                            ->placeholder('-'),

                        TextEntry::make('instance')
                            ->label('Instância')
                            ->placeholder('-'),

                        TextEntry::make('phone')
                            ->label('Telefone')
                            ->placeholder('-'),

                        TextEntry::make('push_name')
                            ->label('Nome no WhatsApp')
                            ->placeholder('-'),

                        TextEntry::make('message_id')
                            ->label('ID da mensagem')
                            ->placeholder('-'),

                        TextEntry::make('message_type')
                            ->label('Tipo')
                            ->placeholder('-'),

                        TextEntry::make('option')
                            ->label('Opção')
                            ->placeholder('-'),

                        TextEntry::make('text')
                            ->label('Texto')
                            ->placeholder('-'),

                        TextEntry::make('error')
                            ->label('Erro')
                            ->placeholder('-')
                            ->columnSpanFull(),

                        TextEntry::make('created_at')
                            ->label('Recebido em')
                            ->dateTime('d/m/Y H:i:s'),
                    ]),

                Section::make('Payload')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        TextEntry::make('raw_payload')
                            ->hiddenLabel()
                            ->state(fn (WhatsAppWebhookLog $record) => json_encode($record->raw_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))
                            ->extraAttributes(['style' => 'white-space:pre-wrap;font-family:monospace;font-size:12px;'])
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
