<?php

namespace App\Filament\Resources\MenuNodes\Pages;

use App\Filament\Actions\DeleteMenuNodeAction;
use App\Filament\Resources\MenuNodes\WhatsAppMenuNodeResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class ViewWhatsAppMenuNode extends ViewRecord
{
    protected static string $resource = WhatsAppMenuNodeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->icon(Heroicon::OutlinedPencil),
            DeleteMenuNodeAction::make(),
        ];
    }
}
