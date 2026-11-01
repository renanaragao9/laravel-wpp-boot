<?php

namespace Tests\Unit;

use App\Enums\DemandStatus;
use App\Models\WhatsAppDemand;
use App\Services\Demand\MoveDemandService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MoveDemandServiceTest extends TestCase
{
    use RefreshDatabase;

    private function demand(string $phone, DemandStatus $status, int $position): WhatsAppDemand
    {
        return WhatsAppDemand::create(['driver' => 'meta', 'phone' => $phone, 'sector' => 'S', 'title' => 'T', 'status' => $status, 'position' => $position]);
    }

    public function test_inserts_at_the_position_and_renumbers_the_column(): void
    {
        $a = $this->demand('1', DemandStatus::Waiting, 0);
        $b = $this->demand('2', DemandStatus::Waiting, 1);
        $moving = $this->demand('3', DemandStatus::Open, 0);

        app(MoveDemandService::class)->run($moving, DemandStatus::Waiting, 0);

        $this->assertSame([$moving->id, $a->id, $b->id], WhatsAppDemand::where('status', 'waiting')->orderBy('position')->pluck('id')->all());
        $this->assertSame([0, 1, 2], WhatsAppDemand::where('status', 'waiting')->orderBy('position')->pluck('position')->all());
    }

    public function test_position_beyond_the_end_or_negative_is_clamped(): void
    {
        $a = $this->demand('1', DemandStatus::Paused, 0);
        $end = $this->demand('2', DemandStatus::Open, 0);
        $start = $this->demand('3', DemandStatus::Open, 1);

        app(MoveDemandService::class)->run($end, DemandStatus::Paused, 50);
        app(MoveDemandService::class)->run($start, DemandStatus::Paused, -5);

        $this->assertSame([$start->id, $a->id, $end->id], WhatsAppDemand::where('status', 'paused')->orderBy('position')->pluck('id')->all());
    }

    public function test_reordering_inside_the_same_column_works(): void
    {
        $a = $this->demand('1', DemandStatus::Open, 0);
        $b = $this->demand('2', DemandStatus::Open, 1);
        $c = $this->demand('3', DemandStatus::Open, 2);

        app(MoveDemandService::class)->run($a, DemandStatus::Open, 2);

        $this->assertSame([$b->id, $c->id, $a->id], WhatsAppDemand::where('status', 'open')->orderBy('position')->pluck('id')->all());
    }
}
