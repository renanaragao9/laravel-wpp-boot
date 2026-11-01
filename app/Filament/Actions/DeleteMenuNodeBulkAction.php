<?php

namespace App\Filament\Actions;

use App\Models\WhatsAppMenuNode;
use Filament\Actions\DeleteBulkAction;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Collection;

class DeleteMenuNodeBulkAction
{
    public static function make(): DeleteBulkAction
    {
        return DeleteBulkAction::make()
            ->before(function (Collection $records, DeleteBulkAction $action): void {
                if (! WhatsAppMenuNode::query()->whereIn('parent_id', $records->modelKeys())->exists()) {
                    return;
                }

                Notification::make()
                    ->title('Não é possível remover')
                    ->body('Alguma das opções selecionadas tem itens dentro dela. Remova ou mova os itens primeiro.')
                    ->danger()
                    ->send();

                $action->cancel();
            });
    }
}
