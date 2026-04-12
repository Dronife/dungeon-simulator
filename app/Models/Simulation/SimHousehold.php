<?php

namespace App\Models\Simulation;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SimHousehold extends Model
{
    protected $table = 'sim_households';

    protected $guarded = ['id'];

    public function homePlace(): BelongsTo
    {
        return $this->belongsTo(SimPlace::class, 'home_place_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(SimNpc::class, 'household_id');
    }
}
