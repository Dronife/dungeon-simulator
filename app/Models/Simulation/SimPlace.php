<?php

namespace App\Models\Simulation;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SimPlace extends Model
{
    protected $table = 'sim_places';

    protected $guarded = ['id'];

    protected $casts = [
        'x' => 'integer',
        'y' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
        'danger_level' => 'integer',
        'population' => 'integer',
        'prosperity' => 'integer',
    ];

    public function npcs(): HasMany
    {
        return $this->hasMany(SimNpc::class, 'place_id');
    }

    public function objects(): HasMany
    {
        return $this->hasMany(SimObject::class, 'place_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(SimPlace::class, 'parent_id');
    }
}
