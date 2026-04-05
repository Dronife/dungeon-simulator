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
        'age',
        'gender',
        'race',
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
        'talking_mannerism',
        'talking_style',
        'temperature',
        'str',
        'dex',
        'con',
        'int',
        'wis',
        'cha',
        'hp',
        'max_hp',
        'trauma_severity',
        'goal_severity',
        'intention_severity',
        'personality_severity',
        'chaotic_temperature',
        'positive_temperature',
    ];

    protected $casts = [
        'is_player' => 'boolean',
        'age' => 'integer',
        'temperature' => 'float',
        'str' => 'integer',
        'dex' => 'integer',
        'con' => 'integer',
        'int' => 'integer',
        'wis' => 'integer',
        'cha' => 'integer',
        'hp' => 'integer',
        'max_hp' => 'integer',
        'trauma_severity' => 'integer',
        'goal_severity' => 'integer',
        'intention_severity' => 'integer',
        'personality_severity' => 'integer',
        'chaotic_temperature' => 'float',
        'positive_temperature' => 'float',
    ];

    /**
     * Calculate modifier from stat value.
     * D&D formula: (stat - 10) / 2, rounded down
     */
    public function modifier(int $stat): int
    {
        return (int) floor(($stat - 10) / 2);
    }

    public function strMod(): int { return $this->modifier($this->str); }
    public function dexMod(): int { return $this->modifier($this->dex); }
    public function conMod(): int { return $this->modifier($this->con); }
    public function intMod(): int { return $this->modifier($this->int); }
    public function wisMod(): int { return $this->modifier($this->wis); }
    public function chaMod(): int { return $this->modifier($this->cha); }

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    public function characterMemories(): HasMany
    {
        return $this->hasMany(CharacterMemory::class);
    }

    public function memories(): BelongsToMany
    {
        return $this->belongsToMany(Memory::class, 'character_memories')
            ->where('active', true);
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

    public function relationshipMaps(): HasMany
    {
        return $this->hasMany(CharacterRelationshipMap::class, 'from_character_id');
    }

    public function createRelationshipWith(Character $target, array $axes): CharacterRelationship
    {
        $relationship = CharacterRelationship::create($axes);

        CharacterRelationshipMap::create([
            'from_character_id' => $this->id,
            'to_character_id' => $target->id,
            'character_relationship_id' => $relationship->id,
        ]);

        return $relationship;
    }

    public function relationshipWith(Character $target): ?CharacterRelationship
    {
        $map = $this->relationshipMaps()
            ->where('to_character_id', $target->id)
            ->first();

        return $map?->relationship;
    }
}
