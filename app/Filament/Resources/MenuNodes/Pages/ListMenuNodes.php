<?php

namespace App\Filament\Resources\MenuNodes\Pages;

use App\Filament\Resources\MenuNodes\WhatsAppMenuNodeResource;
use App\Models\WhatsAppMenuNode;
use App\Services\Menu\BuildMenuTreeService;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ListMenuNodes extends ListRecords
{
    protected static string $resource = WhatsAppMenuNodeResource::class;

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            View::make('filament.menu-tree')
                ->viewData(fn (): array => [
                    'rows' => app(BuildMenuTreeService::class)->run(),
                    'createUrl' => fn (WhatsAppMenuNode $node): string => WhatsAppMenuNodeResource::getUrl('create', ['parent_id' => $node->id]),
                    'editUrl' => fn (WhatsAppMenuNode $node): string => WhatsAppMenuNodeResource::getUrl('edit', ['record' => $node]),
                ]),

            EmbeddedTable::make(),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(fn () => WhatsAppMenuNode::root() === null ? 'Criar menu principal' : 'Nova opção')
                ->icon(Heroicon::OutlinedPlus),
        ];
    }
}
