<?php

namespace App\Filament\Actions;

use App\Models\WhatsAppDemand;
use App\Services\Demand\ReplyDemandService;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;

class ReplyDemandAction
{
    public static function make(): Action
    {
        return Action::make('reply')
            ->label('Responder')
            ->icon(Heroicon::OutlinedPaperAirplane)
            ->modalHeading(fn (WhatsAppDemand $record) => 'Responder '.($record->push_name ?: $record->phone))
            ->modalSubmitActionLabel('Enviar')
            ->schema([
                Textarea::make('message')
                    ->label('Mensagem')
                    ->required()
                    ->maxLength(4000)
                    ->rows(5),
            ])
            ->visible(fn (WhatsAppDemand $record) => $record->status->value !== 'done')
            ->action(function (WhatsAppDemand $record, array $data): void {
                $result = app(ReplyDemandService::class)->run($record, $data['message'], auth()->id());

                if ($result->success) {
                    Notification::make()->title('Mensagem enviada')->success()->send();

                    return;
                }

                Notification::make()->title('Falha ao enviar')->body($result->error)->danger()->send();
            });
    }
}
