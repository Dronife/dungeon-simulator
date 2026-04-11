<?php

namespace App\Models\Simulation;

use Illuminate\Database\Eloquent\Model;

class SimState extends Model
{
    protected $table = 'sim_state';

    protected $guarded = ['id'];

    protected $casts = [
        'tick' => 'integer',
    ];

    public static function current(): self
    {
        return self::firstOrCreate([], ['tick' => 0, 'time_of_day' => 'morning', 'weather' => 'clear']);
    }
}
