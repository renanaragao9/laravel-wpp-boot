<?php

namespace Tests\Unit;

use App\Enums\MessageDirection;
use App\Models\WhatsAppDemand;
use App\Models\WhatsAppDemandMessage;
use App\Services\Demand\MarkDemandAsReadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarkDemandAsReadServiceTest extends TestCase
{
    use RefreshDatabase;

    private function demand(): WhatsAppDemand
    {
        return WhatsAppDemand::create(['driver' => 'meta', 'phone' => '1', 'sector' => 'S', 'title' => 'T']);
    }

    public function test_marks_inbound_messages_as_read_and_sets_seen_at(): void
    {
        $demand = $this->demand();
        $inbound = WhatsAppDemandMessage::create(['demand_id' => $demand->id, 'direction' => MessageDirection::Inbound, 'body' => 'a']);
        $outbound = WhatsAppDemandMessage::create(['demand_id' => $demand->id, 'direction' => MessageDirection::Outbound, 'body' => 'b']);

        app(MarkDemandAsReadService::class)->run($demand);

        $this->assertNotNull($inbound->fresh()->read_at);
        $this->assertNull($outbound->fresh()->read_at);
        $this->assertNotNull($demand->fresh()->seen_at);
    }

    public function test_keeps_the_original_seen_at_and_read_at(): void
    {
        $demand = $this->demand();
        $demand->forceFill(['seen_at' => '2026-11-01 10:00:00'])->save();
        $read = WhatsAppDemandMessage::create(['demand_id' => $demand->id, 'direction' => MessageDirection::Inbound, 'body' => 'a', 'read_at' => '2026-11-01 10:05:00']);

        app(MarkDemandAsReadService::class)->run($demand->fresh());

        $this->assertSame('2026-11-01 10:00:00', $demand->fresh()->seen_at->format('Y-m-d H:i:s'));
        $this->assertSame('2026-11-01 10:05:00', $read->fresh()->read_at->format('Y-m-d H:i:s'));
    }

    public function test_only_touches_the_given_demand(): void
    {
        $one = $this->demand();
        $two = WhatsAppDemand::create(['driver' => 'meta', 'phone' => '2', 'sector' => 'S', 'title' => 'T']);
        $other = WhatsAppDemandMessage::create(['demand_id' => $two->id, 'direction' => MessageDirection::Inbound, 'body' => 'x']);

        app(MarkDemandAsReadService::class)->run($one);

        $this->assertNull($other->fresh()->read_at);
        $this->assertNull($two->fresh()->seen_at);
    }
}
