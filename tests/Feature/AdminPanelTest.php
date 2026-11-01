<?php

namespace Tests\Feature;

use App\Enums\DemandStatus;
use App\Enums\WebhookLogStatus;
use App\Enums\WhatsAppDriver;
use App\Filament\Resources\Demands\Pages\ViewWhatsAppDemand;
use App\Filament\Resources\MenuNodes\Pages\CreateWhatsAppMenuNode;
use App\Filament\Resources\WebhookLogs\Pages\ListWebhookLogs;
use App\Models\User;
use App\Models\WhatsAppDemand;
use App\Models\WhatsAppMenuNode;
use App\Models\WhatsAppWebhookLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    private function demand(array $attributes = []): WhatsAppDemand
    {
        return WhatsAppDemand::create($attributes + [
            'driver' => WhatsAppDriver::Meta,
            'phone' => '5585999990000',
            'push_name' => 'Maria',
            'sector' => 'Comercial',
            'title' => 'Comercial - Vendas',
        ]);
    }

    public function test_resource_pages_render(): void
    {
        $this->actingAs($master = User::factory()->create(['is_master' => true]));
        $node = WhatsAppMenuNode::create(['label' => 'Menu', 'type' => 'menu']);
        $demand = $this->demand();
        $log = WhatsAppWebhookLog::create(['driver' => WhatsAppDriver::Evolution, 'status' => WebhookLogStatus::Processed, 'raw_payload' => ['a' => 1]]);

        foreach ([
            '/admin', '/admin/menu', '/admin/menu/create', "/admin/menu/{$node->id}", "/admin/menu/{$node->id}/edit",
            '/admin/demandas', "/admin/demandas/{$demand->id}", "/admin/demandas/{$demand->id}/edit",
            '/admin/logs-webhook', "/admin/logs-webhook/{$log->id}",
            '/admin/users', '/admin/users/create', "/admin/users/{$master->id}/edit",
        ] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_only_master_can_open_users(): void
    {
        $this->actingAs(User::factory()->create())->get('/admin/users')->assertForbidden();
    }

    public function test_demands_cannot_be_created_and_logs_are_read_only(): void
    {
        $this->actingAs(User::factory()->create(['is_master' => true]));

        $this->get('/admin/demandas/create')->assertNotFound();
        $this->get('/admin/logs-webhook/create')->assertNotFound();
    }

    public function test_reply_action_sends_the_message_and_records_it(): void
    {
        config(['whatsapp.meta' => ['access_token' => 'tok', 'phone_number_id' => '123', 'graph_version' => 'v21.0']]);
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.x']]])]);
        $this->actingAs($user = User::factory()->create());
        $demand = $this->demand();

        Livewire::test(ViewWhatsAppDemand::class, ['record' => $demand->getRouteKey()])
            ->callAction('reply', ['message' => 'Olá, tudo bem?'])
            ->assertNotified('Mensagem enviada');

        $this->assertDatabaseHas('whatsapp_demand_messages', ['demand_id' => $demand->id, 'body' => 'Olá, tudo bem?', 'user_id' => $user->id]);
    }

    public function test_close_action_finishes_the_demand(): void
    {
        $this->actingAs(User::factory()->create());
        $demand = $this->demand();

        Livewire::test(ViewWhatsAppDemand::class, ['record' => $demand->getRouteKey()])
            ->callAction('close')
            ->assertNotified('Demanda concluída');

        $this->assertSame(DemandStatus::Done, $demand->fresh()->status);
    }

    public function test_menu_node_form_requires_sector_name_for_sector_type(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(CreateWhatsAppMenuNode::class)
            ->fillForm(['label' => 'Vendas', 'type' => 'sector'])
            ->call('create')
            ->assertHasFormErrors(['sector' => 'required']);
    }

    public function test_logs_table_filters_by_status(): void
    {
        $this->actingAs(User::factory()->create(['is_master' => true]));
        $ok = WhatsAppWebhookLog::create(['driver' => WhatsAppDriver::Meta, 'status' => WebhookLogStatus::Processed]);
        $bad = WhatsAppWebhookLog::create(['driver' => WhatsAppDriver::Meta, 'status' => WebhookLogStatus::Failed]);

        Livewire::test(ListWebhookLogs::class)
            ->filterTable('status', 'failed')
            ->assertCanSeeTableRecords([$bad])
            ->assertCanNotSeeTableRecords([$ok]);
    }
}
