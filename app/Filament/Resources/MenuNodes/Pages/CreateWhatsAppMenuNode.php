<?php

namespace App\Filament\Resources\MenuNodes\Pages;

use App\Filament\Resources\MenuNodes\WhatsAppMenuNodeResource;
use App\Models\WhatsAppMenuNode;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Contracts\Support\Htmlable;
use Livewire\Attributes\Url;

class CreateWhatsAppMenuNode extends CreateRecord
{
    protected static string $resource = WhatsAppMenuNodeResource::class;

    #[Url(as: 'parent_id')]
    public ?int $parentId = null;

    public function getTitle(): string|Htmlable
    {
        if (WhatsAppMenuNode::root() === null) {
            return 'Criar menu principal';
        }

        $parent = WhatsAppMenuNode::find($this->parentId ?? WhatsAppMenuNode::root()?->id);

        return $parent ? "Nova opção em \"{$parent->label}\"" : 'Nova opção';
    }
}
