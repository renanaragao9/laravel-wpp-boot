<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\HasDashboardPeriod;
use App\Services\Dashboard\DashboardMetricsService;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DashboardStats extends StatsOverviewWidget
{
    use HasDashboardPeriod;

    protected static ?int $sort = 1;

    protected ?string $pollingInterval = '30s';

    protected int|array|null $columns = 3;

    protected function getStats(): array
    {
        $days = $this->periodDays();
        $data = app(DashboardMetricsService::class)->overview($this->periodStart(), $days);
        $period = $days === 1 ? 'hoje' : "últimos {$days} dias";

        $trend = $data['created'] - $data['created_previous'];

        return [
            Stat::make('Demandas ativas', $data['active'])
                ->description($data['unassigned_open'].' aberta(s) sem atendente')
                ->descriptionIcon(Heroicon::OutlinedInboxArrowDown)
                ->color($data['unassigned_open'] > 0 ? 'warning' : 'success'),

            Stat::make('Novas demandas', $data['created'])
                ->description(($trend >= 0 ? '+' : '').$trend.' vs. período anterior ('.$period.')')
                ->descriptionIcon($trend >= 0 ? Heroicon::OutlinedArrowTrendingUp : Heroicon::OutlinedArrowTrendingDown)
                ->chart($data['created_per_day'])
                ->color('info'),

            Stat::make('Concluídas no período', $data['done'])
                ->description($period)
                ->descriptionIcon(Heroicon::OutlinedCheckCircle)
                ->color('success'),

            Stat::make('Primeira resposta (média)', DashboardMetricsService::formatMinutes($data['average_first_reply_minutes']))
                ->description('da abertura até a 1ª resposta da equipe')
                ->descriptionIcon(Heroicon::OutlinedClock)
                ->color('gray'),

            Stat::make('Conversas no bot', $data['navigating'])
                ->description('navegando no menu agora')
                ->descriptionIcon(Heroicon::OutlinedChatBubbleLeftRight)
                ->color('gray'),

            Stat::make('Webhooks recebidos', $data['webhooks'])
                ->description($data['webhooks_failed'] > 0 ? $data['webhooks_failed'].' com falha' : 'nenhuma falha')
                ->descriptionIcon($data['webhooks_failed'] > 0 ? Heroicon::OutlinedExclamationTriangle : Heroicon::OutlinedBolt)
                ->color($data['webhooks_failed'] > 0 ? 'danger' : 'success'),
        ];
    }
}
