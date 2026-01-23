<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Game extends Model
{
    protected $fillable = [
//        'name',
//        'status',
//        'current_tick',
//        'global_rules',
    ];

    protected $casts = [
//        'global_rules' => 'array',
//        'current_tick' => 'integer',
    ];

    public function characters(): HasMany
    {
        return $this->hasMany(Character::class);
    }

    public function world(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(World::class);
    }

    public function dmMemories(): HasMany
    {
        return $this->hasMany(DmMemory::class);
    }

    public function memories(): HasManyThrough
    {
        return $this->hasManyThrough(Memory::class, DmMemory::class, 'game_id', 'id', 'id', 'memory_id');
    }
}
