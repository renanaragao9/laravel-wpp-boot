<?php

namespace App\Filament\Resources\MenuNodes\Pages;

use App\Filament\Actions\DeleteMenuNodeAction;
use App\Filament\Resources\MenuNodes\WhatsAppMenuNodeResource;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditWhatsAppMenuNode extends EditRecord
{
    protected static string $resource = WhatsAppMenuNodeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()
                ->icon(Heroicon::OutlinedEye),
            DeleteMenuNodeAction::make(),
        ];
    }
}
