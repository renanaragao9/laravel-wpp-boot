<?php

namespace App\Filament\Resources\Demands\Pages;

use App\Enums\DemandStatus;
use App\Enums\MessageDirection;
use App\Enums\WhatsAppDriver;
use App\Filament\Actions\CloseDemandAction;
use App\Filament\Actions\OpenDemandAction;
use App\Filament\Resources\Demands\WhatsAppDemandResource;
use App\Models\WhatsAppDemand;
use App\Models\WhatsAppDemandMessage;
use App\Services\Demand\MarkDemandAsReadService;
use App\Services\Demand\MoveDemandService;
use Filament\Actions\Action;
use Filament\Resources\Pages\Page;
use Livewire\Attributes\Url;

class ListDemands extends Page
{
    protected static string $resource = WhatsAppDemandResource::class;

    protected string $view = 'filament.demands-kanban';

    public const DONE_LIMIT = 15;

    #[Url(as: 'busca')]
    public string $search = '';

    #[Url(as: 'setor')]
    public string $sector = '';

    #[Url(as: 'provedor')]
    public string $driver = '';

    #[Url(as: 'atendente')]
    public string $assigned = '';

    public function getTitle(): string
    {
        return 'Demandas';
    }

    public function openAction(): Action
    {
        return OpenDemandAction::make()->record(fn (array $arguments) => WhatsAppDemand::findOrFail($arguments['id']));
    }

    public function closeAction(): Action
    {
        return CloseDemandAction::make()->record(fn (array $arguments) => WhatsAppDemand::findOrFail($arguments['id']));
    }

    public function refreshBoard(): void
    {
        foreach ($this->mountedActions as $mounted) {
            if (($mounted['name'] ?? null) === 'open' && isset($mounted['arguments']['id'])) {
                $demand = WhatsAppDemand::query()->find($mounted['arguments']['id']);

                if ($demand !== null) {
                    app(MarkDemandAsReadService::class)->run($demand);
                }
            }
        }
    }

    public function attentionCount(): int
    {
        return WhatsAppDemand::query()
            ->where('status', '!=', DemandStatus::Done->value)
            ->where(fn ($query) => $query
                ->whereNull('seen_at')
                ->orWhereHas('messages', fn ($messages) => $messages->where('direction', MessageDirection::Inbound->value)->whereNull('read_at')))
            ->count();
    }

    public function moveDemand(int|string $id, string $status, int $position): void
    {
        $demand = WhatsAppDemand::query()->findOrFail((int) $id);
        $target = DemandStatus::tryFrom($status);

        if ($target === null || $target === $demand->status) {
            return;
        }

        if ($target === DemandStatus::Done) {
            $this->mountAction('close', ['id' => $demand->id]);

            return;
        }

        app(MoveDemandService::class)->run($demand, $target, $position);
    }

    public function getColumns(): array
    {
        return collect(DemandStatus::cases())->map(function (DemandStatus $status): array {
            $query = $this->query($status);
            $total = (clone $query)->count();

            $demands = $status === DemandStatus::Done
                ? $query->orderByDesc('updated_at')->limit(self::DONE_LIMIT)->get()
                : $query->orderBy('position')->orderBy('id')->limit(100)->get();

            return ['status' => $status, 'total' => $total, 'demands' => $demands];
        })->all();
    }

    public function sectorOptions(): array
    {
        return WhatsAppDemand::query()->distinct()->orderBy('sector')->pluck('sector', 'sector')->all();
    }

    public function driverOptions(): array
    {
        return collect(WhatsAppDriver::cases())->mapWithKeys(fn (WhatsAppDriver $d) => [$d->value => $d->label()])->all();
    }

    private function query(DemandStatus $status)
    {
        return WhatsAppDemand::query()
            ->with('assignedUser')
            ->withCount(['messages as unread_count' => fn ($messages) => $messages->where('direction', MessageDirection::Inbound->value)->whereNull('read_at')])
            ->addSelect(['last_message' => WhatsAppDemandMessage::query()
                ->select('body')
                ->whereColumn('demand_id', 'whatsapp_demands.id')
                ->latest('id')
                ->limit(1)])
            ->where('status', $status->value)
            ->when($this->sector !== '', fn ($q) => $q->where('sector', $this->sector))
            ->when($this->driver !== '', fn ($q) => $q->where('driver', $this->driver))
            ->when($this->assigned === 'me', fn ($q) => $q->where('assigned_user_id', auth()->id()))
            ->when($this->assigned === 'none', fn ($q) => $q->whereNull('assigned_user_id'))
            ->when(trim($this->search) !== '', fn ($q) => $q->where(function ($sub) {
                $term = '%'.trim($this->search).'%';
                $sub->where('title', 'like', $term)->orWhere('push_name', 'like', $term)->orWhere('phone', 'like', $term);
            }));
    }
}
