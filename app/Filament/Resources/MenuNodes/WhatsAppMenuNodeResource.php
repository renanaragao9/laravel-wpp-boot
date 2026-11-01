<?php

namespace App\Filament\Resources\MenuNodes;

use App\Filament\Resources\MenuNodes\Pages\CreateWhatsAppMenuNode;
use App\Filament\Resources\MenuNodes\Pages\EditWhatsAppMenuNode;
use App\Filament\Resources\MenuNodes\Pages\ListMenuNodes;
use App\Filament\Resources\MenuNodes\Pages\ViewWhatsAppMenuNode;
use App\Filament\Resources\MenuNodes\Schemas\WhatsAppMenuNodeForm;
use App\Filament\Resources\MenuNodes\Schemas\WhatsAppMenuNodeInfolist;
use App\Filament\Resources\MenuNodes\Tables\MenuNodesTable;
use App\Models\WhatsAppMenuNode;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class WhatsAppMenuNodeResource extends Resource
{
    protected static ?string $model = WhatsAppMenuNode::class;

    protected static ?string $slug = 'menu';

    protected static ?string $recordTitleAttribute = 'label';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedListBullet;

    protected static ?string $modelLabel = 'Opção do menu';

    protected static ?string $pluralModelLabel = 'Menu do bot';

    protected static ?string $navigationLabel = 'Menu do bot';

    protected static string|UnitEnum|null $navigationGroup = 'Bot';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return WhatsAppMenuNodeForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return WhatsAppMenuNodeInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MenuNodesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMenuNodes::route('/'),
            'create' => CreateWhatsAppMenuNode::route('/create'),
            'view' => ViewWhatsAppMenuNode::route('/{record}'),
            'edit' => EditWhatsAppMenuNode::route('/{record}/edit'),
        ];
    }
}
