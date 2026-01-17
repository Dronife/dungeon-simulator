<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Item extends Model
{
    protected $fillable = [
        'name',
        'hp',
        'damage',
        'armor',
        'speed',
        'special_ability',
    ];

    protected $casts = [
        'hp' => 'integer',
        'damage' => 'integer',
        'armor' => 'integer',
        'speed' => 'integer',
    ];

    public function characterItems(): HasMany
    {
        return $this->hasMany(CharacterItem::class);
    }

    public function characters(): BelongsToMany
    {
        return $this->belongsToMany(Character::class, 'character_items')
            ->withPivot(['quantity', 'equipped'])
            ->withTimestamps();
    }
}
