<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Memory extends Model
{
    protected $fillable = [
        'memory',
        'type',
    ];

    public function characterMemories(): HasMany
    {
        return $this->hasMany(CharacterMemory::class);
    }

    public function dmMemories(): HasMany
    {
        return $this->hasMany(DmMemory::class);
    }
}
