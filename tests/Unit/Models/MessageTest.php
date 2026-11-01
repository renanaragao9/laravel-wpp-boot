<?php

namespace Tests\Unit\Models;

use App\Enums\MessageDirection;
use App\Models\ChatSession;
use App\Models\Message;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MessageTest extends TestCase
{
    use RefreshDatabase;

    protected function make(array $attributes = []): Message
    {
        return Message::create(array_merge(['chat_session_id' => ChatSession::create(['token' => 'tok'.uniqid(), 'identifier' => 'web-'.uniqid(), 'name' => 'Maria'])->id, 'body' => 'oi'], $attributes));
    }

    public function test_can_create(): void
    {
        $record = $this->make();

        $this->assertDatabaseHas('messages', ['id' => $record->id]);
    }

    public function test_can_read(): void
    {
        $this->assertNotNull(Message::find($this->make()->id));
    }

    public function test_can_update(): void
    {
        $record = $this->make();

        $record->update(['body' => 'novo']);

        $this->assertDatabaseHas('messages', ['id' => $record->id] + ['body' => 'novo']);
    }

    public function test_can_delete(): void
    {
        $record = $this->make();

        $record->delete();

        $this->assertSoftDeleted('messages', ['id' => $record->id]);
        $this->assertNull(Message::find($record->id));
    }

    public function test_defaults_to_inbound_and_belongs_to_the_chat_session(): void
    {
        $message = $this->make()->fresh();

        $this->assertSame(MessageDirection::Inbound, $message->direction);
        $this->assertNotNull($message->chatSession);
    }
}
