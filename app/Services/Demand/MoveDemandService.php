<?php

declare(strict_types=1);

namespace App\Services\Demand;

use App\Enums\DemandStatus;
use App\Models\WhatsAppDemand;
use Illuminate\Support\Facades\DB;

class MoveDemandService
{
    public function run(WhatsAppDemand $demand, DemandStatus $status, int $position): WhatsAppDemand
    {
        return DB::transaction(function () use ($demand, $status, $position): WhatsAppDemand {
            $ids = WhatsAppDemand::query()
                ->where('status', $status->value)
                ->whereKeyNot($demand->id)
                ->orderBy('position')
                ->orderBy('id')
                ->pluck('id')
                ->all();

            array_splice($ids, max(0, min($position, count($ids))), 0, [$demand->id]);

            $demand->update(['status' => $status]);

            foreach ($ids as $index => $id) {
                WhatsAppDemand::query()->whereKey($id)->update(['position' => $index]);
            }

            return $demand->refresh();
        });
    }
}
