<?php

namespace Tests\Unit;

use App\Enums\ConversationStatus;
use App\Enums\DemandStatus;
use App\Enums\MessageDirection;
use App\Enums\WebhookLogStatus;
use App\Enums\WhatsAppDriver;
use App\Models\User;
use App\Models\WhatsAppConversation;
use App\Models\WhatsAppDemand;
use App\Models\WhatsAppDemandMessage;
use App\Models\WhatsAppWebhookLog;
use App\Services\Dashboard\DashboardMetricsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DashboardMetricsServiceTest extends TestCase
{
    use RefreshDatabase;

    private DashboardMetricsService $service;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-11-10 12:00:00');
        $this->service = new DashboardMetricsService;
    }

    private function demand(array $attributes = [], ?string $createdAt = null): WhatsAppDemand
    {
        static $n = 0;
        $n++;

        $demand = WhatsAppDemand::create($attributes + ['driver' => WhatsAppDriver::Meta, 'phone' => "55{$n}", 'sector' => 'Comercial', 'title' => "T{$n}"]);

        if ($createdAt !== null) {
            $demand->forceFill(['created_at' => $createdAt])->save();
        }

        return $demand;
    }

    private function start(int $days): Carbon
    {
        return Carbon::now()->subDays($days - 1)->startOfDay();
    }

    public function test_overview_counts_active_unassigned_new_done_and_trend(): void
    {
        $this->demand(['status' => DemandStatus::Open]);
        $this->demand(['status' => DemandStatus::Open, 'assigned_user_id' => User::factory()->create()->id]);
        $this->demand(['status' => DemandStatus::InProgress]);
        $this->demand(['status' => DemandStatus::Done]);
        $this->demand(['status' => DemandStatus::Open], '2026-10-30 10:00:00');
        $this->demand(['status' => DemandStatus::Open], '2026-10-01 10:00:00');

        $overview = $this->service->overview($this->start(7), 7);

        $this->assertSame(5, $overview['active']);
        $this->assertSame(3, $overview['unassigned_open']);
        $this->assertSame(4, $overview['created']);
        $this->assertSame(1, $overview['created_previous']);
        $this->assertSame(1, $overview['done']);
    }

    public function test_overview_counts_navigating_conversations_and_non_ignored_webhooks(): void
    {
        WhatsAppConversation::create(['driver' => 'meta', 'phone' => '1', 'status' => ConversationStatus::Navigating]);
        WhatsAppConversation::create(['driver' => 'meta', 'phone' => '2', 'status' => ConversationStatus::WithAgent]);
        WhatsAppWebhookLog::create(['driver' => 'meta', 'status' => WebhookLogStatus::Processed]);
        WhatsAppWebhookLog::create(['driver' => 'meta', 'status' => WebhookLogStatus::Failed]);
        WhatsAppWebhookLog::create(['driver' => 'meta', 'status' => WebhookLogStatus::Ignored]);
        $old = WhatsAppWebhookLog::create(['driver' => 'meta', 'status' => WebhookLogStatus::Processed]);
        $old->forceFill(['created_at' => '2026-09-01'])->save();

        $overview = $this->service->overview($this->start(30), 30);

        $this->assertSame(1, $overview['navigating']);
        $this->assertSame(2, $overview['webhooks']);
        $this->assertSame(1, $overview['webhooks_failed']);
    }

    public function test_per_day_fills_missing_days_with_zero_and_separates_new_from_done(): void
    {
        $this->demand([], '2026-11-08 09:00:00');
        $this->demand([], '2026-11-08 18:00:00');
        $done = $this->demand(['status' => DemandStatus::Done], '2026-11-01 09:00:00');
        $done->forceFill(['updated_at' => '2026-11-10 08:00:00'])->save();

        $series = $this->service->perDay($this->start(4), 4);

        $this->assertSame(['07/11', '08/11', '09/11', '10/11'], $series['labels']);
        $this->assertSame([0, 2, 0, 0], $series['created']);
        $this->assertSame([0, 0, 0, 1], $series['done']);
    }

    public function test_by_status_lists_every_status_including_empty_ones(): void
    {
        $this->demand(['status' => DemandStatus::Open]);
        $this->demand(['status' => DemandStatus::Open]);
        $this->demand(['status' => DemandStatus::Done]);

        $this->assertSame(['open' => 2, 'in_progress' => 0, 'waiting' => 0, 'paused' => 0, 'done' => 1], $this->service->byStatus());
    }

    public function test_by_sector_is_ordered_limited_and_respects_the_period(): void
    {
        foreach (['Financeiro', 'Financeiro', 'Financeiro', 'Comercial', 'Comercial', 'RH'] as $sector) {
            $this->demand(['sector' => $sector]);
        }
        $this->demand(['sector' => 'Antigo'], '2026-08-01 10:00:00');

        $this->assertSame(['Financeiro' => 3, 'Comercial' => 2, 'RH' => 1], $this->service->bySector($this->start(30)));
        $this->assertSame(['Financeiro' => 3, 'Comercial' => 2], $this->service->bySector($this->start(30), 2));
    }

    public function test_by_driver_counts_per_channel_in_the_period(): void
    {
        $this->demand(['driver' => WhatsAppDriver::Web]);
        $this->demand(['driver' => WhatsAppDriver::Web]);
        $this->demand(['driver' => WhatsAppDriver::Twilio]);
        $this->demand(['driver' => WhatsAppDriver::Meta], '2026-08-01 10:00:00');

        $this->assertSame(['web' => 2, 'twilio' => 1], $this->service->byDriver($this->start(30)));
    }

    public function test_average_first_reply_uses_only_outbound_messages_and_the_first_one(): void
    {
        $fast = $this->demand([], '2026-11-10 10:00:00');
        $slow = $this->demand([], '2026-11-10 08:00:00');
        $none = $this->demand([], '2026-11-10 09:00:00');

        $message = fn (WhatsAppDemand $d, MessageDirection $direction, string $at) => WhatsAppDemandMessage::create(['demand_id' => $d->id, 'direction' => $direction, 'body' => 'x'])->forceFill(['created_at' => $at])->save();

        $message($fast, MessageDirection::Inbound, '2026-11-10 10:01:00');
        $message($fast, MessageDirection::Outbound, '2026-11-10 10:30:00');
        $message($fast, MessageDirection::Outbound, '2026-11-10 11:30:00');
        $message($slow, MessageDirection::Outbound, '2026-11-10 09:30:00');
        $message($none, MessageDirection::Inbound, '2026-11-10 09:05:00');

        $this->assertSame(60, $this->service->averageFirstReplyMinutes($this->start(7)));
    }

    public function test_average_first_reply_is_null_without_replies(): void
    {
        $this->demand();

        $this->assertNull($this->service->averageFirstReplyMinutes($this->start(7)));
    }

    public function test_format_minutes(): void
    {
        $this->assertSame('-', DashboardMetricsService::formatMinutes(null));
        $this->assertSame('0 min', DashboardMetricsService::formatMinutes(0));
        $this->assertSame('45 min', DashboardMetricsService::formatMinutes(45));
        $this->assertSame('2 h 5 min', DashboardMetricsService::formatMinutes(125));
        $this->assertSame('1 d 3 h', DashboardMetricsService::formatMinutes(1620));
    }
}
