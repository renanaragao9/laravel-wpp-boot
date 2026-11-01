<?php

namespace App\Filament\Pages;

use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class Dashboard extends BaseDashboard
{
    use HasFiltersForm;

    public function getColumns(): int|array
    {
        return 2;
    }

    public function filtersForm(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columnSpanFull()->columns(1)->schema([
                Select::make('period')
                    ->label('Período')
                    ->options([
                        7 => 'Últimos 7 dias',
                        30 => 'Últimos 30 dias',
                        90 => 'Últimos 90 dias',
                    ])
                    ->default(30)
                    ->native(false)
                    ->selectablePlaceholder(false)
                    ->columnSpanFull(),
            ]),
        ]);
    }
}
