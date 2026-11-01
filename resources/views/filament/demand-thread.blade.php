<div wire:key="thread-{{ $messages->count() }}" x-data x-init="$nextTick(() => $el.scrollTop = $el.scrollHeight)" style="max-height:46vh;overflow-y:auto;display:flex;flex-direction:column;gap:8px;padding:4px 6px 4px 2px;">
    @forelse ($messages as $message)
        @php($mine = $message->direction->value === 'outbound')
        <div style="width:fit-content;max-width:84%;min-width:96px;padding:8px 12px 6px;border-radius:14px;font-size:14px;line-height:1.45;word-wrap:break-word;overflow-wrap:anywhere;align-self:{{ $mine ? 'flex-end' : 'flex-start' }};background:{{ $mine ? 'rgba(217,119,6,.2)' : 'rgba(127,127,127,.16)' }};border-{{ $mine ? 'bottom-right' : 'bottom-left' }}-radius:4px;">
            <div style="white-space:pre-wrap;">{{ $message->body }}</div>
            <div style="font-size:11px;opacity:.65;margin-top:4px;text-align:{{ $mine ? 'right' : 'left' }};">{{ $mine ? ($message->user?->name ?? 'Bot') : 'Contato' }} · {{ $message->created_at->format('d/m H:i') }}</div>
        </div>
    @empty
        <p style="opacity:.6;text-align:center;padding:16px 0;">Ainda não há mensagens nesta demanda.</p>
    @endforelse
</div>
