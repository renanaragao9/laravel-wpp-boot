<div style="max-height:46vh;overflow-y:auto;display:flex;flex-direction:column;gap:8px;padding:4px 2px;">
    @forelse ($messages as $message)
        @php($mine = $message->direction->value === 'outbound')
        <div style="max-width:84%;padding:8px 12px;border-radius:12px;font-size:14px;line-height:1.4;white-space:pre-wrap;word-wrap:break-word;align-self:{{ $mine ? 'flex-end' : 'flex-start' }};background:{{ $mine ? 'rgba(217,119,6,.18)' : 'rgba(127,127,127,.14)' }};">
            {{ $message->body }}
            <div style="font-size:11px;opacity:.65;margin-top:3px;">
                {{ $mine ? ($message->user?->name ?? 'Bot') : 'Contato' }} · {{ $message->created_at->format('d/m H:i') }}
            </div>
        </div>
    @empty
        <p style="opacity:.6;text-align:center;padding:16px 0;">Ainda não há mensagens nesta demanda.</p>
    @endforelse
</div>
