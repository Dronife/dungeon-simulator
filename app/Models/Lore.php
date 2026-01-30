<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Lore extends Model
{
    protected $table = 'lore';

    protected $fillable = [
        'world_id',
        'name',
        'type',
        'description',
        'occurrence',
        'know_how',
        'reason',
        'image_prompt',
    ];

    public function world(): BelongsTo
    {
        return $this->belongsTo(World::class);
    }
}
