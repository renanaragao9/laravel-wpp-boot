<?php

namespace Tests\Unit\Models;

use App\Enums\ConversationStatus;
use App\Enums\WhatsAppDriver;
use App\Models\WhatsAppConversation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WhatsAppConversationTest extends TestCase
{
    use RefreshDatabase;

    protected function make(array $attributes = []): WhatsAppConversation
    {
        return WhatsAppConversation::create(array_merge(['driver' => 'meta', 'phone' => '5585999990000'], $attributes));
    }

    public function test_can_create(): void
    {
        $record = $this->make();

        $this->assertDatabaseHas('whatsapp_conversations', ['id' => $record->id]);
    }

    public function test_can_read(): void
    {
        $this->assertNotNull(WhatsAppConversation::find($this->make()->id));
    }

    public function test_can_update(): void
    {
        $record = $this->make();

        $record->update(['push_name' => 'Maria']);

        $this->assertDatabaseHas('whatsapp_conversations', ['id' => $record->id] + ['push_name' => 'Maria']);
    }

    public function test_can_delete(): void
    {
        $record = $this->make();

        $record->delete();

        $this->assertSoftDeleted('whatsapp_conversations', ['id' => $record->id]);
        $this->assertNull(WhatsAppConversation::find($record->id));
    }

    public function test_defaults_to_navigating_and_casts_enums(): void
    {
        $conversation = $this->make()->fresh();

        $this->assertSame(ConversationStatus::Navigating, $conversation->status);
        $this->assertSame(WhatsAppDriver::Meta, $conversation->driver);
    }
}
