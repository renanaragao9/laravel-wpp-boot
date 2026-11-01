<?php

namespace Tests\Feature;

use App\Enums\MenuNodeType;
use App\Filament\Resources\MenuNodes\Pages\CreateWhatsAppMenuNode;
use App\Filament\Resources\MenuNodes\Pages\EditWhatsAppMenuNode;
use App\Filament\Resources\MenuNodes\Pages\ListMenuNodes;
use App\Models\User;
use App\Models\WhatsAppMenuNode;
use App\Services\Menu\BuildMenuTreeService;
use Filament\Forms\Components\Select;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Support\SimpleMenuSeeder;
use Tests\TestCase;

class MenuBuilderTest extends TestCase
{
    use RefreshDatabase;

    private WhatsAppMenuNode $root;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
        $this->seed(SimpleMenuSeeder::class);
        $this->root = WhatsAppMenuNode::root();
    }

    private function support(): WhatsAppMenuNode
    {
        return WhatsAppMenuNode::where('label', 'Suporte')->firstOrFail();
    }

    public function test_first_node_can_be_the_root_but_a_second_root_is_refused(): void
    {
        WhatsAppMenuNode::query()->forceDelete();

        Livewire::test(CreateWhatsAppMenuNode::class)
            ->assertSet('data.parent_id', null)
            ->fillForm(['label' => 'Menu principal', 'type' => 'menu'])
            ->call('create')
            ->assertHasNoFormErrors();

        Livewire::test(CreateWhatsAppMenuNode::class)
            ->fillForm(['parent_id' => null, 'label' => 'Outra raiz', 'type' => 'menu'])
            ->call('create')
            ->assertHasFormErrors(['parent_id' => 'required']);
    }

    public function test_new_option_defaults_to_the_root_and_can_come_from_the_url(): void
    {
        Livewire::test(CreateWhatsAppMenuNode::class)->assertSet('data.parent_id', $this->root->id);

        Livewire::withQueryParams(['parent_id' => $this->support()->id])
            ->test(CreateWhatsAppMenuNode::class)
            ->assertSet('data.parent_id', $this->support()->id);
    }

    public function test_page_title_is_contextual(): void
    {
        Livewire::withQueryParams(['parent_id' => $this->support()->id])
            ->test(CreateWhatsAppMenuNode::class)
            ->assertSee('Nova opção em "Suporte"');

        WhatsAppMenuNode::query()->forceDelete();

        Livewire::test(CreateWhatsAppMenuNode::class)->assertSee('Criar menu principal');
    }

    public function test_option_number_is_required_inside_a_menu_and_unique_per_parent(): void
    {
        Livewire::test(CreateWhatsAppMenuNode::class)
            ->fillForm(['parent_id' => $this->root->id, 'label' => 'Novo', 'type' => 'menu', 'option_number' => null])
            ->call('create')
            ->assertHasFormErrors(['option_number' => 'required']);

        Livewire::test(CreateWhatsAppMenuNode::class)
            ->fillForm(['parent_id' => $this->root->id, 'label' => 'Duplicada', 'type' => 'menu', 'option_number' => '1'])
            ->call('create')
            ->assertHasFormErrors(['option_number' => 'unique']);

        Livewire::test(CreateWhatsAppMenuNode::class)
            ->fillForm(['parent_id' => $this->root->id, 'label' => 'Terceira', 'type' => 'menu', 'option_number' => '3'])
            ->call('create')
            ->assertHasNoFormErrors();
    }

    public function test_same_number_is_allowed_in_another_menu_and_when_the_old_one_was_deleted(): void
    {
        Livewire::test(CreateWhatsAppMenuNode::class)
            ->fillForm(['parent_id' => $this->support()->id, 'label' => 'Terceiro', 'type' => 'menu', 'option_number' => '1'])
            ->call('create')
            ->assertHasFormErrors(['option_number' => 'unique']);

        WhatsAppMenuNode::where('label', 'Comercial')->firstOrFail()->delete();

        Livewire::test(CreateWhatsAppMenuNode::class)
            ->fillForm(['parent_id' => $this->root->id, 'label' => 'Novo 1', 'type' => 'menu', 'option_number' => '1'])
            ->call('create')
            ->assertHasNoFormErrors();
    }

    public function test_option_number_has_a_maximum_length(): void
    {
        Livewire::test(CreateWhatsAppMenuNode::class)
            ->fillForm(['parent_id' => $this->root->id, 'label' => 'X', 'type' => 'menu', 'option_number' => '12345678901'])
            ->call('create')
            ->assertHasFormErrors(['option_number' => 'max']);
    }

    public function test_parent_options_only_list_menus_and_never_the_node_itself_or_its_descendants(): void
    {
        $nested = WhatsAppMenuNode::create(['parent_id' => $this->support()->id, 'label' => 'Sub-menu', 'option_number' => '3', 'type' => MenuNodeType::Menu]);
        $expected = [$this->root->id, $this->support()->id, $nested->id];

        Livewire::test(CreateWhatsAppMenuNode::class)
            ->assertFormFieldExists('parent_id', fn (Select $field) => collect($field->getOptions())->keys()->sort()->values()->all() === collect($expected)->sort()->values()->all());

        Livewire::test(EditWhatsAppMenuNode::class, ['record' => $this->support()->getRouteKey()])
            ->assertFormFieldExists('parent_id', fn (Select $field) => collect($field->getOptions())->keys()->all() === [$this->root->id]);
    }

    public function test_sector_requires_the_sector_name(): void
    {
        Livewire::test(CreateWhatsAppMenuNode::class)
            ->fillForm(['parent_id' => $this->root->id, 'label' => 'Setor', 'type' => 'sector', 'option_number' => '9'])
            ->call('create')
            ->assertHasFormErrors(['sector' => 'required']);
    }

    public function test_node_with_children_cannot_be_deleted_but_a_leaf_can(): void
    {
        Livewire::test(ListMenuNodes::class)
            ->callTableAction('delete', $this->support())
            ->assertNotified('Não é possível remover');

        $this->assertNotSoftDeleted($this->support());

        $leaf = WhatsAppMenuNode::where('label', 'Comercial')->firstOrFail();

        Livewire::test(ListMenuNodes::class)->callTableAction('delete', $leaf);

        $this->assertSoftDeleted($leaf);
    }

    public function test_bulk_delete_is_blocked_when_a_selected_node_has_children(): void
    {
        Livewire::test(ListMenuNodes::class)
            ->callTableBulkAction('delete', [$this->support()])
            ->assertNotified('Não é possível remover');

        $this->assertNotSoftDeleted($this->support());
    }

    public function test_tree_follows_parent_order_with_depth_and_numeric_sorting(): void
    {
        WhatsAppMenuNode::create(['parent_id' => $this->root->id, 'label' => 'Décima', 'option_number' => '10', 'type' => MenuNodeType::Sector, 'sector' => 'X']);

        $rows = app(BuildMenuTreeService::class)->run()->map(fn (array $row) => str_repeat('-', $row['depth']).$row['node']->label)->all();

        $this->assertSame(['Menu inicial', '-Comercial', '-Suporte', '--Financeiro', '--Problema técnico', '-Décima'], $rows);
    }

    public function test_list_page_shows_the_tree_with_badges_and_add_links(): void
    {
        $this->support()->update(['active' => false]);

        Livewire::test(ListMenuNodes::class)
            ->assertSee('Árvore do menu')
            ->assertSee('Financeiro')
            ->assertSee('inativo')
            ->assertSee('Nova opção');

        $this->get('/admin/menu')->assertOk()->assertSee('parent_id='.$this->support()->id, false);
    }

    public function test_next_option_number_is_suggested_for_the_default_and_the_chosen_parent(): void
    {
        Livewire::test(CreateWhatsAppMenuNode::class)->assertSet('data.option_number', '3');

        Livewire::withQueryParams(['parent_id' => $this->support()->id])
            ->test(CreateWhatsAppMenuNode::class)
            ->assertSet('data.option_number', '3');
    }

    public function test_suggestion_follows_the_parent_but_never_overwrites_what_the_user_typed(): void
    {
        $nested = WhatsAppMenuNode::create(['parent_id' => $this->support()->id, 'label' => 'Sub', 'option_number' => '7', 'type' => MenuNodeType::Menu]);

        Livewire::test(CreateWhatsAppMenuNode::class)
            ->assertSet('data.option_number', '3')
            ->set('data.parent_id', $this->support()->id)
            ->assertSet('data.option_number', '8')
            ->set('data.parent_id', $nested->id)
            ->assertSet('data.option_number', '1')
            ->set('data.option_number', '42')
            ->set('data.parent_id', $this->root->id)
            ->assertSet('data.option_number', '42');
    }

    public function test_next_number_ignores_deleted_and_non_numeric_options_and_starts_at_one(): void
    {
        $this->assertSame('1', WhatsAppMenuNode::nextOptionNumber(WhatsAppMenuNode::where('label', 'Comercial')->value('id')));

        WhatsAppMenuNode::create(['parent_id' => $this->root->id, 'label' => 'Letra', 'option_number' => 'A', 'type' => MenuNodeType::Menu]);
        WhatsAppMenuNode::create(['parent_id' => $this->root->id, 'label' => 'Apagada', 'option_number' => '9', 'type' => MenuNodeType::Menu])->delete();

        $this->assertSame('3', WhatsAppMenuNode::nextOptionNumber($this->root->id));
        $this->assertNull(WhatsAppMenuNode::nextOptionNumber(null));
    }

    public function test_editing_does_not_change_the_option_number(): void
    {
        Livewire::test(EditWhatsAppMenuNode::class, ['record' => $this->support()->getRouteKey()])
            ->assertSet('data.option_number', '2');
    }
}
