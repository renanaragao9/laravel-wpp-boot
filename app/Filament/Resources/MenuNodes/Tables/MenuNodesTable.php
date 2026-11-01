<?php

namespace App\Filament\Resources\MenuNodes\Tables;

use App\Filament\Actions\DeleteMenuNodeAction;
use App\Filament\Actions\DeleteMenuNodeBulkAction;
use App\Filament\Filters\MenuNode\ActiveFilter;
use App\Filament\Filters\MenuNode\TypeFilter;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MenuNodesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('parent.label')
                    ->label('Dentro de')
                    ->placeholder('Menu inicial')
                    ->sortable(),

                TextColumn::make('option_number')
                    ->label('Opção')
                    ->sortable(),

                TextColumn::make('label')
                    ->label('Título')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('type')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state->label()),

                TextColumn::make('sector')
                    ->label('Setor')
                    ->placeholder('-')
                    ->searchable(),

                IconColumn::make('active')
                    ->label('Ativo')
                    ->boolean(),

                TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TypeFilter::make(),
                ActiveFilter::make(),
            ])
            ->defaultSort('parent_id')
            ->recordActions([
                ViewAction::make()
                    ->icon(Heroicon::OutlinedEye),

                EditAction::make()
                    ->icon(Heroicon::OutlinedPencil),

                DeleteMenuNodeAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteMenuNodeBulkAction::make(),
                ]),
            ]);
    }
}
