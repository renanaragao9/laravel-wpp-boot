<?php

namespace Tests\Feature;

use App\Enums\ConversationStatus;
use App\Enums\DemandStatus;
use App\Enums\MessageDirection;
use App\Enums\WhatsAppDriver;
use App\Filament\Resources\Demands\Pages\ListDemands;
use App\Models\User;
use App\Models\WhatsAppConversation;
use App\Models\WhatsAppDemand;
use App\Models\WhatsAppDemandMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class DemandKanbanTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs($this->user = User::factory()->create(['name' => 'Agente']));
    }

    private function demand(array $attributes = []): WhatsAppDemand
    {
        static $n = 0;
        $n++;

        return WhatsAppDemand::create($attributes + [
            'driver' => WhatsAppDriver::Meta,
            'phone' => "5585999{$n}",
            'push_name' => "Contato {$n}",
            'sector' => 'Comercial',
            'title' => "Comercial - Demanda {$n}",
        ]);
    }

    private function columns(): array
    {
        return collect(Livewire::test(ListDemands::class)->instance()->getColumns())
            ->mapWithKeys(fn (array $column) => [$column['status']->value => $column['demands']->pluck('id')->all()])
            ->all();
    }

    public function test_page_renders_the_five_columns_with_cards_and_counts(): void
    {
        $this->demand(['status' => DemandStatus::Open, 'title' => 'Card aberto']);
        $this->demand(['status' => DemandStatus::InProgress, 'title' => 'Card em andamento']);
        $this->demand(['status' => DemandStatus::Done, 'title' => 'Card concluido']);

        Livewire::test(ListDemands::class)
            ->assertSee('Aberta')
            ->assertSee('Em andamento')
            ->assertSee('Aguardando')
            ->assertSee('Pausada')
            ->assertSee('Concluída')
            ->assertSee('Card aberto')
            ->assertSee('Card em andamento')
            ->assertSee('Card concluido')
            ->assertSee('Arraste uma demanda para cá');

        $this->get('/admin/demandas')->assertOk()->assertSee('x-sort', false);
    }

    public function test_card_shows_contact_driver_assignee_and_last_message(): void
    {
        $demand = $this->demand(['push_name' => 'Maria Souza', 'assigned_user_id' => $this->user->id]);
        WhatsAppDemandMessage::create(['demand_id' => $demand->id, 'direction' => MessageDirection::Inbound, 'body' => 'Primeira']);
        WhatsAppDemandMessage::create(['demand_id' => $demand->id, 'direction' => MessageDirection::Inbound, 'body' => 'Qual o prazo de entrega?']);

        Livewire::test(ListDemands::class)
            ->assertSee('Maria Souza')
            ->assertSee('Agente')
            ->assertSee('WhatsApp Cloud API (Meta)')
            ->assertSee('Qual o prazo de entrega?')
            ->assertDontSee('Primeira');
    }

    public function test_web_demands_show_chat_web_instead_of_the_internal_identifier(): void
    {
        $this->demand(['driver' => WhatsAppDriver::Web, 'phone' => 'web-segredo123']);

        Livewire::test(ListDemands::class)->assertSee('Chat web')->assertDontSee('web-segredo123');
    }

    public function test_filters_by_search_sector_driver_and_assignee(): void
    {
        $a = $this->demand(['push_name' => 'Ana Lima', 'sector' => 'Financeiro', 'driver' => WhatsAppDriver::Twilio, 'assigned_user_id' => $this->user->id]);
        $b = $this->demand(['push_name' => 'Bruno', 'sector' => 'Comercial']);

        $this->assertEqualsCanonicalizing([$a->id], $this->filtered(['search' => 'ana']));
        $this->assertEqualsCanonicalizing([$a->id], $this->filtered(['sector' => 'Financeiro']));
        $this->assertEqualsCanonicalizing([$a->id], $this->filtered(['driver' => 'twilio']));
        $this->assertEqualsCanonicalizing([$a->id], $this->filtered(['assigned' => 'me']));
        $this->assertEqualsCanonicalizing([$b->id], $this->filtered(['assigned' => 'none']));
        $this->assertEqualsCanonicalizing([$a->id, $b->id], $this->filtered([]));
    }

    private function filtered(array $filters): array
    {
        $test = Livewire::test(ListDemands::class);

        foreach ($filters as $key => $value) {
            $test->set($key, $value);
        }

        return collect($test->instance()->getColumns())->flatMap(fn (array $c) => $c['demands']->pluck('id'))->all();
    }

    public function test_moving_a_card_changes_the_status_and_places_it_at_the_requested_position(): void
    {
        $first = $this->demand(['status' => DemandStatus::InProgress, 'position' => 0]);
        $second = $this->demand(['status' => DemandStatus::InProgress, 'position' => 1]);
        $moving = $this->demand(['status' => DemandStatus::Open]);

        Livewire::test(ListDemands::class)->call('moveDemand', $moving->id, 'in_progress', 1);

        $this->assertSame(DemandStatus::InProgress, $moving->fresh()->status);
        $this->assertSame([$first->id, $moving->id, $second->id], $this->columns()['in_progress']);
        $this->assertSame([0, 1, 2], WhatsAppDemand::where('status', 'in_progress')->orderBy('position')->pluck('position')->all());
        $this->assertSame([], $this->columns()['open']);
    }

    public function test_moving_to_the_end_and_to_an_empty_column_works(): void
    {
        $a = $this->demand();
        $b = $this->demand();

        Livewire::test(ListDemands::class)
            ->call('moveDemand', $a->id, 'waiting', 0)
            ->call('moveDemand', $b->id, 'waiting', 99);

        $this->assertSame([$a->id, $b->id], $this->columns()['waiting']);
    }

    public function test_moving_to_done_asks_for_confirmation_and_only_then_closes(): void
    {
        $conversation = WhatsAppConversation::create(['driver' => 'meta', 'phone' => '1', 'status' => ConversationStatus::WithAgent]);
        $demand = $this->demand(['conversation_id' => $conversation->id]);

        $page = Livewire::test(ListDemands::class)
            ->call('moveDemand', $demand->id, 'done', 0)
            ->assertActionMounted('close');

        $this->assertSame(DemandStatus::Open, $demand->fresh()->status);

        $page->callMountedAction()->assertNotified('Demanda concluída');

        $this->assertSame(DemandStatus::Done, $demand->fresh()->status);
        $this->assertSame(ConversationStatus::Finished, $conversation->fresh()->status);
    }

    public function test_cancelling_the_confirmation_keeps_the_card_where_it_was(): void
    {
        $demand = $this->demand();

        Livewire::test(ListDemands::class)
            ->call('moveDemand', $demand->id, 'done', 0)
            ->assertActionMounted('close')
            ->unmountAction();

        $this->assertSame(DemandStatus::Open, $demand->fresh()->status);
        $this->assertContains($demand->id, $this->columns()['open']);
    }

    public function test_invalid_status_and_same_column_are_ignored(): void
    {
        $demand = $this->demand(['position' => 5]);

        Livewire::test(ListDemands::class)
            ->call('moveDemand', $demand->id, 'inexistente', 0)
            ->call('moveDemand', $demand->id, 'open', 0);

        $this->assertSame(DemandStatus::Open, $demand->fresh()->status);
        $this->assertSame(5, $demand->fresh()->position);
    }

    public function test_a_done_card_can_be_reopened_by_dragging_it_back(): void
    {
        $demand = $this->demand(['status' => DemandStatus::Done]);

        Livewire::test(ListDemands::class)->call('moveDemand', $demand->id, 'in_progress', 0);

        $this->assertSame(DemandStatus::InProgress, $demand->fresh()->status);
    }

    public function test_done_column_shows_only_the_most_recent_and_reports_the_total(): void
    {
        foreach (range(1, ListDemands::DONE_LIMIT + 3) as $i) {
            $this->demand(['status' => DemandStatus::Done, 'title' => "Pronta {$i}"]);
        }

        $done = collect(Livewire::test(ListDemands::class)->instance()->getColumns())->firstWhere('status', DemandStatus::Done);

        $this->assertSame(ListDemands::DONE_LIMIT + 3, $done['total']);
        $this->assertCount(ListDemands::DONE_LIMIT, $done['demands']);

        Livewire::test(ListDemands::class)->assertSee('Mostrando as 15 mais recentes de 18');
    }

    public function test_open_action_shows_the_thread_and_replies_through_the_demand_provider(): void
    {
        config(['whatsapp.meta' => ['access_token' => 'tok', 'phone_number_id' => '123', 'graph_version' => 'v21.0']]);
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.x']]])]);
        $demand = $this->demand();
        WhatsAppDemandMessage::create(['demand_id' => $demand->id, 'direction' => MessageDirection::Inbound, 'body' => 'Preciso de ajuda']);

        Livewire::test(ListDemands::class)
            ->mountAction('open', ['id' => $demand->id])
            ->assertActionMounted('open')
            ->setActionData(['message' => 'Já estou vendo!'])
            ->callMountedAction()
            ->assertNotified('Mensagem enviada');

        $this->assertDatabaseHas('whatsapp_demand_messages', ['demand_id' => $demand->id, 'body' => 'Já estou vendo!', 'user_id' => $this->user->id]);
        $this->assertSame(DemandStatus::InProgress, $demand->fresh()->status);
    }

    public function test_thread_view_lists_messages_with_authors_and_handles_empty_demands(): void
    {
        $demand = $this->demand();
        WhatsAppDemandMessage::create(['demand_id' => $demand->id, 'direction' => MessageDirection::Inbound, 'body' => 'Preciso de ajuda']);
        WhatsAppDemandMessage::create(['demand_id' => $demand->id, 'direction' => MessageDirection::Outbound, 'body' => 'Já estou vendo!', 'user_id' => $this->user->id]);

        $html = view('filament.demand-thread', ['messages' => $demand->messages()->with('user')->orderBy('id')->get()])->render();

        $this->assertStringContainsString('Preciso de ajuda', $html);
        $this->assertStringContainsString('Contato', $html);
        $this->assertStringContainsString('Já estou vendo!', $html);
        $this->assertStringContainsString('Agente', $html);
        $this->assertLessThan(strpos($html, 'Já estou vendo!'), strpos($html, 'Preciso de ajuda'));

        $empty = view('filament.demand-thread', ['messages' => collect()])->render();
        $this->assertStringContainsString('Ainda não há mensagens', $empty);
    }

    public function test_thread_escapes_html_from_the_contact(): void
    {
        $demand = $this->demand();
        WhatsAppDemandMessage::create(['demand_id' => $demand->id, 'direction' => MessageDirection::Inbound, 'body' => '<script>alert(1)</script>']);

        $html = view('filament.demand-thread', ['messages' => $demand->messages()->get()])->render();

        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    public function test_old_table_list_is_gone_and_other_pages_still_work(): void
    {
        $demand = $this->demand();
        $this->actingAs(User::factory()->create(['is_master' => true]));

        $this->get('/admin/demandas')->assertOk()->assertDontSee('fi-ta-table', false);
        $this->get("/admin/demandas/{$demand->id}")->assertOk();
        $this->get("/admin/demandas/{$demand->id}/edit")->assertOk();
    }
}
