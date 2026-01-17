<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Character extends Model
{
    protected $fillable = [
        'game_id',
        'name',
        'is_player',
        'info',
        'personality',
        'traits',
        'trauma',
        'hobbies',
        'routines',
        'job',
        'skills',
        'goals',
        'secrets',
        'limits',
        'intentions',
        'temperature',
    ];

    protected $casts = [
        'is_player' => 'boolean',
        'temperature' => 'float',
    ];

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    public function characterMemories(): HasMany
    {
        return $this->hasMany(CharacterMemory::class);
    }

    public function memories(): HasManyThrough
    {
        return $this->hasManyThrough(Memory::class, CharacterMemory::class, 'character_id', 'id', 'id', 'memory_id');
    }

    public function characterItems(): HasMany
    {
        return $this->hasMany(CharacterItem::class);
    }

    public function items(): BelongsToMany
    {
        return $this->belongsToMany(Item::class, 'character_items')
            ->withPivot(['quantity', 'equipped'])
            ->withTimestamps();
    }
}
