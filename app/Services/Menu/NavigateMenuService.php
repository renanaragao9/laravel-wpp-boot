<?php

declare(strict_types=1);

namespace App\Services\Menu;

use App\Enums\ConversationStatus;
use App\Enums\DemandStatus;
use App\Enums\MenuNodeType;
use App\Enums\MessageDirection;
use App\Models\WhatsAppConversation;
use App\Models\WhatsAppDemand;
use App\Models\WhatsAppDemandMessage;
use App\Models\WhatsAppMenuNode;
use App\Services\WhatsApp\SendMessageService;
use App\Support\WhatsApp\IncomingMessage;
use Illuminate\Support\Carbon;

class NavigateMenuService
{
    public function __construct(private readonly SendMessageService $sender) {}

    public function run(IncomingMessage $incoming): array
    {
        $option = trim((string) ($incoming->option ?? ''));

        $demand = $this->activeDemand($incoming->phone);

        if ($demand !== null) {
            return $this->holdWithAgent($demand, $incoming);
        }

        $conversation = $this->activeConversation($incoming->phone) ?? new WhatsAppConversation([
            'driver' => $incoming->driver,
            'phone' => $incoming->phone,
        ]);

        if (! $conversation->exists || $this->isRestart($option) || $this->isExpired($conversation)) {
            return $this->startAtRoot($conversation, $incoming);
        }

        $current = $conversation->current_node_id ? WhatsAppMenuNode::find($conversation->current_node_id) : null;

        if ($current === null) {
            return $this->startAtRoot($conversation, $incoming);
        }

        $chosen = $this->findChild($current, $option);

        if ($chosen === null) {
            $this->reply($incoming, "Opção inválida.\n\n".$this->render($current));

            return ['action' => 'invalid_option', 'node_id' => $current->id, 'demand_id' => null];
        }

        return $chosen->type === MenuNodeType::Sector
            ? $this->openDemand($conversation, $chosen, $incoming)
            : $this->enterMenu($conversation, $chosen, $incoming);
    }

    private function activeConversation(string $phone): ?WhatsAppConversation
    {
        return WhatsAppConversation::query()
            ->where('phone', $phone)
            ->whereIn('status', array_map(fn (ConversationStatus $s) => $s->value, ConversationStatus::active()))
            ->latest('id')
            ->first();
    }

    private function activeDemand(string $phone): ?WhatsAppDemand
    {
        return WhatsAppDemand::query()
            ->where('phone', $phone)
            ->whereIn('status', array_map(fn (DemandStatus $s) => $s->value, DemandStatus::active()))
            ->latest('id')
            ->first();
    }

    private function isRestart(string $option): bool
    {
        return in_array(mb_strtolower($option), ['0', 'menu', 'início', 'inicio'], true);
    }

    private function isExpired(WhatsAppConversation $conversation): bool
    {
        $minutes = (int) config('whatsapp.session_timeout_minutes');

        return $conversation->status === ConversationStatus::Navigating
            && $minutes > 0
            && $conversation->last_interaction_at !== null
            && $conversation->last_interaction_at->lt(Carbon::now()->subMinutes($minutes));
    }

    private function holdWithAgent(WhatsAppDemand $demand, IncomingMessage $incoming): array
    {
        $conversation = $demand->conversation_id ? WhatsAppConversation::find($demand->conversation_id) : null;

        if ($conversation !== null) {
            $conversation->update([
                'status' => ConversationStatus::WithAgent,
                'push_name' => $incoming->name ?? $conversation->push_name,
                'last_interaction_at' => Carbon::now(),
            ]);
        }

        WhatsAppDemandMessage::create([
            'demand_id' => $demand->id,
            'direction' => MessageDirection::Inbound,
            'message_id' => $incoming->messageId,
            'body' => $incoming->option,
        ]);

        return ['action' => 'handed_to_agent', 'node_id' => $demand->menu_node_id, 'demand_id' => $demand->id];
    }

    private function startAtRoot(WhatsAppConversation $conversation, IncomingMessage $incoming): array
    {
        $root = WhatsAppMenuNode::query()->whereNull('parent_id')->where('active', true)->first();

        if ($root === null) {
            return ['action' => 'no_menu_configured', 'node_id' => null, 'demand_id' => null];
        }

        $this->moveTo($conversation, $root, $incoming);
        $this->reply($incoming, $this->render($root));

        return ['action' => 'entered_root', 'node_id' => $root->id, 'demand_id' => null];
    }

    private function enterMenu(WhatsAppConversation $conversation, WhatsAppMenuNode $node, IncomingMessage $incoming): array
    {
        $this->moveTo($conversation, $node, $incoming);
        $this->reply($incoming, $this->render($node));

        return ['action' => 'entered_menu', 'node_id' => $node->id, 'demand_id' => null];
    }

    private function openDemand(WhatsAppConversation $conversation, WhatsAppMenuNode $node, IncomingMessage $incoming): array
    {
        $conversation->fill([
            'current_node_id' => $node->id,
            'status' => ConversationStatus::WithAgent,
            'push_name' => $incoming->name ?? $conversation->push_name,
            'last_interaction_at' => Carbon::now(),
        ])->save();

        $demand = WhatsAppDemand::create([
            'driver' => $incoming->driver,
            'conversation_id' => $conversation->id,
            'menu_node_id' => $node->id,
            'phone' => $incoming->phone,
            'push_name' => $incoming->name,
            'sector' => $node->sector ?? $node->label,
            'title' => $node->sector ? "{$node->sector} - {$node->label}" : $node->label,
            'status' => DemandStatus::Open,
            'position' => (int) WhatsAppDemand::query()->where('status', DemandStatus::Open->value)->max('position') + 1,
        ]);

        $sector = $node->sector ?? $node->label;
        $this->reply($incoming, "Pronto! Sua solicitação foi encaminhada para o setor *{$sector}*. Em breve alguém da equipe entrará em contato por aqui.");

        return ['action' => 'demand_opened', 'node_id' => $node->id, 'demand_id' => $demand->id];
    }

    private function moveTo(WhatsAppConversation $conversation, WhatsAppMenuNode $node, IncomingMessage $incoming): void
    {
        $conversation->fill([
            'driver' => $conversation->driver ?? $incoming->driver,
            'current_node_id' => $node->id,
            'status' => ConversationStatus::Navigating,
            'push_name' => $incoming->name ?? $conversation->push_name,
            'last_interaction_at' => Carbon::now(),
        ])->save();
    }

    private function findChild(WhatsAppMenuNode $node, string $option): ?WhatsAppMenuNode
    {
        if ($option === '') {
            return null;
        }

        return $node->children()->where('active', true)->where('option_number', $option)->first();
    }

    private function render(WhatsAppMenuNode $node): string
    {
        $lines = [];

        if (! empty($node->message)) {
            $lines[] = $node->message;
            $lines[] = '';
        }

        $children = $node->children()->where('active', true)->get()
            ->sortBy(fn (WhatsAppMenuNode $child) => (int) $child->option_number);

        foreach ($children as $child) {
            $lines[] = "{$child->option_number} - {$child->label}";
        }

        $lines[] = '';
        $lines[] = 'Responda com o número da opção. Digite 0 para voltar ao menu inicial.';

        return implode("\n", $lines);
    }

    private function reply(IncomingMessage $incoming, string $text): void
    {
        $this->sender->run($incoming->driver, $incoming->phone, $text);
    }
}
