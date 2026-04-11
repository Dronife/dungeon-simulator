<?php

namespace App\Models\Simulation;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SimAction extends Model
{
    protected $table = 'sim_actions';

    protected $guarded = ['id'];

    protected $casts = [
        'tick' => 'integer',
        'duration' => 'integer',
        'difficulty' => 'integer',
    ];

    public function source(): BelongsTo
    {
        return $this->belongsTo(SimNpc::class, 'source_npc_id');
    }

    public function targetNpc(): BelongsTo
    {
        return $this->belongsTo(SimNpc::class, 'target_npc_id');
    }

    public function targetObject(): BelongsTo
    {
        return $this->belongsTo(SimObject::class, 'target_object_id');
    }

    public function place(): BelongsTo
    {
        return $this->belongsTo(SimPlace::class, 'place_id');
    }
}
