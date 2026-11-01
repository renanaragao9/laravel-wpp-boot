<?php

namespace App\Filament\Widgets;

use App\Enums\WebhookLogStatus;
use App\Filament\Resources\WebhookLogs\WhatsAppWebhookLogResource;
use App\Models\WhatsAppWebhookLog;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class FailedWebhooksTable extends TableWidget
{
    protected static ?int $sort = 7;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Webhooks com falha')
            ->description('As falhas mais recentes ao processar mensagens recebidas.')
            ->query(fn () => WhatsAppWebhookLog::query()
                ->where('status', WebhookLogStatus::Failed->value)
                ->latest())
            ->columns([
                TextColumn::make('created_at')
                    ->label('Recebido')
                    ->since(),

                TextColumn::make('driver')
                    ->label('Canal')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state->label()),

                TextColumn::make('phone')
                    ->label('Telefone')
                    ->placeholder('-'),

                TextColumn::make('error')
                    ->label('Erro')
                    ->limit(70)
                    ->tooltip(fn (WhatsAppWebhookLog $record) => $record->error),
            ])
            ->recordActions([
                Action::make('view')
                    ->label('Ver')
                    ->icon(Heroicon::OutlinedEye)
                    ->url(fn (WhatsAppWebhookLog $record) => WhatsAppWebhookLogResource::getUrl('view', ['record' => $record])),
            ])
            ->paginated([5])
            ->emptyStateHeading('Nenhuma falha')
            ->emptyStateDescription('Todos os webhooks recebidos foram processados.')
            ->emptyStateIcon(Heroicon::OutlinedCheckCircle);
    }
}
