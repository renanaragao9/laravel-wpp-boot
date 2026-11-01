<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class ChatSession extends BaseModel
{
    protected $fillable = [
        'token',
        'identifier',
        'name',
        'email',
        'phone',
        'last_seen_at',
    ];

    protected $hidden = [
        'token',
    ];

    protected function casts(): array
    {
        return [
            'last_seen_at' => 'datetime',
        ];
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function demands(): HasMany
    {
        return $this->hasMany(WhatsAppDemand::class, 'phone', 'identifier');
    }
}
