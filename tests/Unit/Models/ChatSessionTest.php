<?php

namespace Tests\Unit\Models;

use App\Models\ChatSession;
use App\Models\WhatsAppDemand;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatSessionTest extends TestCase
{
    use RefreshDatabase;

    protected function make(array $attributes = []): ChatSession
    {
        return ChatSession::create(array_merge(['token' => 'tok'.uniqid(), 'identifier' => 'web-'.uniqid(), 'name' => 'Maria'], $attributes));
    }

    public function test_can_create(): void
    {
        $record = $this->make();

        $this->assertDatabaseHas('chat_sessions', ['id' => $record->id]);
    }

    public function test_can_read(): void
    {
        $this->assertNotNull(ChatSession::find($this->make()->id));
    }

    public function test_can_update(): void
    {
        $record = $this->make();

        $record->update(['name' => 'Ana']);

        $this->assertDatabaseHas('chat_sessions', ['id' => $record->id] + ['name' => 'Ana']);
    }

    public function test_can_delete(): void
    {
        $record = $this->make();

        $record->delete();

        $this->assertSoftDeleted('chat_sessions', ['id' => $record->id]);
        $this->assertNull(ChatSession::find($record->id));
    }

    public function test_token_is_hidden_when_serialized(): void
    {
        $this->assertArrayNotHasKey('token', $this->make()->toArray());
    }

    public function test_demands_are_linked_by_the_identifier_stored_as_phone(): void
    {
        $session = $this->make(['identifier' => 'web-abc']);
        $demand = WhatsAppDemand::create(['driver' => 'web', 'phone' => 'web-abc', 'sector' => 'S', 'title' => 'T']);

        $this->assertTrue($session->demands->contains($demand));
        $this->assertTrue($demand->chatSession->is($session));
    }

    public function test_has_messages_relationship(): void
    {
        $this->assertInstanceOf(HasMany::class, $this->make()->messages());
    }
}
