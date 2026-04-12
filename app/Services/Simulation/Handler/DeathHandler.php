<?php

namespace App\Services\Simulation\Handler;

use App\Models\Simulation\SimNpc;
use App\Models\Simulation\SimObject;
use App\Services\Simulation\Ticker;

class DeathHandler
{
    private const DEATH_GRACE_TICKS = 5;

    public function __construct(private readonly Ticker $ticker) {}

    public function checkDeath(SimNpc $npc, int $tick): bool
    {
        $worst = min($npc->hunger, $npc->thirst, $npc->rest);

        if ($worst > 0) {
            $npc->starving_since_tick = null;
            return false;
        }

        if ($npc->starving_since_tick === null) {
            $npc->starving_since_tick = $tick;
            return false;
        }

        if (($tick - $npc->starving_since_tick) < self::DEATH_GRACE_TICKS) {
            return false;
        }

        $this->killNpc($npc, $tick);
        return true;
    }

    private function killNpc(SimNpc $npc, int $tick): void
    {
        $cause = match (true) {
            $npc->hunger <= 0 => 'starvation',
            $npc->thirst <= 0 => 'thirst',
            $npc->rest <= 0   => 'exhaustion',
            default           => 'neglect',
        };

        SimObject::where('owner_npc_id', $npc->id)->update([
            'owner_npc_id' => null,
            'place_id'     => $npc->place_id,
            'x'            => $npc->x,
            'y'            => $npc->y,
            'for_sale'     => false,
        ]);

        $npc->current_action = 'dead';
        $npc->current_action_target = $cause;
        $this->ticker->log(
            $npc, $tick, 'idle', 'rest', null, $npc->place_id,
            "died of {$cause}"
        );
    }
}
