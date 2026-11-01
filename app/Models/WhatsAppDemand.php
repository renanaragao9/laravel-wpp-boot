<?php

namespace App\Models;

use App\Enums\DemandStatus;
use App\Enums\WhatsAppDriver;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class WhatsAppDemand extends BaseModel
{
    protected $table = 'whatsapp_demands';

    protected $fillable = [
        'driver',
        'phone',
        'push_name',
        'sector',
        'title',
        'status',
        'notes',
        'position',
        'conversation_id',
        'menu_node_id',
        'assigned_user_id',
    ];

    protected function casts(): array
    {
        return [
            'driver' => WhatsAppDriver::class,
            'status' => DemandStatus::class,
            'position' => 'integer',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(WhatsAppConversation::class, 'conversation_id');
    }

    public function menuNode(): BelongsTo
    {
        return $this->belongsTo(WhatsAppMenuNode::class, 'menu_node_id');
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function chatSession(): HasOne
    {
        return $this->hasOne(ChatSession::class, 'identifier', 'phone');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(WhatsAppDemandMessage::class, 'demand_id');
    }
}
