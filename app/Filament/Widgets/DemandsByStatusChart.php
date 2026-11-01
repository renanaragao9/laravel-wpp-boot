<?php

namespace App\Filament\Widgets;

use App\Enums\DemandStatus;
use App\Services\Dashboard\DashboardMetricsService;
use Filament\Widgets\ChartWidget;

class DemandsByStatusChart extends ChartWidget
{
    protected static ?int $sort = 3;

    protected ?string $heading = 'Situação atual das demandas';

    protected ?string $maxHeight = '260px';

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getData(): array
    {
        $counts = app(DashboardMetricsService::class)->byStatus();

        return [
            'datasets' => [[
                'data' => array_values($counts),
                'backgroundColor' => array_map(fn (DemandStatus $status) => $status->hex(), DemandStatus::cases()),
            ]],
            'labels' => array_map(fn (DemandStatus $status) => $status->label(), DemandStatus::cases()),
        ];
    }
}
