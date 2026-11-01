<?php

namespace App\Filament\Resources\Demands\Schemas;

use App\Enums\DemandStatus;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class WhatsAppDemandForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Section::make('Atendimento')->columnSpanFull()->columns(2)->schema([
                Select::make('status')
                    ->label('Status')
                    ->options(collect(DemandStatus::cases())->mapWithKeys(fn (DemandStatus $s) => [$s->value => $s->label()]))
                    ->required(),

                Select::make('assigned_user_id')
                    ->label('Atendente')
                    ->relationship('assignedUser', 'name')
                    ->searchable()
                    ->preload(),

                Textarea::make('notes')
                    ->label('Observações internas')
                    ->rows(4)
                    ->columnSpanFull(),
            ]),
        ]);
    }
}
