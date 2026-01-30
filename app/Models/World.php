<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class World extends Model
{
    protected $fillable = [
        'game_id',
        'time',
        'universe_rules',
        'environment_description',
    ];

    protected $casts = [
        'lore' => 'array',
    ];

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }
}
