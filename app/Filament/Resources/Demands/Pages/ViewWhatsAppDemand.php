<?php

namespace App\Filament\Resources\Demands\Pages;

use App\Filament\Actions\CloseDemandAction;
use App\Filament\Actions\ReplyDemandAction;
use App\Filament\Resources\Demands\WhatsAppDemandResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class ViewWhatsAppDemand extends ViewRecord
{
    protected static string $resource = WhatsAppDemandResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ReplyDemandAction::make(),
            CloseDemandAction::make(),
            EditAction::make()
                ->icon(Heroicon::OutlinedPencil),
            DeleteAction::make()
                ->icon(Heroicon::OutlinedTrash),
        ];
    }
}
