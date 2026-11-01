<?php

namespace App\Filament\Resources\Demands\Pages;

use App\Filament\Resources\Demands\WhatsAppDemandResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditWhatsAppDemand extends EditRecord
{
    protected static string $resource = WhatsAppDemandResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()
                ->icon(Heroicon::OutlinedEye),
            DeleteAction::make()
                ->icon(Heroicon::OutlinedTrash),
        ];
    }
}
