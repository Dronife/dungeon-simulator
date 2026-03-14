<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameChat extends Model
{
    protected $fillable = [
        'game_id',
        'type',
        'content',
    ];

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }
}
