<?php

namespace App\Filament\Resources\Demands;

use App\Filament\Resources\Demands\Pages\EditWhatsAppDemand;
use App\Filament\Resources\Demands\Pages\ListDemands;
use App\Filament\Resources\Demands\Pages\ViewWhatsAppDemand;
use App\Filament\Resources\Demands\RelationManagers\MessagesRelationManager;
use App\Filament\Resources\Demands\Schemas\WhatsAppDemandForm;
use App\Filament\Resources\Demands\Schemas\WhatsAppDemandInfolist;
use App\Filament\Resources\Demands\Tables\DemandsTable;
use App\Models\WhatsAppDemand;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class WhatsAppDemandResource extends Resource
{
    protected static ?string $model = WhatsAppDemand::class;

    protected static ?string $slug = 'demandas';

    protected static ?string $recordTitleAttribute = 'title';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInboxArrowDown;

    protected static ?string $modelLabel = 'Demanda';

    protected static ?string $pluralModelLabel = 'Demandas';

    protected static ?string $navigationLabel = 'Demandas';

    protected static string|UnitEnum|null $navigationGroup = 'Atendimento';

    protected static ?int $navigationSort = 1;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return WhatsAppDemandForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return WhatsAppDemandInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DemandsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            MessagesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDemands::route('/'),
            'view' => ViewWhatsAppDemand::route('/{record}'),
            'edit' => EditWhatsAppDemand::route('/{record}/edit'),
        ];
    }
}
