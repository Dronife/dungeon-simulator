<?php

namespace App\Services\Simulation\Handler;

use App\Models\Simulation\SimNpc;
use App\Services\Simulation\Ticker;

class JusticeHandler
{
    private const WITNESS_RADIUS = 6;
    private const FINE_AMOUNT = 10;
    private const SAFETY_PENALTY = 25;
    private const GUARD_TRUST_GAIN = 5;

    /** @var array<int, array{thief_id: int, victim_id: int, tick: int}> */
    private array $pendingCrimes = [];

    public function __construct(private readonly Ticker $ticker) {}

    public function resetTick(): void
    {
        $this->pendingCrimes = [];
    }

    /**
     * Called from BehaviorHandler after a theft occurs.
     * Records the crime so guards can react this tick.
     */
    public function reportCrime(int $thiefId, int $victimId, int $tick): void
    {
        $this->pendingCrimes[] = [
            'thief_id'  => $thiefId,
            'victim_id' => $victimId,
            'tick'      => $tick,
        ];
    }

    /**
     * Called from Ticker after all NPCs have acted.
     * Guards who are near a crime scene respond.
     */
    public function processJustice(int $tick): void
    {
        if (count($this->pendingCrimes) === 0) {
            return;
        }

        $guards = $this->ticker->npcById->filter(
            fn (SimNpc $npc) => $npc->current_action !== 'dead'
                && in_array($npc->profession, ['guard', 'soldier', 'constable'], true)
        );

        if ($guards->isEmpty()) {
            return;
        }

        $handledThieves = [];

        foreach ($this->pendingCrimes as $crime) {
            if (isset($handledThieves[$crime['thief_id']])) {
                continue;
            }

            $thief = $this->ticker->npcById[$crime['thief_id']] ?? null;
            $victim = $this->ticker->npcById[$crime['victim_id']] ?? null;
            if ($thief === null || $thief->current_action === 'dead') {
                continue;
            }

            $nearestGuard = $guards
                ->filter(fn (SimNpc $g) => $this->ticker->npcDistance($g, $thief) <= self::WITNESS_RADIUS)
                ->sortBy(fn (SimNpc $g) => $this->ticker->npcDistance($g, $thief))
                ->first();

            if ($nearestGuard === null) {
                continue;
            }

            $this->punishThief($nearestGuard, $thief, $victim, $tick);
            $handledThieves[$crime['thief_id']] = true;
        }
    }

    private function punishThief(SimNpc $guard, SimNpc $thief, ?SimNpc $victim, int $tick): void
    {
        $fine = min(self::FINE_AMOUNT, $thief->wealth);
        $thief->wealth -= $fine;
        $thief->safety = max(0, $thief->safety - self::SAFETY_PENALTY);

        $guard->purpose = min(100, $guard->purpose + 15);
        $guard->current_action = 'arresting';
        $guard->current_action_target = $thief->name;

        $this->ticker->log(
            $guard, $tick, 'combat', 'arrest', null, $guard->place_id,
            "caught {$thief->name} stealing — fined {$fine}c"
        );

        $this->ticker->log(
            $thief, $tick, 'combat', 'flee', null, $thief->place_id,
            "fined {$fine}c by {$guard->name} for theft"
        );

        // Return fine to victim if present, otherwise guard keeps it
        if ($victim !== null && $victim->current_action !== 'dead' && $fine > 0) {
            $victim->wealth += $fine;
            $this->ticker->log(
                $victim, $tick, 'trade', 'take', null, $victim->place_id,
                "received {$fine}c restitution from {$guard->name}"
            );
        } else {
            $guard->wealth += $fine;
        }

        // Relationship effects
        $this->ticker->modifyRelationship($thief->id, $guard->id, -10, 20, 'arrested', $tick);

        if ($victim !== null && $victim->current_action !== 'dead') {
            $this->ticker->modifyRelationship(
                $victim->id, $guard->id,
                self::GUARD_TRUST_GAIN, 0,
                'justice_served', $tick,
            );
        }
    }
}
