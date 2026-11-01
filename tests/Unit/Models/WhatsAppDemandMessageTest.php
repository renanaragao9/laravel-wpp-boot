<?php

namespace Tests\Unit\Models;

use App\Enums\MessageDirection;
use App\Models\WhatsAppDemand;
use App\Models\WhatsAppDemandMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WhatsAppDemandMessageTest extends TestCase
{
    use RefreshDatabase;

    protected function make(array $attributes = []): WhatsAppDemandMessage
    {
        return WhatsAppDemandMessage::create(array_merge(['demand_id' => WhatsAppDemand::create(['driver' => 'meta', 'phone' => '1', 'sector' => 'S', 'title' => 'T'])->id, 'body' => 'oi'], $attributes));
    }

    public function test_can_create(): void
    {
        $record = $this->make();

        $this->assertDatabaseHas('whatsapp_demand_messages', ['id' => $record->id]);
    }

    public function test_can_read(): void
    {
        $this->assertNotNull(WhatsAppDemandMessage::find($this->make()->id));
    }

    public function test_can_update(): void
    {
        $record = $this->make();

        $record->update(['body' => 'novo']);

        $this->assertDatabaseHas('whatsapp_demand_messages', ['id' => $record->id] + ['body' => 'novo']);
    }

    public function test_can_delete(): void
    {
        $record = $this->make();

        $record->delete();

        $this->assertSoftDeleted('whatsapp_demand_messages', ['id' => $record->id]);
        $this->assertNull(WhatsAppDemandMessage::find($record->id));
    }

    public function test_defaults_to_inbound_and_belongs_to_demand(): void
    {
        $message = $this->make()->fresh();

        $this->assertSame(MessageDirection::Inbound, $message->direction);
        $this->assertNotNull($message->demand);
    }
}
