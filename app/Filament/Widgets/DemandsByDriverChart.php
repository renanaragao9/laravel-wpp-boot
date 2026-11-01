<?php

namespace App\Filament\Widgets;

use App\Enums\WhatsAppDriver;
use App\Filament\Widgets\Concerns\HasDashboardPeriod;
use App\Services\Dashboard\DashboardMetricsService;
use Filament\Widgets\ChartWidget;

class DemandsByDriverChart extends ChartWidget
{
    use HasDashboardPeriod;

    protected static ?int $sort = 4;

    protected ?string $heading = 'Demandas por canal';

    protected ?string $maxHeight = '260px';

    protected function getType(): string
    {
        return 'pie';
    }

    protected function getData(): array
    {
        $counts = app(DashboardMetricsService::class)->byDriver($this->periodStart());
        $palette = ['meta' => '#2563eb', 'evolution' => '#16a34a', 'twilio' => '#dc2626', 'web' => '#d97706'];

        return [
            'datasets' => [[
                'data' => array_values($counts),
                'backgroundColor' => array_map(fn (string $driver) => $palette[$driver] ?? '#6b7280', array_keys($counts)),
            ]],
            'labels' => array_map(fn (string $driver) => WhatsAppDriver::tryFrom($driver)?->label() ?? $driver, array_keys($counts)),
        ];
    }
}
