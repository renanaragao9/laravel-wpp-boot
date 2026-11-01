@php
    $colors = [
        'open' => '#3b82f6',
        'in_progress' => '#f59e0b',
        'waiting' => '#8b5cf6',
        'paused' => '#9ca3af',
        'done' => '#10b981',
    ];
    $initials = fn (?string $name) => mb_strtoupper(collect(preg_split('/\s+/', trim((string) $name)))->filter()->take(2)->map(fn ($part) => mb_substr($part, 0, 1))->implode('')) ?: '?';
@endphp

<x-filament-panels::page>
    <style>
        .kb-filters { display: flex; flex-wrap: wrap; gap: 10px; align-items: end; }
        .kb-filters > * { min-width: 160px; }
        .kb-filters .kb-search { flex: 1; min-width: 220px; }
        .kb-board { display: grid; grid-template-columns: repeat(5, minmax(240px, 1fr)); gap: 14px; overflow-x: auto; padding-bottom: 8px; align-items: start; }
        .kb-col { background: rgba(127,127,127,.09); border-radius: 14px; padding: 10px; border-top: 4px solid var(--kb); display: flex; flex-direction: column; min-height: 200px; }
        .kb-head { display: flex; justify-content: space-between; align-items: center; padding: 2px 4px 10px; font-weight: 600; font-size: 14px; }
        .kb-head span.kb-dot { display: inline-block; width: 9px; height: 9px; border-radius: 50%; background: var(--kb); margin-right: 8px; }
        .kb-count { font-size: 12px; padding: 1px 9px; border-radius: 999px; background: color-mix(in srgb, var(--kb) 22%, transparent); }
        .kb-list { display: flex; flex-direction: column; gap: 10px; flex: 1; min-height: 90px; }
        .kb-card { background: rgb(255 255 255); border: 1px solid rgba(127,127,127,.25); border-left: 4px solid var(--kb); border-radius: 12px; padding: 10px 12px; cursor: grab; box-shadow: 0 1px 2px rgba(0,0,0,.06); }
        .dark .kb-card { background: rgb(39 39 42); }
        .kb-card:active { cursor: grabbing; }
        .kb-title { font-size: 14px; font-weight: 600; display: flex; justify-content: space-between; gap: 8px; align-items: start; }
        .kb-badge { font-size: 10px; text-transform: uppercase; letter-spacing: .03em; padding: 2px 8px; border-radius: 999px; background: rgba(217,119,6,.15); color: #b45309; white-space: nowrap; }
        .dark .kb-badge { color: #fbbf24; }
        .kb-person { display: flex; gap: 8px; align-items: center; margin-top: 8px; }
        .kb-avatar { width: 30px; height: 30px; border-radius: 50%; background: var(--kb); color: #fff; font-size: 11px; font-weight: 700; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .kb-name { font-size: 12px; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .kb-meta { font-size: 11px; opacity: .65; }
        .kb-last { font-size: 12px; opacity: .8; margin-top: 8px; font-style: italic; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; }
        .kb-foot { display: flex; justify-content: space-between; align-items: center; margin-top: 10px; gap: 8px; }
        .kb-empty { font-size: 12px; opacity: .55; text-align: center; padding: 14px 0; }
        .kb-note { font-size: 11px; opacity: .55; text-align: center; padding-top: 8px; }
        .sortable-ghost, [x-sort-ghost] { opacity: .4; }
    </style>

    <div class="kb-filters">
        <div class="kb-search">
            <x-filament::input.wrapper prefix-icon="heroicon-o-magnifying-glass">
                <x-filament::input type="search" wire:model.live.debounce.400ms="search" placeholder="Buscar por contato, telefone ou título" />
            </x-filament::input.wrapper>
        </div>
        <x-filament::input.wrapper>
            <x-filament::input.select wire:model.live="sector">
                <option value="">Todos os setores</option>
                @foreach ($this->sectorOptions() as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </x-filament::input.select>
        </x-filament::input.wrapper>
        <x-filament::input.wrapper>
            <x-filament::input.select wire:model.live="driver">
                <option value="">Todos os provedores</option>
                @foreach ($this->driverOptions() as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </x-filament::input.select>
        </x-filament::input.wrapper>
        <x-filament::input.wrapper>
            <x-filament::input.select wire:model.live="assigned">
                <option value="">Todos os atendentes</option>
                <option value="me">Minhas demandas</option>
                <option value="none">Sem atendente</option>
            </x-filament::input.select>
        </x-filament::input.wrapper>
    </div>

    <div class="kb-board" wire:poll.20s.visible>
        @foreach ($this->getColumns() as $column)
            @php($status = $column['status'])
            <section class="kb-col" style="--kb: {{ $colors[$status->value] }}" wire:key="col-{{ $status->value }}">
                <div class="kb-head">
                    <div><span class="kb-dot"></span>{{ $status->label() }}</div>
                    <span class="kb-count">{{ $column['total'] }}</span>
                </div>

                <div class="kb-list" x-sort="$wire.moveDemand($item, '{{ $status->value }}', $position)" x-sort:group="demands">
                    @foreach ($column['demands'] as $demand)
                        <article class="kb-card" x-sort:item="{{ $demand->id }}" wire:key="demand-{{ $demand->id }}">
                            <div class="kb-title">
                                <span>{{ $demand->title }}</span>
                                <span class="kb-badge">{{ $demand->sector }}</span>
                            </div>

                            <div class="kb-person">
                                <div class="kb-avatar">{{ $initials($demand->push_name) }}</div>
                                <div style="min-width:0">
                                    <div class="kb-name">{{ $demand->push_name ?: 'Sem nome' }}</div>
                                    <div class="kb-meta">{{ $demand->driver === \App\Enums\WhatsAppDriver::Web ? 'Chat web' : $demand->phone }} · {{ $demand->driver->label() }}</div>
                                </div>
                            </div>

                            @if ($demand->last_message)
                                <div class="kb-last">{{ $demand->last_message }}</div>
                            @endif

                            <div class="kb-foot">
                                <span class="kb-meta">{{ $demand->assignedUser?->name ?? 'Sem atendente' }} · {{ $demand->created_at->diffForHumans(short: true) }}</span>
                                <x-filament::button size="xs" color="gray" icon="heroicon-o-chat-bubble-left-right" wire:click="mountAction('open', { id: {{ $demand->id }} })">Abrir</x-filament::button>
                            </div>
                        </article>
                    @endforeach

                    @if ($column['demands']->isEmpty())
                        <div class="kb-empty">Arraste uma demanda para cá</div>
                    @endif
                </div>

                @if ($status->value === 'done' && $column['total'] > $column['demands']->count())
                    <div class="kb-note">Mostrando as {{ $column['demands']->count() }} mais recentes de {{ $column['total'] }}</div>
                @endif
            </section>
        @endforeach
    </div>
</x-filament-panels::page>
