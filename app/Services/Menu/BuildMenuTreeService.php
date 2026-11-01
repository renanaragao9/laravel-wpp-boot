<?php

declare(strict_types=1);

namespace App\Services\Menu;

use App\Models\WhatsAppMenuNode;
use Illuminate\Support\Collection;

class BuildMenuTreeService
{
    public function run(): Collection
    {
        $byParent = WhatsAppMenuNode::query()->get()->groupBy(fn (WhatsAppMenuNode $node) => $node->parent_id ?? 0);

        $flat = collect();
        $this->walk($byParent, 0, 0, $flat);

        return $flat;
    }

    private function walk(Collection $byParent, int $parentId, int $depth, Collection $flat): void
    {
        $children = ($byParent->get($parentId) ?? collect())->sortBy(fn (WhatsAppMenuNode $node) => (int) $node->option_number);

        foreach ($children as $node) {
            $flat->push(['node' => $node, 'depth' => $depth]);
            $this->walk($byParent, $node->id, $depth + 1, $flat);
        }
    }
}
