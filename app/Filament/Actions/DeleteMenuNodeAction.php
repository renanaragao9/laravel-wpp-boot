<?php

namespace App\Filament\Actions;

use App\Models\WhatsAppMenuNode;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;

class DeleteMenuNodeAction
{
    public static function make(): DeleteAction
    {
        return DeleteAction::make()
            ->icon(Heroicon::OutlinedTrash)
            ->before(function (WhatsAppMenuNode $record, DeleteAction $action): void {
                if (! $record->children()->exists()) {
                    return;
                }

                Notification::make()
                    ->title('Não é possível remover')
                    ->body('Esta opção tem itens dentro dela. Remova ou mova os itens primeiro.')
                    ->danger()
                    ->send();

                $action->cancel();
            });
    }
}
