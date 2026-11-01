<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\HasDashboardPeriod;
use App\Services\Dashboard\DashboardMetricsService;
use Filament\Widgets\ChartWidget;

class DemandsPerDayChart extends ChartWidget
{
    use HasDashboardPeriod;

    protected static ?int $sort = 2;

    protected ?string $heading = 'Demandas por dia';

    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '280px';

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $series = app(DashboardMetricsService::class)->perDay($this->periodStart(), $this->periodDays());

        return [
            'datasets' => [
                [
                    'label' => 'Novas',
                    'data' => $series['created'],
                    'borderColor' => '#3b82f6',
                    'backgroundColor' => 'rgba(59,130,246,.15)',
                    'fill' => true,
                    'tension' => 0.3,
                ],
                [
                    'label' => 'Concluídas',
                    'data' => $series['done'],
                    'borderColor' => '#10b981',
                    'backgroundColor' => 'rgba(16,185,129,.15)',
                    'fill' => true,
                    'tension' => 0.3,
                ],
            ],
            'labels' => $series['labels'],
        ];
    }

    protected function getOptions(): array
    {
        return [
            'scales' => ['y' => ['beginAtZero' => true, 'ticks' => ['precision' => 0]]],
        ];
    }
}
