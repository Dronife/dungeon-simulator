<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CharacterRelationship extends Model
{
    protected $fillable = [
        'trust',
        'affection',
        'respect',
        'fear',
        'loyalty',
        'debt',
        'rivalry',
        'attraction',
    ];

    protected $casts = [
        'trust' => 'float',
        'affection' => 'float',
        'respect' => 'float',
        'fear' => 'float',
        'loyalty' => 'float',
        'debt' => 'float',
        'rivalry' => 'float',
        'attraction' => 'float',
    ];

    public function map(): HasOne
    {
        return $this->hasOne(CharacterRelationshipMap::class);
    }

    /**
     * @return string[]
     */
    public function getFillable(): array
    {
        return $this->fillable;
    }
}
