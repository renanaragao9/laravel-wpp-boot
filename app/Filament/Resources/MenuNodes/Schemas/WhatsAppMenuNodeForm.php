<?php

namespace App\Filament\Resources\MenuNodes\Schemas;

use App\Enums\MenuNodeType;
use App\Models\WhatsAppMenuNode;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Unique;

class WhatsAppMenuNodeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Section::make('Opção do menu')->columnSpanFull()->columns(2)->schema([
                Select::make('parent_id')
                    ->label('Dentro de')
                    ->helperText(fn () => WhatsAppMenuNode::root() === null
                        ? 'Deixe vazio para criar o menu principal (raiz). Ele só pode existir um.'
                        : 'Já existe um menu principal: toda nova opção fica dentro de um menu.')
                    ->options(fn (?WhatsAppMenuNode $record) => WhatsAppMenuNode::query()
                        ->where('type', MenuNodeType::Menu->value)
                        ->when($record, fn ($q) => $q->whereKeyNot([$record->id, ...$record->descendantIds()]))
                        ->pluck('label', 'id'))
                    ->default(fn ($livewire) => $livewire->parentId ?? WhatsAppMenuNode::root()?->id)
                    ->required(fn (?WhatsAppMenuNode $record) => WhatsAppMenuNode::query()
                        ->whereNull('parent_id')
                        ->when($record, fn ($q) => $q->whereKeyNot($record->id))
                        ->exists())
                    ->placeholder('Menu principal (raiz)')
                    ->searchable()
                    ->live()
                    ->afterStateUpdated(function (Set $set, Get $get, string $operation, $state, $old): void {
                        if ($operation !== 'create') {
                            return;
                        }

                        $current = (string) $get('option_number');

                        if ($current === '' || $current === (string) WhatsAppMenuNode::nextOptionNumber($old ? (int) $old : null)) {
                            $set('option_number', WhatsAppMenuNode::nextOptionNumber($state ? (int) $state : null));
                        }
                    }),

                TextInput::make('label')
                    ->label('Título')
                    ->required()
                    ->maxLength(255),

                TextInput::make('option_number')
                    ->label('Número da opção')
                    ->helperText('O que o contato digita para escolher esta opção. Já vem sugerido o próximo número livre do menu.')
                    ->default(fn ($livewire) => WhatsAppMenuNode::nextOptionNumber($livewire->parentId ?? WhatsAppMenuNode::root()?->id))
                    ->required(fn (Get $get) => filled($get('parent_id')))
                    ->maxLength(10)
                    ->unique(
                        table: 'whatsapp_menu_nodes',
                        column: 'option_number',
                        ignoreRecord: true,
                        modifyRuleUsing: fn (Unique $rule, Get $get) => $rule
                            ->where('parent_id', $get('parent_id') ?: null)
                            ->whereNull('deleted_at'),
                    )
                    ->validationMessages(['unique' => 'Já existe uma opção com este número neste menu. Escolha outro número.']),

                Select::make('type')
                    ->label('Tipo')
                    ->options(collect(MenuNodeType::cases())->mapWithKeys(fn (MenuNodeType $t) => [$t->value => $t->label()]))
                    ->default(MenuNodeType::Menu->value)
                    ->required()
                    ->live(),

                TextInput::make('sector')
                    ->label('Setor')
                    ->helperText('Nome do setor que recebe a demanda.')
                    ->required(fn (Get $get) => $get('type') === MenuNodeType::Sector->value)
                    ->visible(fn (Get $get) => $get('type') === MenuNodeType::Sector->value),

                Textarea::make('message')
                    ->label('Mensagem')
                    ->helperText('Texto mostrado antes das opções deste menu.')
                    ->rows(4)
                    ->columnSpanFull(),

                Toggle::make('active')
                    ->label('Ativo')
                    ->default(true),
            ]),
        ]);
    }
}
