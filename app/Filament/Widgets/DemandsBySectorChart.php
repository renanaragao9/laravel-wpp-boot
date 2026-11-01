<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\HasDashboardPeriod;
use App\Services\Dashboard\DashboardMetricsService;
use Filament\Widgets\ChartWidget;

class DemandsBySectorChart extends ChartWidget
{
    use HasDashboardPeriod;

    protected static ?int $sort = 5;

    protected ?string $heading = 'Demandas por setor';

    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '280px';

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $counts = app(DashboardMetricsService::class)->bySector($this->periodStart());

        return [
            'datasets' => [[
                'label' => 'Demandas',
                'data' => array_values($counts),
                'backgroundColor' => '#d97706',
                'borderRadius' => 6,
            ]],
            'labels' => array_keys($counts),
        ];
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => ['legend' => ['display' => false]],
            'scales' => ['y' => ['beginAtZero' => true, 'ticks' => ['precision' => 0]]],
        ];
    }
}
