<?php

namespace App\Filament\Actions;

use App\Models\WhatsAppDemand;
use App\Services\Demand\CloseDemandService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;

class CloseDemandAction
{
    public static function make(): Action
    {
        return Action::make('close')
            ->label('Concluir')
            ->icon(Heroicon::OutlinedCheckCircle)
            ->color('success')
            ->requiresConfirmation()
            ->modalDescription('A conversa volta para o bot na próxima mensagem do contato.')
            ->visible(fn (WhatsAppDemand $record) => $record->status->value !== 'done')
            ->action(function (WhatsAppDemand $record): void {
                app(CloseDemandService::class)->run($record);

                Notification::make()->title('Demanda concluída')->success()->send();
            });
    }
}
