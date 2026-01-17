<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CharacterItem extends Model
{
    protected $fillable = [
        'character_id',
        'item_id',
        'quantity',
        'equipped',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'equipped' => 'boolean',
    ];

    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}
