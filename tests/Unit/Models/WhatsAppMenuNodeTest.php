<?php

namespace Tests\Unit\Models;

use App\Enums\MenuNodeType;
use App\Models\WhatsAppMenuNode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WhatsAppMenuNodeTest extends TestCase
{
    use RefreshDatabase;

    protected function make(array $attributes = []): WhatsAppMenuNode
    {
        return WhatsAppMenuNode::create(array_merge(['label' => 'Menu', 'type' => 'menu'], $attributes));
    }

    public function test_can_create(): void
    {
        $record = $this->make();

        $this->assertDatabaseHas('whatsapp_menu_nodes', ['id' => $record->id]);
    }

    public function test_can_read(): void
    {
        $this->assertNotNull(WhatsAppMenuNode::find($this->make()->id));
    }

    public function test_can_update(): void
    {
        $record = $this->make();

        $record->update(['label' => 'Novo']);

        $this->assertDatabaseHas('whatsapp_menu_nodes', ['id' => $record->id] + ['label' => 'Novo']);
    }

    public function test_can_delete(): void
    {
        $record = $this->make();

        $record->delete();

        $this->assertSoftDeleted('whatsapp_menu_nodes', ['id' => $record->id]);
        $this->assertNull(WhatsAppMenuNode::find($record->id));
    }

    public function test_has_children_and_parent_relationships(): void
    {
        $root = $this->make();
        $child = $this->make(['parent_id' => $root->id, 'label' => 'Filho', 'option_number' => '1']);

        $this->assertTrue($root->children->contains($child));
        $this->assertTrue($child->parent->is($root));
    }

    public function test_type_is_cast_to_enum_and_active_to_boolean(): void
    {
        $node = $this->make()->fresh();

        $this->assertSame(MenuNodeType::Menu, $node->type);
        $this->assertTrue($node->active);
    }
}
