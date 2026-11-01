<?php

namespace App\Models;

use App\Enums\MenuNodeType;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WhatsAppMenuNode extends BaseModel
{
    protected $table = 'whatsapp_menu_nodes';

    protected $fillable = [
        'parent_id',
        'label',
        'option_number',
        'message',
        'type',
        'sector',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'type' => MenuNodeType::class,
            'active' => 'boolean',
        ];
    }

    public static function root(): ?self
    {
        return static::query()->whereNull('parent_id')->first();
    }

    public static function nextOptionNumber(?int $parentId): ?string
    {
        if ($parentId === null) {
            return null;
        }

        $numbers = static::query()
            ->where('parent_id', $parentId)
            ->pluck('option_number')
            ->filter(fn (?string $number) => $number !== null && ctype_digit($number));

        return (string) ($numbers->isEmpty() ? 1 : $numbers->map(fn (string $number) => (int) $number)->max() + 1);
    }

    public function descendantIds(): array
    {
        $ids = [];
        $queue = [$this->id];

        while ($queue !== []) {
            $children = static::query()->whereIn('parent_id', $queue)->pluck('id')->all();
            $ids = [...$ids, ...$children];
            $queue = $children;
        }

        return $ids;
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }
}
