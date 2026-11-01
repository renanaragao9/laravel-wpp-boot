<?php

namespace App\Filament\Widgets\Concerns;

use Carbon\CarbonImmutable;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

trait HasDashboardPeriod
{
    use InteractsWithPageFilters;

    protected function periodDays(): int
    {
        $days = (int) ($this->pageFilters['period'] ?? 30);

        return in_array($days, [7, 30, 90], true) ? $days : 30;
    }

    protected function periodStart(): CarbonImmutable
    {
        return CarbonImmutable::now()->subDays($this->periodDays() - 1)->startOfDay();
    }
}
