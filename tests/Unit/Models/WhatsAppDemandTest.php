<?php

namespace Tests\Unit\Models;

use App\Enums\DemandStatus;
use App\Models\WhatsAppDemand;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WhatsAppDemandTest extends TestCase
{
    use RefreshDatabase;

    protected function make(array $attributes = []): WhatsAppDemand
    {
        return WhatsAppDemand::create(array_merge(['driver' => 'evolution', 'phone' => '5585999990000', 'sector' => 'Comercial', 'title' => 'Comercial'], $attributes));
    }

    public function test_can_create(): void
    {
        $record = $this->make();

        $this->assertDatabaseHas('whatsapp_demands', ['id' => $record->id]);
    }

    public function test_can_read(): void
    {
        $this->assertNotNull(WhatsAppDemand::find($this->make()->id));
    }

    public function test_can_update(): void
    {
        $record = $this->make();

        $record->update(['sector' => 'Financeiro']);

        $this->assertDatabaseHas('whatsapp_demands', ['id' => $record->id] + ['sector' => 'Financeiro']);
    }

    public function test_can_delete(): void
    {
        $record = $this->make();

        $record->delete();

        $this->assertSoftDeleted('whatsapp_demands', ['id' => $record->id]);
        $this->assertNull(WhatsAppDemand::find($record->id));
    }

    public function test_defaults_to_open_and_has_messages_relationship(): void
    {
        $demand = $this->make()->fresh();

        $this->assertSame(DemandStatus::Open, $demand->status);
        $this->assertInstanceOf(HasMany::class, $demand->messages());
    }
}
