<?php

namespace App\Filament\Resources\WebhookLogs;

use App\Filament\Resources\WebhookLogs\Pages\ListWebhookLogs;
use App\Filament\Resources\WebhookLogs\Pages\ViewWhatsAppWebhookLog;
use App\Filament\Resources\WebhookLogs\Schemas\WhatsAppWebhookLogInfolist;
use App\Filament\Resources\WebhookLogs\Tables\WebhookLogsTable;
use App\Models\WhatsAppWebhookLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class WhatsAppWebhookLogResource extends Resource
{
    protected static ?string $model = WhatsAppWebhookLog::class;

    protected static ?string $slug = 'logs-webhook';

    protected static ?string $recordTitleAttribute = 'phone';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBolt;

    protected static ?string $modelLabel = 'Log de webhook';

    protected static ?string $pluralModelLabel = 'Logs de webhook';

    protected static ?string $navigationLabel = 'Logs de webhook';

    protected static string|UnitEnum|null $navigationGroup = 'Bot';

    protected static ?int $navigationSort = 2;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function infolist(Schema $schema): Schema
    {
        return WhatsAppWebhookLogInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WebhookLogsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWebhookLogs::route('/'),
            'view' => ViewWhatsAppWebhookLog::route('/{record}'),
        ];
    }
}
