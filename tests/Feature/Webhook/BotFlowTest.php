<?php

namespace Tests\Feature\Webhook;

use App\Enums\ConversationStatus;
use App\Enums\DemandStatus;
use App\Enums\MessageDirection;
use App\Models\User;
use App\Models\WhatsAppConversation;
use App\Models\WhatsAppDemand;
use App\Models\WhatsAppMenuNode;
use App\Services\Demand\CloseDemandService;
use App\Services\Demand\ReplyDemandService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

class BotFlowTest extends WebhookTestCase
{
    private function say(string $text, string $id): void
    {
        $this->postMeta($this->metaPayload('5585999990000', $text, $id))->assertOk();
    }

    private function lastReply(): string
    {
        return (string) Http::recorded()->last()[0]['text']['body'];
    }

    public function test_navigates_to_a_sector_and_opens_a_demand(): void
    {
        $this->say('oi', 'm1');
        $this->say('2', 'm2');
        $this->assertStringContainsString('1 - Financeiro', $this->lastReply());

        $this->say('1', 'm3');

        $demand = WhatsAppDemand::firstOrFail();
        $this->assertSame('Financeiro', $demand->sector);
        $this->assertSame(DemandStatus::Open, $demand->status);
        $this->assertSame('5585999990000', $demand->phone);
        $this->assertSame(ConversationStatus::WithAgent, WhatsAppConversation::firstOrFail()->status);
        $this->assertStringContainsString('*Financeiro*', $this->lastReply());
    }

    public function test_after_the_demand_opens_the_bot_stops_answering_and_stores_messages(): void
    {
        $this->say('oi', 'm1');
        $this->say('1', 'm2');
        $sent = Http::recorded()->count();

        $this->say('alguém aí?', 'm3');

        $this->assertSame($sent, Http::recorded()->count());
        $message = WhatsAppDemand::firstOrFail()->messages()->firstOrFail();
        $this->assertSame(MessageDirection::Inbound, $message->direction);
        $this->assertSame('alguém aí?', $message->body);
    }

    public function test_invalid_option_repeats_the_menu(): void
    {
        $this->say('oi', 'm1');
        $this->say('99', 'm2');

        $this->assertStringContainsString('Opção inválida', $this->lastReply());
        $this->assertStringContainsString('1 - Comercial', $this->lastReply());
    }

    public function test_zero_restarts_at_the_root_menu(): void
    {
        $this->say('oi', 'm1');
        $this->say('2', 'm2');
        $this->say('0', 'm3');

        $this->assertStringContainsString('1 - Comercial', $this->lastReply());
        $this->assertSame(WhatsAppMenuNode::whereNull('parent_id')->value('id'), WhatsAppConversation::firstOrFail()->current_node_id);
    }

    public function test_conversation_restarts_after_the_inactivity_timeout(): void
    {
        $this->say('oi', 'm1');
        $this->say('2', 'm2');
        WhatsAppConversation::query()->update(['last_interaction_at' => Carbon::now()->subMinutes(31)]);

        $this->say('1', 'm3');

        $this->assertStringContainsString('1 - Comercial', $this->lastReply());
        $this->assertDatabaseCount('whatsapp_demands', 0);
    }

    public function test_without_a_configured_menu_nothing_is_sent(): void
    {
        WhatsAppMenuNode::query()->delete();

        $this->say('oi', 'm1');

        Http::assertNothingSent();
    }

    public function test_agent_reply_is_sent_through_the_demand_driver_and_recorded(): void
    {
        $this->say('oi', 'm1');
        $this->say('1', 'm2');
        $demand = WhatsAppDemand::firstOrFail();
        $user = User::factory()->create();

        $result = app(ReplyDemandService::class)->run($demand, 'Olá, vamos te atender', $user->id);

        $this->assertTrue($result->success);
        $this->assertSame('Olá, vamos te atender', $this->lastReply());
        $this->assertDatabaseHas('whatsapp_demand_messages', ['demand_id' => $demand->id, 'direction' => 'outbound', 'user_id' => $user->id, 'message_id' => 'wamid.out']);
        $this->assertSame(DemandStatus::InProgress, $demand->fresh()->status);
        $this->assertSame($user->id, $demand->fresh()->assigned_user_id);
    }

    public function test_failed_agent_reply_is_not_recorded(): void
    {
        $this->say('oi', 'm1');
        $this->say('1', 'm2');
        config(['whatsapp.meta.access_token' => '']);

        $result = app(ReplyDemandService::class)->run(WhatsAppDemand::firstOrFail(), 'oi', null);

        $this->assertFalse($result->success);
        $this->assertDatabaseCount('whatsapp_demand_messages', 0);
    }

    public function test_closing_the_demand_finishes_the_conversation_and_the_bot_resumes(): void
    {
        $this->say('oi', 'm1');
        $this->say('1', 'm2');

        app(CloseDemandService::class)->run(WhatsAppDemand::firstOrFail());

        $this->assertSame(DemandStatus::Done, WhatsAppDemand::firstOrFail()->status);
        $this->assertSame(ConversationStatus::Finished, WhatsAppConversation::firstOrFail()->status);

        $this->say('oi de novo', 'm3');

        $this->assertStringContainsString('1 - Comercial', $this->lastReply());
    }
}
