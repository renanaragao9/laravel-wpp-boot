<?php

namespace Database\Seeders;

use App\Enums\MenuNodeType;
use App\Models\WhatsAppMenuNode;
use Illuminate\Database\Seeder;

class MenuNodeSeeder extends Seeder
{
    public function run(): void
    {
        if (WhatsAppMenuNode::query()->exists()) {
            return;
        }

        $this->create(null, null, $this->flow());
    }

    private function flow(): array
    {
        return [
            'label' => 'Menu inicial',
            'message' => "Olá! 👋 Bem-vindo ao nosso atendimento.\nComo podemos ajudar? Escolha uma das opções:",
            'children' => [
                [
                    'label' => 'Comercial',
                    'message' => 'Comercial: sobre o que você quer falar?',
                    'children' => [
                        $this->sector('Quero contratar ou comprar', 'Comercial'),
                        $this->sector('Orçamentos e propostas', 'Comercial'),
                        $this->sector('Falar com um consultor', 'Comercial'),
                    ],
                ],
                [
                    'label' => 'Suporte',
                    'message' => 'Suporte: qual é o assunto?',
                    'children' => [
                        [
                            'label' => 'Estou com um problema',
                            'message' => 'Onde está acontecendo o problema?',
                            'children' => [
                                $this->sector('No sistema ou aplicativo', 'Suporte técnico'),
                                $this->sector('Acesso e senha', 'Suporte técnico'),
                                $this->sector('Outro problema', 'Suporte técnico'),
                            ],
                        ],
                        $this->sector('Dúvidas de uso', 'Suporte técnico'),
                        $this->sector('Acompanhar um chamado', 'Suporte técnico'),
                    ],
                ],
                [
                    'label' => 'Financeiro',
                    'message' => 'Financeiro: como podemos ajudar?',
                    'children' => [
                        $this->sector('Segunda via de boleto', 'Financeiro'),
                        $this->sector('Pagamentos e comprovantes', 'Financeiro'),
                        $this->sector('Cancelamento e reembolso', 'Financeiro'),
                    ],
                ],
                [
                    'label' => 'Recursos Humanos',
                    'message' => 'Recursos Humanos: qual é o seu assunto?',
                    'children' => [
                        $this->sector('Quero trabalhar conosco', 'Recursos Humanos'),
                        $this->sector('Sou colaborador', 'Recursos Humanos'),
                    ],
                ],
                $this->sector('Falar com um atendente', 'Atendimento geral'),
            ],
        ];
    }

    private function sector(string $label, string $sector): array
    {
        return ['label' => $label, 'sector' => $sector];
    }

    private function create(?WhatsAppMenuNode $parent, ?int $number, array $definition): void
    {
        $node = WhatsAppMenuNode::create([
            'parent_id' => $parent?->id,
            'option_number' => $number === null ? null : (string) $number,
            'label' => $definition['label'],
            'type' => isset($definition['sector']) ? MenuNodeType::Sector : MenuNodeType::Menu,
            'sector' => $definition['sector'] ?? null,
            'message' => $definition['message'] ?? null,
        ]);

        foreach (array_values($definition['children'] ?? []) as $index => $child) {
            $this->create($node, $index + 1, $child);
        }
    }
}
