<?php

namespace Tests\Support;

use App\Enums\MenuNodeType;
use App\Models\WhatsAppMenuNode;
use Illuminate\Database\Seeder;

class SimpleMenuSeeder extends Seeder
{
    public function run(): void
    {
        if (WhatsAppMenuNode::query()->exists()) {
            return;
        }

        $root = WhatsAppMenuNode::create([
            'label' => 'Menu inicial',
            'type' => MenuNodeType::Menu,
            'message' => 'Olá! Sou o assistente virtual. Como posso ajudar?',
        ]);

        WhatsAppMenuNode::create([
            'parent_id' => $root->id,
            'label' => 'Comercial',
            'option_number' => '1',
            'type' => MenuNodeType::Sector,
            'sector' => 'Comercial',
        ]);

        $support = WhatsAppMenuNode::create([
            'parent_id' => $root->id,
            'label' => 'Suporte',
            'option_number' => '2',
            'type' => MenuNodeType::Menu,
            'message' => 'Suporte: qual é o assunto?',
        ]);

        WhatsAppMenuNode::create([
            'parent_id' => $support->id,
            'label' => 'Financeiro',
            'option_number' => '1',
            'type' => MenuNodeType::Sector,
            'sector' => 'Financeiro',
        ]);

        WhatsAppMenuNode::create([
            'parent_id' => $support->id,
            'label' => 'Problema técnico',
            'option_number' => '2',
            'type' => MenuNodeType::Sector,
            'sector' => 'Suporte técnico',
        ]);
    }
}
