<?php

namespace App\Filament\Widgets;

use App\Enums\DemandStatus;
use App\Filament\Resources\Demands\WhatsAppDemandResource;
use App\Models\WhatsAppDemand;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class AwaitingDemandsTable extends TableWidget
{
    protected static ?int $sort = 6;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Aguardando atendimento')
            ->description('Demandas abertas sem atendente, as mais antigas primeiro.')
            ->query(fn () => WhatsAppDemand::query()
                ->where('status', DemandStatus::Open->value)
                ->whereNull('assigned_user_id')
                ->oldest())
            ->columns([
                TextColumn::make('created_at')
                    ->label('Aberta há')
                    ->since(),

                TextColumn::make('push_name')
                    ->label('Contato')
                    ->placeholder('Sem nome'),

                TextColumn::make('sector')
                    ->label('Setor')
                    ->badge(),

                TextColumn::make('driver')
                    ->label('Canal')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state->label()),

                TextColumn::make('title')
                    ->label('Título')
                    ->limit(40),
            ])
            ->recordActions([
                Action::make('open')
                    ->label('Atender')
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->url(fn (WhatsAppDemand $record) => WhatsAppDemandResource::getUrl('view', ['record' => $record])),
            ])
            ->paginated([5])
            ->emptyStateHeading('Nenhuma demanda esperando')
            ->emptyStateDescription('Todas as demandas abertas já têm atendente.')
            ->emptyStateIcon(Heroicon::OutlinedCheckCircle);
    }
}
