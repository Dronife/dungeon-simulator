<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorldHook extends Model
{
    protected $fillable = [
        'world_id',
        'name',
        'type',
        'brief',
        'situation',
        'stakes',
        'clue',
        'image_prompt',
        'image_cell_index',
        'image_path',
    ];

    public function world(): BelongsTo
    {
        return $this->belongsTo(World::class);
    }
}
