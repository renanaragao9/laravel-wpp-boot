<x-filament::section heading="Árvore do menu" description="É assim que o contato navega. Use + para adicionar uma opção dentro de um menu." :compact="true">
    @if ($rows->isEmpty())
        <p style="text-align:center;padding:24px 0;opacity:.6;">Nenhuma opção configurada. Clique em "Criar menu principal" para começar.</p>
    @else
        <ul style="list-style:none;margin:0;padding:0;">
            @foreach ($rows as $row)
                @php($node = $row['node'])
                <li style="display:flex;align-items:center;justify-content:space-between;gap:8px;padding:6px 8px 6px {{ $row['depth'] * 24 + 8 }}px;">
                    <div style="display:flex;align-items:center;gap:8px;min-width:0;">
                        <x-filament::icon :icon="$node->type->value === 'sector' ? 'heroicon-o-flag' : 'heroicon-o-folder'" style="width:18px;height:18px;flex-shrink:0;" />
                        <span style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                            @if ($node->option_number)<span style="font-family:monospace;opacity:.6;">{{ $node->option_number }} - </span>@endif{{ $node->label }}
                        </span>
                        @if ($node->type->value === 'sector')
                            <x-filament::badge color="warning" size="sm">{{ $node->sector }}</x-filament::badge>
                        @endif
                        @if (! $node->active)
                            <x-filament::badge color="gray" size="sm">inativo</x-filament::badge>
                        @endif
                    </div>
                    <div style="display:flex;align-items:center;gap:2px;flex-shrink:0;">
                        @if ($node->type->value === 'menu')
                            <x-filament::icon-button icon="heroicon-o-plus" tag="a" :href="$createUrl($node)" tooltip="Adicionar opção aqui" size="sm" />
                        @endif
                        <x-filament::icon-button icon="heroicon-o-pencil" tag="a" :href="$editUrl($node)" tooltip="Editar" size="sm" />
                    </div>
                </li>
            @endforeach
        </ul>
    @endif
</x-filament::section>
