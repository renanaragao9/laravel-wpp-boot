<?php

namespace Tests\Feature\Webhook;

use App\Enums\DemandStatus;
use App\Enums\MenuNodeType;
use App\Models\WhatsAppDemand;
use App\Models\WhatsAppMenuNode;
use App\Services\Menu\BuildMenuTreeService;
use Database\Seeders\MenuNodeSeeder;
use Illuminate\Support\Facades\Http;

class FullMenuFlowTest extends WebhookTestCase
{
    protected function menuSeeder(): string
    {
        return MenuNodeSeeder::class;
    }

    private function say(string $text, string $id): void
    {
        $this->postMeta($this->metaPayload('5585999990000', $text, $id))->assertOk();
    }

    private function lastReply(): string
    {
        return (string) Http::recorded()->last()[0]['text']['body'];
    }

    public function test_menu_is_well_formed(): void
    {
        $nodes = WhatsAppMenuNode::all();

        $this->assertSame(1, $nodes->whereNull('parent_id')->count());

        foreach ($nodes as $node) {
            $children = $nodes->where('parent_id', $node->id);

            if ($node->type === MenuNodeType::Menu) {
                $this->assertGreaterThan(0, $children->count(), "Menu sem opções: {$node->label}");
                $this->assertSame($children->count(), $children->pluck('option_number')->unique()->count(), "Números repetidos em {$node->label}");
            } else {
                $this->assertSame(0, $children->count(), "Setor com filhos: {$node->label}");
                $this->assertNotEmpty($node->sector);
            }

            if ($node->parent_id !== null) {
                $this->assertNotEmpty($node->option_number, "Sem número: {$node->label}");
            }

            $this->assertTrue($node->active);
        }

        $this->assertLessThanOrEqual(3, app(BuildMenuTreeService::class)->run()->max('depth'));
    }

    public function test_seeder_does_not_touch_an_existing_menu(): void
    {
        $count = WhatsAppMenuNode::count();
        WhatsAppMenuNode::where('label', 'Financeiro')->update(['label' => 'Alterado']);

        $this->seed(MenuNodeSeeder::class);

        $this->assertSame($count, WhatsAppMenuNode::count());
        $this->assertDatabaseHas('whatsapp_menu_nodes', ['label' => 'Alterado']);
    }

    public function test_greeting_shows_all_the_areas(): void
    {
        $this->say('oi', 'm1');

        $reply = $this->lastReply();
        $this->assertStringContainsString('Bem-vindo ao nosso atendimento', $reply);

        foreach (['1 - Comercial', '2 - Suporte', '3 - Financeiro', '4 - Recursos Humanos', '5 - Falar com um atendente'] as $line) {
            $this->assertStringContainsString($line, $reply);
        }
    }

    public function test_two_level_path_opens_a_sector_demand(): void
    {
        $this->say('oi', 'm1');
        $this->say('3', 'm2');
        $this->assertStringContainsString('1 - Segunda via de boleto', $this->lastReply());

        $this->say('1', 'm3');

        $demand = WhatsAppDemand::firstOrFail();
        $this->assertSame('Financeiro', $demand->sector);
        $this->assertSame('Financeiro - Segunda via de boleto', $demand->title);
        $this->assertSame(DemandStatus::Open, $demand->status);
        $this->assertStringContainsString('*Financeiro*', $this->lastReply());
    }

    public function test_three_level_path_support_problem_system(): void
    {
        $this->say('oi', 'm1');
        $this->say('2', 'm2');
        $this->say('1', 'm3');
        $this->assertStringContainsString('Onde está acontecendo o problema?', $this->lastReply());
        $this->assertStringContainsString('1 - No sistema ou aplicativo', $this->lastReply());

        $this->say('1', 'm4');

        $this->assertSame('Suporte técnico - No sistema ou aplicativo', WhatsAppDemand::firstOrFail()->title);
    }

    public function test_direct_option_goes_straight_to_an_attendant(): void
    {
        $this->say('oi', 'm1');
        $this->say('5', 'm2');

        $this->assertSame('Atendimento geral', WhatsAppDemand::firstOrFail()->sector);
    }

    public function test_every_sector_leaf_is_reachable_by_the_numbers_a_contact_would_type(): void
    {
        $leaves = WhatsAppMenuNode::where('type', MenuNodeType::Sector->value)->get();

        foreach ($leaves as $i => $leaf) {
            $path = [];

            for ($node = $leaf; $node->parent_id !== null; $node = $node->parent) {
                array_unshift($path, $node->option_number);
            }

            $phone = '55859'.str_pad((string) $i, 8, '0', STR_PAD_LEFT);
            $this->postMeta($this->metaPayload($phone, 'oi', "hi{$i}"))->assertOk();

            foreach ($path as $step => $number) {
                $this->postMeta($this->metaPayload($phone, $number, "s{$i}-{$step}"))->assertOk();
            }

            $this->assertDatabaseHas('whatsapp_demands', ['phone' => $phone, 'menu_node_id' => $leaf->id]);
        }
    }
}
