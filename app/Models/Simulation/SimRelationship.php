<?php

namespace App\Models\Simulation;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SimRelationship extends Model
{
    protected $table = 'sim_relationships';

    protected $guarded = ['id'];

    protected $casts = [
        'trust' => 'integer',
        'fear' => 'integer',
        'last_event_tick' => 'integer',
    ];

    public function from(): BelongsTo
    {
        return $this->belongsTo(SimNpc::class, 'from_npc_id');
    }

    public function to(): BelongsTo
    {
        return $this->belongsTo(SimNpc::class, 'to_npc_id');
    }
}
