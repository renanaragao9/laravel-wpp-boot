<?php

declare(strict_types=1);

namespace App\Services\Dashboard;

use App\Enums\ConversationStatus;
use App\Enums\DemandStatus;
use App\Enums\MessageDirection;
use App\Enums\WebhookLogStatus;
use App\Models\WhatsAppConversation;
use App\Models\WhatsAppDemand;
use App\Models\WhatsAppWebhookLog;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class DashboardMetricsService
{
    public function overview(CarbonInterface $start, int $days): array
    {
        $previousStart = $start->copy()->subDays($days);

        $created = $this->createdSince($start)->count();
        $previous = WhatsAppDemand::query()->where('created_at', '>=', $previousStart)->where('created_at', '<', $start)->count();

        $webhooks = WhatsAppWebhookLog::query()
            ->where('created_at', '>=', $start)
            ->where('status', '!=', WebhookLogStatus::Ignored->value);

        return [
            'active' => WhatsAppDemand::query()->whereIn('status', $this->activeStatuses())->count(),
            'unassigned_open' => WhatsAppDemand::query()->where('status', DemandStatus::Open->value)->whereNull('assigned_user_id')->count(),
            'created' => $created,
            'created_previous' => $previous,
            'done' => WhatsAppDemand::query()->where('status', DemandStatus::Done->value)->where('updated_at', '>=', $start)->count(),
            'navigating' => WhatsAppConversation::query()->where('status', ConversationStatus::Navigating->value)->count(),
            'webhooks' => (clone $webhooks)->count(),
            'webhooks_failed' => (clone $webhooks)->where('status', WebhookLogStatus::Failed->value)->count(),
            'average_first_reply_minutes' => $this->averageFirstReplyMinutes($start),
            'created_per_day' => $this->perDay($start, $days)['created'],
        ];
    }

    public function perDay(CarbonInterface $start, int $days): array
    {
        $created = $this->countByDay('created_at', $start, null);
        $done = $this->countByDay('updated_at', $start, DemandStatus::Done);

        $labels = [];
        $createdSeries = [];
        $doneSeries = [];

        for ($i = 0; $i < $days; $i++) {
            $day = Carbon::parse($start)->addDays($i);
            $key = $day->toDateString();
            $labels[] = $day->format('d/m');
            $createdSeries[] = $created[$key] ?? 0;
            $doneSeries[] = $done[$key] ?? 0;
        }

        return ['labels' => $labels, 'created' => $createdSeries, 'done' => $doneSeries];
    }

    public function byStatus(): array
    {
        $counts = WhatsAppDemand::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        return collect(DemandStatus::cases())
            ->mapWithKeys(fn (DemandStatus $status) => [$status->value => (int) ($counts[$status->value] ?? 0)])
            ->all();
    }

    public function bySector(CarbonInterface $start, int $limit = 8): array
    {
        return $this->createdSince($start)
            ->selectRaw('sector, count(*) as total')
            ->groupBy('sector')
            ->orderByDesc('total')
            ->orderBy('sector')
            ->limit($limit)
            ->pluck('total', 'sector')
            ->map(fn ($total) => (int) $total)
            ->all();
    }

    public function byDriver(CarbonInterface $start): array
    {
        return $this->createdSince($start)
            ->selectRaw('driver, count(*) as total')
            ->groupBy('driver')
            ->orderByDesc('total')
            ->pluck('total', 'driver')
            ->map(fn ($total) => (int) $total)
            ->all();
    }

    public function averageFirstReplyMinutes(CarbonInterface $start): ?int
    {
        $minutes = WhatsAppDemand::query()
            ->where('created_at', '>=', $start)
            ->withMin(['messages as first_reply_at' => fn ($query) => $query->where('direction', MessageDirection::Outbound->value)], 'created_at')
            ->get(['id', 'created_at'])
            ->filter(fn (WhatsAppDemand $demand) => $demand->first_reply_at !== null)
            ->map(fn (WhatsAppDemand $demand) => $demand->created_at->diffInMinutes(Carbon::parse($demand->first_reply_at)));

        return $minutes->isEmpty() ? null : (int) round($minutes->avg());
    }

    public static function formatMinutes(?int $minutes): string
    {
        if ($minutes === null) {
            return '-';
        }

        if ($minutes < 60) {
            return $minutes.' min';
        }

        if ($minutes < 1440) {
            return intdiv($minutes, 60).' h '.($minutes % 60).' min';
        }

        return intdiv($minutes, 1440).' d '.intdiv($minutes % 1440, 60).' h';
    }

    private function createdSince(CarbonInterface $start)
    {
        return WhatsAppDemand::query()->where('created_at', '>=', $start);
    }

    private function countByDay(string $column, CarbonInterface $start, ?DemandStatus $status): array
    {
        return WhatsAppDemand::query()
            ->where($column, '>=', $start)
            ->when($status, fn ($query) => $query->where('status', $status->value))
            ->selectRaw("DATE({$column}) as day, count(*) as total")
            ->groupBy('day')
            ->pluck('total', 'day')
            ->map(fn ($total) => (int) $total)
            ->all();
    }

    private function activeStatuses(): array
    {
        return array_map(fn (DemandStatus $status) => $status->value, DemandStatus::active());
    }
}
