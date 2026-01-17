<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CharacterMemory extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'memory_id',
        'character_id',
    ];

    public function memory(): BelongsTo
    {
        return $this->belongsTo(Memory::class);
    }

    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }
}
