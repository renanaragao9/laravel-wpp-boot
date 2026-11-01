<?php

namespace Tests\Feature;

use App\Enums\DemandStatus;
use App\Enums\WebhookLogStatus;
use App\Enums\WhatsAppDriver;
use App\Filament\Pages\Dashboard;
use App\Filament\Widgets\AwaitingDemandsTable;
use App\Filament\Widgets\DashboardStats;
use App\Filament\Widgets\DemandsByDriverChart;
use App\Filament\Widgets\DemandsBySectorChart;
use App\Filament\Widgets\DemandsByStatusChart;
use App\Filament\Widgets\DemandsPerDayChart;
use App\Filament\Widgets\FailedWebhooksTable;
use App\Models\User;
use App\Models\WhatsAppDemand;
use App\Models\WhatsAppWebhookLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use ReflectionMethod;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
    }

    private function demand(array $attributes = []): WhatsAppDemand
    {
        static $n = 0;
        $n++;

        return WhatsAppDemand::create($attributes + ['driver' => WhatsAppDriver::Meta, 'phone' => "55{$n}", 'push_name' => "Contato {$n}", 'sector' => 'Comercial', 'title' => "Demanda {$n}"]);
    }

    private function protectedCall(object $widget, string $method): mixed
    {
        $reflection = new ReflectionMethod($widget, $method);
        $reflection->setAccessible(true);

        return $reflection->invoke($widget);
    }

    public function test_dashboard_page_renders_all_widgets_and_the_period_filter(): void
    {
        $this->demand();

        $page = $this->get('/admin')->assertOk()->assertSee('Período')->assertDontSee('Documentação');

        foreach ([
            DashboardStats::class,
            DemandsPerDayChart::class,
            DemandsByStatusChart::class,
            DemandsByDriverChart::class,
            DemandsBySectorChart::class,
            AwaitingDemandsTable::class,
            FailedWebhooksTable::class,
        ] as $widget) {
            $page->assertSeeLivewire($widget);
        }

        Livewire::test(Dashboard::class)->assertSet('filters.period', 30);
    }

    public function test_stats_react_to_the_selected_period(): void
    {
        $this->demand();
        $old = $this->demand();
        $old->forceFill(['created_at' => now()->subDays(20)])->save();

        $created = function (int $period): int {
            $widget = Livewire::test(DashboardStats::class, ['pageFilters' => ['period' => $period]])->instance();

            return (int) $this->protectedCall($widget, 'getStats')[1]->getValue();
        };

        $this->assertSame(1, $created(7));
        $this->assertSame(2, $created(30));
    }

    public function test_invalid_period_falls_back_to_thirty_days(): void
    {
        $widget = Livewire::test(DashboardStats::class, ['pageFilters' => ['period' => 999]])->instance();

        $this->assertSame(30, $this->protectedCall($widget, 'periodDays'));
    }

    public function test_stats_flag_failures_and_unassigned_demands(): void
    {
        $this->demand(['status' => DemandStatus::Open]);
        WhatsAppWebhookLog::create(['driver' => 'meta', 'status' => WebhookLogStatus::Failed]);

        Livewire::test(DashboardStats::class)
            ->assertSee('1 aberta(s) sem atendente')
            ->assertSee('1 com falha');
    }

    public function test_charts_render_with_their_headings(): void
    {
        $this->demand();

        foreach ([
            DemandsPerDayChart::class => 'Demandas por dia',
            DemandsByStatusChart::class => 'Situação atual das demandas',
            DemandsByDriverChart::class => 'Demandas por canal',
            DemandsBySectorChart::class => 'Demandas por setor',
        ] as $chart => $heading) {
            Livewire::test($chart)->assertSee($heading);
        }
    }

    public function test_chart_data_matches_the_metrics(): void
    {
        $this->demand(['status' => DemandStatus::Open, 'sector' => 'Financeiro']);
        $this->demand(['status' => DemandStatus::Done, 'sector' => 'Financeiro', 'driver' => WhatsAppDriver::Web]);

        $status = $this->protectedCall(Livewire::test(DemandsByStatusChart::class)->instance(), 'getData');
        $this->assertSame([1, 0, 0, 0, 1], $status['datasets'][0]['data']);
        $this->assertSame(['Aberta', 'Em andamento', 'Aguardando', 'Pausada', 'Concluída'], $status['labels']);

        $sector = $this->protectedCall(Livewire::test(DemandsBySectorChart::class)->instance(), 'getData');
        $this->assertSame(['Financeiro'], $sector['labels']);
        $this->assertSame([2], $sector['datasets'][0]['data']);

        $driver = $this->protectedCall(Livewire::test(DemandsByDriverChart::class)->instance(), 'getData');
        $this->assertEqualsCanonicalizing(['WhatsApp Cloud API (Meta)', 'Web (chat do site)'], $driver['labels']);

        $perDay = $this->protectedCall(Livewire::test(DemandsPerDayChart::class, ['pageFilters' => ['period' => 7]])->instance(), 'getData');
        $this->assertCount(7, $perDay['labels']);
        $this->assertSame(2, array_sum($perDay['datasets'][0]['data']));
    }

    public function test_awaiting_table_lists_only_open_unassigned_demands_oldest_first(): void
    {
        $user = User::factory()->create();
        $recent = $this->demand();
        $oldest = $this->demand();
        $oldest->forceFill(['created_at' => now()->subHours(5)])->save();
        $assigned = $this->demand(['assigned_user_id' => $user->id]);
        $progress = $this->demand(['status' => DemandStatus::InProgress]);

        Livewire::test(AwaitingDemandsTable::class)
            ->assertCanSeeTableRecords([$oldest, $recent], inOrder: true)
            ->assertCanNotSeeTableRecords([$assigned, $progress]);
    }

    public function test_failed_webhooks_table_lists_only_failures(): void
    {
        $failed = WhatsAppWebhookLog::create(['driver' => 'meta', 'status' => WebhookLogStatus::Failed, 'error' => 'Falha ao processar']);
        $ok = WhatsAppWebhookLog::create(['driver' => 'meta', 'status' => WebhookLogStatus::Processed]);

        Livewire::test(FailedWebhooksTable::class)
            ->assertCanSeeTableRecords([$failed])
            ->assertCanNotSeeTableRecords([$ok])
            ->assertSee('Falha ao processar');
    }

    public function test_empty_tables_show_friendly_messages(): void
    {
        Livewire::test(AwaitingDemandsTable::class)->assertSee('Nenhuma demanda esperando');
        Livewire::test(FailedWebhooksTable::class)->assertSee('Nenhuma falha');
    }
}
