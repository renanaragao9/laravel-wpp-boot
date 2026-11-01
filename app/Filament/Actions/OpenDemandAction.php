<?php

namespace App\Filament\Actions;

use App\Enums\DemandStatus;
use App\Enums\WhatsAppDriver;
use App\Filament\Resources\Demands\WhatsAppDemandResource;
use App\Models\WhatsAppDemand;
use App\Services\Demand\MarkDemandAsReadService;
use App\Services\Demand\ReplyDemandService;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Livewire\Component;

class OpenDemandAction
{
    public static function make(): Action
    {
        return Action::make('open')
            ->label('Abrir')
            ->icon(Heroicon::OutlinedChatBubbleLeftRight)
            ->color('gray')
            ->size('sm')
            ->slideOver()
            ->modalWidth(Width::TwoExtraLarge)
            ->modalHeading(fn (WhatsAppDemand $record) => $record->title)
            ->modalDescription(fn (WhatsAppDemand $record) => ($record->push_name ?: 'Sem nome').' · '.($record->driver === WhatsAppDriver::Web ? 'Chat web' : $record->phone.' · '.$record->driver->label()))
            ->modalSubmitActionLabel('Enviar')
            ->modalCancelActionLabel('Fechar')
            ->modalSubmitAction(fn (WhatsAppDemand $record) => $record->status === DemandStatus::Done ? false : null)
            ->mountUsing(function (WhatsAppDemand $record, Schema $schema): void {
                app(MarkDemandAsReadService::class)->run($record);
                $schema->fill();
            })
            ->schema([
                View::make('filament.demand-thread')
                    ->viewData(fn (WhatsAppDemand $record): array => [
                        'messages' => $record->messages()->with('user')->orderBy('id')->get(),
                    ]),

                Textarea::make('message')
                    ->label('Responder')
                    ->rows(3)
                    ->maxLength(4000)
                    ->required()
                    ->visible(fn (WhatsAppDemand $record) => $record->status !== DemandStatus::Done),
            ])
            ->extraModalFooterActions(fn (WhatsAppDemand $record) => [
                Action::make('details')
                    ->label('Ver detalhes')
                    ->color('gray')
                    ->url(WhatsAppDemandResource::getUrl('view', ['record' => $record])),
            ])
            ->action(function (WhatsAppDemand $record, array $data, Action $action, Component $livewire): void {
                $result = app(ReplyDemandService::class)->run($record, $data['message'], auth()->id());

                if ($result->success) {
                    Notification::make()->title('Mensagem enviada')->success()->send();
                    $livewire->mountedActions[array_key_last($livewire->mountedActions)]['data']['message'] = '';
                } else {
                    Notification::make()->title('Falha ao enviar')->body($result->error)->danger()->send();
                }

                $action->halt();
            });
    }
}
