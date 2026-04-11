<?php

namespace App\Models\Simulation;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SimNpc extends Model
{
    protected $table = 'sim_npcs';

    protected $guarded = ['id'];

    protected $casts = [
        'age' => 'integer',
        'wealth' => 'integer',
        'openness' => 'integer',
        'conscientiousness' => 'integer',
        'extraversion' => 'integer',
        'agreeableness' => 'integer',
        'neuroticism' => 'integer',
        'str' => 'integer',
        'dex' => 'integer',
        'con' => 'integer',
        'int' => 'integer',
        'hp' => 'integer',
        'max_hp' => 'integer',
        'hunger' => 'integer',
        'thirst' => 'integer',
        'rest' => 'integer',
        'hygiene' => 'integer',
        'safety' => 'integer',
        'social_need' => 'integer',
        'purpose' => 'integer',
        'x' => 'integer',
        'y' => 'integer',
        'last_work_tick' => 'integer',
        'starving_since_tick' => 'integer',
    ];

    public function place(): BelongsTo
    {
        return $this->belongsTo(SimPlace::class, 'place_id');
    }

    public function inventory(): HasMany
    {
        return $this->hasMany(SimObject::class, 'owner_npc_id');
    }

    public function actions(): HasMany
    {
        return $this->hasMany(SimAction::class, 'source_npc_id')->latest('tick');
    }

    public function needs(): array
    {
        return [
            'hunger' => $this->hunger,
            'thirst' => $this->thirst,
            'rest' => $this->rest,
            'hygiene' => $this->hygiene,
            'safety' => $this->safety,
            'social_need' => $this->social_need,
            'purpose' => $this->purpose,
        ];
    }

    public function mostUrgentNeed(): string
    {
        return array_search(min($this->needs()), $this->needs());
    }
}
