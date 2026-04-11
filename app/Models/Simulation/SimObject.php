<?php

namespace App\Models\Simulation;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SimObject extends Model
{
    protected $table = 'sim_objects';

    protected $guarded = ['id'];

    protected $casts = [
        'weight' => 'integer',
        'size' => 'integer',
        'integrity' => 'integer',
        'value' => 'integer',
        'quantity' => 'integer',
        'price' => 'integer',
        'for_sale' => 'boolean',
        'x' => 'integer',
        'y' => 'integer',
        'affordances' => 'array',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(SimNpc::class, 'owner_npc_id');
    }

    public function place(): BelongsTo
    {
        return $this->belongsTo(SimPlace::class, 'place_id');
    }
}
