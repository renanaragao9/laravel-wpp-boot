<?php

namespace Tests\Feature;

use App\Enums\ConversationStatus;
use App\Enums\DemandStatus;
use App\Enums\MenuNodeType;
use App\Enums\MessageDirection;
use App\Enums\WhatsAppDriver;
use App\Filament\Resources\Demands\Pages\ViewWhatsAppDemand;
use App\Models\ChatSession;
use App\Models\User;
use App\Models\WhatsAppConversation;
use App\Models\WhatsAppDemand;
use App\Models\WhatsAppMenuNode;
use App\Services\Demand\CloseDemandService;
use App\Services\Demand\ReplyDemandService;
use Database\Seeders\MenuNodeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Carbon;
use Illuminate\Testing\TestResponse;
use Livewire\Livewire;
use Tests\Support\SimpleMenuSeeder;
use Tests\TestCase;

class WebChatTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SimpleMenuSeeder::class);
    }

    private function start(array $overrides = []): TestResponse
    {
        return $this->postJson('/api/chat', $overrides + ['name' => 'Maria', 'email' => 'maria@exemplo.com', 'phone' => '85999990000']);
    }

    private function say(string $token, string $text): TestResponse
    {
        return $this->postJson("/api/chat/{$token}/messages", ['message' => $text])->assertCreated();
    }

    private function chat(string $token): ChatSession
    {
        return ChatSession::query()->where('token', $token)->firstOrFail();
    }

    /**
     * @return list<string>
     */
    private function botTexts(string $token): array
    {
        return $this->chat($token)->messages()->where('direction', MessageDirection::Outbound->value)->orderBy('id')->pluck('body')->all();
    }

    private function lastBot(string $token): string
    {
        return (string) collect($this->botTexts($token))->last();
    }

    public function test_starting_the_chat_shows_the_root_menu_and_does_not_open_a_demand(): void
    {
        $response = $this->start()->assertCreated();
        $token = $response->json('token');

        $this->assertSame(48, strlen($token));
        $response->assertJsonCount(1, 'data')->assertJsonPath('data.0.direction', 'outbound');
        $this->assertStringContainsString('1 - Comercial', $response->json('data.0.body'));
        $this->assertStringContainsString('2 - Suporte', $response->json('data.0.body'));
        $this->assertDatabaseCount('whatsapp_demands', 0);

        $conversation = WhatsAppConversation::firstOrFail();
        $this->assertSame(WhatsAppDriver::Web, $conversation->driver);
        $this->assertSame($this->chat($token)->identifier, $conversation->phone);
        $this->assertSame(ConversationStatus::Navigating, $conversation->status);
        $this->assertSame('Maria', $conversation->push_name);
    }

    public function test_navigates_through_submenus_and_opens_a_web_demand_in_a_sector(): void
    {
        $token = $this->start()->json('token');

        $this->say($token, '2');
        $this->assertStringContainsString('1 - Financeiro', $this->lastBot($token));

        $this->say($token, '1');

        $demand = WhatsAppDemand::firstOrFail();
        $this->assertSame(WhatsAppDriver::Web, $demand->driver);
        $this->assertSame('Financeiro', $demand->sector);
        $this->assertSame(DemandStatus::Open, $demand->status);
        $this->assertSame($this->chat($token)->identifier, $demand->phone);
        $this->assertSame('Maria', $demand->push_name);
        $this->assertStringContainsString('*Financeiro*', $this->lastBot($token));
        $this->assertSame(ConversationStatus::WithAgent, WhatsAppConversation::firstOrFail()->status);
    }

    public function test_after_the_demand_opens_the_bot_stops_answering_and_messages_go_to_the_demand(): void
    {
        $token = $this->start()->json('token');
        $this->say($token, '1');
        $botMessages = count($this->botTexts($token));

        $this->say($token, 'alguém aí?');

        $this->assertCount($botMessages, $this->botTexts($token));
        $demandMessage = WhatsAppDemand::firstOrFail()->messages()->firstOrFail();
        $this->assertSame(MessageDirection::Inbound, $demandMessage->direction);
        $this->assertSame('alguém aí?', $demandMessage->body);
    }

    public function test_invalid_option_repeats_the_menu_and_zero_restarts_it(): void
    {
        $token = $this->start()->json('token');

        $this->say($token, '99');
        $this->assertStringContainsString('Opção inválida', $this->lastBot($token));

        $this->say($token, '2');
        $this->say($token, '0');
        $this->assertStringContainsString('1 - Comercial', $this->lastBot($token));
        $this->assertSame(WhatsAppMenuNode::whereNull('parent_id')->value('id'), WhatsAppConversation::firstOrFail()->current_node_id);
    }

    public function test_conversation_restarts_after_the_inactivity_timeout(): void
    {
        $token = $this->start()->json('token');
        $this->say($token, '2');
        WhatsAppConversation::query()->update(['last_interaction_at' => Carbon::now()->subMinutes(31)]);

        $this->say($token, '1');

        $this->assertStringContainsString('1 - Comercial', $this->lastBot($token));
        $this->assertDatabaseCount('whatsapp_demands', 0);
    }

    public function test_agent_reply_reaches_the_visitor_by_polling_and_is_recorded(): void
    {
        $token = $this->start()->json('token');
        $this->say($token, '1');
        $demand = WhatsAppDemand::firstOrFail();
        $agent = User::factory()->create();
        $after = $this->chat($token)->messages()->max('id');

        $result = app(ReplyDemandService::class)->run($demand, 'Olá! Como posso ajudar?', $agent->id);

        $this->assertTrue($result->success);
        $this->getJson("/api/chat/{$token}/messages?after={$after}")
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.direction', 'outbound')
            ->assertJsonPath('data.0.body', 'Olá! Como posso ajudar?');
        $this->assertDatabaseHas('whatsapp_demand_messages', ['demand_id' => $demand->id, 'body' => 'Olá! Como posso ajudar?', 'user_id' => $agent->id, 'direction' => 'outbound']);
        $this->assertSame(DemandStatus::InProgress, $demand->fresh()->status);
    }

    public function test_after_the_demand_is_closed_the_next_message_brings_the_menu_back(): void
    {
        $token = $this->start()->json('token');
        $this->say($token, '1');

        app(CloseDemandService::class)->run(WhatsAppDemand::firstOrFail());
        $this->say($token, 'oi de novo');

        $this->assertStringContainsString('1 - Comercial', $this->lastBot($token));

        $this->say($token, '1');
        $this->assertSame(2, WhatsAppDemand::count());
    }

    public function test_without_a_configured_menu_the_bot_stays_silent(): void
    {
        WhatsAppMenuNode::query()->forceDelete();

        $token = $this->start()->json('token');
        $this->say($token, 'oi');

        $this->assertSame([], $this->botTexts($token));
        $this->assertSame(1, $this->chat($token)->messages()->count());
    }

    public function test_start_and_send_validate_the_payload(): void
    {
        $this->start(['name' => ''])->assertStatus(422)->assertJsonValidationErrors('name');
        $this->start(['email' => 'invalido'])->assertStatus(422)->assertJsonValidationErrors('email');
        $this->start(['email' => null, 'phone' => null])->assertCreated();

        $token = $this->start()->json('token');
        $this->postJson("/api/chat/{$token}/messages", ['message' => ''])->assertStatus(422);
        $this->postJson("/api/chat/{$token}/messages", ['message' => str_repeat('a', 2001)])->assertStatus(422);
    }

    public function test_html_is_stored_as_plain_text(): void
    {
        $token = $this->start()->json('token');

        $this->say($token, '<script>alert(1)</script>');

        $this->assertSame('<script>alert(1)</script>', $this->chat($token)->messages()->where('direction', MessageDirection::Inbound->value)->first()->body);
    }

    public function test_polling_returns_only_new_messages_in_order(): void
    {
        $token = $this->start()->json('token');
        $first = $this->chat($token)->messages()->first()->id;

        $this->say($token, '2');

        $response = $this->getJson("/api/chat/{$token}/messages?after={$first}")->assertOk();
        $this->assertSame(['inbound', 'outbound'], collect($response->json('data'))->pluck('direction')->all());

        $last = collect($response->json('data'))->last()['id'];
        $this->getJson("/api/chat/{$token}/messages?after={$last}")->assertJsonCount(0, 'data');
        $this->getJson("/api/chat/{$token}/messages?after=-1")->assertStatus(422);
        $this->assertNotNull($this->chat($token)->last_seen_at);
    }

    public function test_unknown_token_is_not_found_and_tokens_are_isolated(): void
    {
        $a = $this->start(['name' => 'Ana'])->json('token');
        $b = $this->start(['name' => 'Bia'])->json('token');
        $this->say($a, 'segredo da Ana');

        $this->getJson('/api/chat/token-inexistente/messages')->assertNotFound();
        $this->postJson('/api/chat/token-inexistente/messages', ['message' => 'oi'])->assertNotFound();

        $bodies = collect($this->getJson("/api/chat/{$b}/messages")->json('data'))->pluck('body')->implode('|');
        $this->assertStringNotContainsString('segredo da Ana', $bodies);
        $this->assertNotSame($this->chat($a)->identifier, $this->chat($b)->identifier);
        $this->assertSame(2, WhatsAppConversation::count());
    }

    public function test_the_internal_identifier_is_never_sent_to_the_browser(): void
    {
        $response = $this->start();

        $this->assertStringNotContainsString($this->chat($response->json('token'))->identifier, $response->getContent());
    }

    public function test_start_is_rate_limited(): void
    {
        foreach (range(1, 10) as $i) {
            $this->start()->assertCreated();
        }

        $this->start()->assertStatus(429);
    }

    public function test_panel_shows_the_visitor_data_and_the_reply_action_reaches_the_chat(): void
    {
        $token = $this->start()->json('token');
        $this->say($token, '1');
        $demand = WhatsAppDemand::firstOrFail();
        $this->actingAs(User::factory()->create(['is_master' => true]));

        $this->get("/admin/demandas/{$demand->id}")->assertOk()->assertSee('maria@exemplo.com')->assertSee('85999990000')->assertSee('Web (chat do site)');

        Livewire::test(ViewWhatsAppDemand::class, ['record' => $demand->getRouteKey()])
            ->callAction('reply', ['message' => 'Resposta pelo painel'])
            ->assertNotified('Mensagem enviada');

        $this->getJson("/api/chat/{$token}/messages")->assertJsonFragment(['body' => 'Resposta pelo painel']);
    }

    public function test_every_sector_of_the_full_menu_is_reachable_through_the_web_chat(): void
    {
        $this->withoutMiddleware(ThrottleRequests::class);

        WhatsAppMenuNode::query()->forceDelete();
        $this->seed(MenuNodeSeeder::class);

        foreach (WhatsAppMenuNode::where('type', MenuNodeType::Sector->value)->get() as $leaf) {
            $path = [];

            for ($node = $leaf; $node->parent_id !== null; $node = $node->parent) {
                array_unshift($path, $node->option_number);
            }

            $token = $this->start(['name' => 'Visitante '.$leaf->id])->json('token');

            foreach ($path as $number) {
                $this->say($token, $number);
            }

            $this->assertDatabaseHas('whatsapp_demands', ['driver' => 'web', 'phone' => $this->chat($token)->identifier, 'menu_node_id' => $leaf->id]);
        }
    }
}
