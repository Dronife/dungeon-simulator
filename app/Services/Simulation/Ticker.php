<?php

namespace App\Services\Simulation;

use App\Models\Simulation\SimNpc;
use App\Models\Simulation\SimPlace;
use App\Models\Simulation\SimRelationship;
use App\Models\Simulation\SimState;
use App\Services\Simulation\Handler\BehaviorHandler;
use App\Services\Simulation\Handler\DeathHandler;
use App\Services\Simulation\Handler\SurvivalHandler;
use App\Services\Simulation\Handler\WorkHandler;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class Ticker
{
    public const DECAY = [
        'hunger'      => 2,
        'thirst'      => 2,
        'rest'        => 1,
        'hygiene'     => 1,
        'safety'      => 1,
        'social_need' => 1,
        'purpose'     => 2,
    ];

    private const TIMES = [
        'dawn', 'morning', 'noon', 'afternoon', 'dusk', 'evening', 'night', 'midnight',
    ];

    private const GRID = 60;
    private const CRITICAL_THRESHOLD = 35;
    private const PURPOSE_PRAY_THRESHOLD = 40;
    private const HYGIENE_MEND_THRESHOLD = 40;

    /** @var array<int, array<string, mixed>> */
    public array $actionBatch = [];

    /** @var Collection<int, SimNpc> */
    public Collection $npcById;

    /** @var Collection<int, SimPlace> */
    public Collection $placesById;

    /** @var Collection<int, SimPlace> */
    public Collection $places;

    public string $timeOfDay = 'morning';

    /** @var array<int, array<int, SimRelationship>> */
    private array $relationships = [];

    private readonly SurvivalHandler $survival;
    private readonly WorkHandler $work;
    private readonly BehaviorHandler $behavior;
    private readonly DeathHandler $death;

    public function __construct()
    {
        $this->survival = new SurvivalHandler($this);
        $this->work = new WorkHandler($this);
        $this->behavior = new BehaviorHandler($this);
        $this->death = new DeathHandler($this);
    }

    public function survival(): SurvivalHandler
    {
        return $this->survival;
    }

    public function behavior(): BehaviorHandler
    {
        return $this->behavior;
    }

    public function tick(): array
    {
        $state = SimState::current();
        $state->tick++;
        $state->time_of_day = self::TIMES[$state->tick % count(self::TIMES)];
        $state->save();

        $this->timeOfDay = $state->time_of_day;
        $this->actionBatch = [];
        $this->npcById = SimNpc::all()->keyBy('id');
        $this->places = SimPlace::all();
        $this->placesById = $this->places->keyBy('id');
        $this->loadRelationships();

        DB::transaction(function () use ($state) {
            foreach ($this->npcById as $npc) {
                $this->decayNeeds($npc);
                $this->actFor($npc, $state->tick);
            }
            $this->decayRelationships();
            foreach ($this->npcById as $npc) {
                $npc->save();
            }
            $this->saveRelationships();
            foreach (array_chunk($this->actionBatch, 500) as $chunk) {
                DB::table('sim_actions')->insert($chunk);
            }
        });

        return ['tick' => $state->tick, 'logged' => count($this->actionBatch)];
    }

    private function decayNeeds(SimNpc $npc): void
    {
        foreach (self::DECAY as $need => $amount) {
            $npc->{$need} = max(0, $npc->{$need} - $amount);
        }

        $lowest = PHP_INT_MAX;
        $lowestKey = null;
        foreach (self::DECAY as $k => $_) {
            if ($npc->{$k} < $lowest) {
                $lowest = $npc->{$k};
                $lowestKey = $k;
            }
        }

        if ($lowest < 30) {
            $npc->mood = match ($lowestKey) {
                'hunger', 'thirst', 'rest' => 'tired',
                'safety' => 'afraid',
                'social_need' => 'lonely',
                'purpose' => 'bored',
                default => 'anxious',
            };
        } elseif ($lowest > 70) {
            $npc->mood = 'content';
        }
    }

    private function actFor(SimNpc $npc, int $tick): void
    {
        if ($npc->current_action === 'dead') {
            return;
        }

        if ($this->death->checkDeath($npc, $tick)) {
            return;
        }

        // Critical survival — always takes priority
        $survivalNeeds = [
            'thirst' => $npc->thirst,
            'hunger' => $npc->hunger,
            'rest'   => $npc->rest,
        ];
        asort($survivalNeeds);
        $mostUrgent = array_key_first($survivalNeeds);
        $mostUrgentValue = $survivalNeeds[$mostUrgent];

        if ($mostUrgentValue < self::CRITICAL_THRESHOLD) {
            match ($mostUrgent) {
                'thirst' => $this->survival->tryDrink($npc, $tick),
                'hunger' => $this->survival->tryEat($npc, $tick),
                'rest'   => $this->survival->trySleep($npc, $tick),
            };
            return;
        }

        // Personality-driven behaviors
        if ($this->behavior->shouldShirkWork($npc)) {
            $this->behavior->selfCare($npc, $tick);
            return;
        }

        if ($this->behavior->shouldHelpOthers($npc) && $this->behavior->tryHelp($npc, $tick)) {
            return;
        }

        if ($npc->purpose < self::PURPOSE_PRAY_THRESHOLD && $this->behavior->pray($npc, $tick)) {
            return;
        }

        if ($npc->hygiene < self::HYGIENE_MEND_THRESHOLD && $this->behavior->mend($npc, $tick)) {
            return;
        }

        if ($this->behavior->trySocialize($npc, $tick)) {
            return;
        }

        if ($this->behavior->tryShopForNeeds($npc, $tick)) {
            return;
        }

        // Night: no work — rest or idle
        if ($this->isNightTime()) {
            if ($npc->rest < 70) {
                $this->survival->trySleep($npc, $tick);
                return;
            }
            $this->idle($npc, $tick);
            return;
        }

        // Archetype work loop
        match ($npc->archetype) {
            'household_producer' => $this->work->householdLoop($npc, $tick),
            'market_craftsman'   => $this->work->produceLoop($npc, $tick),
            'service_provider'   => $this->work->produceLoop($npc, $tick),
            'rent_extractor'     => $this->work->rentLoop($npc, $tick),
            'dependent'          => $this->work->dependentLoop($npc, $tick),
            default              => $this->idle($npc, $tick),
        };
    }

    // ---------------------------------------------------------------
    //  Time
    // ---------------------------------------------------------------

    public function isNightTime(): bool
    {
        return in_array($this->timeOfDay, ['night', 'midnight'], true);
    }

    // ---------------------------------------------------------------
    //  Relationships
    // ---------------------------------------------------------------

    private function loadRelationships(): void
    {
        $this->relationships = [];
        foreach (SimRelationship::all() as $rel) {
            $this->relationships[$rel->from_npc_id][$rel->to_npc_id] = $rel;
        }
    }

    private function decayRelationships(): void
    {
        foreach ($this->relationships as $fromRelations) {
            foreach ($fromRelations as $rel) {
                if ($rel->trust > 0) {
                    $rel->trust--;
                } elseif ($rel->trust < 0) {
                    $rel->trust++;
                }

                if ($rel->fear > 0) {
                    $rel->fear--;
                }
            }
        }
    }

    private function saveRelationships(): void
    {
        foreach ($this->relationships as $fromRelations) {
            foreach ($fromRelations as $rel) {
                if ($rel->exists && $rel->trust === 0 && $rel->fear === 0) {
                    $rel->delete();
                    continue;
                }
                if (!$rel->exists || $rel->isDirty()) {
                    $rel->save();
                }
            }
        }
    }

    public function getRelationship(int $fromId, int $toId): ?SimRelationship
    {
        return $this->relationships[$fromId][$toId] ?? null;
    }

    public function modifyRelationship(
        int $fromId,
        int $toId,
        int $trustDelta,
        int $fearDelta,
        string $event,
        int $tick,
    ): void {
        $rel = $this->getRelationship($fromId, $toId);

        if (!$rel) {
            $rel = new SimRelationship([
                'from_npc_id' => $fromId,
                'to_npc_id'   => $toId,
                'trust'       => 0,
                'fear'        => 0,
            ]);
            $this->relationships[$fromId][$toId] = $rel;
        }

        $rel->trust = max(-100, min(100, $rel->trust + $trustDelta));
        $rel->fear = max(0, min(100, $rel->fear + $fearDelta));
        $rel->last_event = $event;
        $rel->last_event_tick = $tick;
    }

    // ---------------------------------------------------------------
    //  Shared primitives — used by all handlers
    // ---------------------------------------------------------------

    public function log(
        SimNpc $npc,
        int $tick,
        string $type,
        string $verb,
        ?int $objectId,
        ?int $placeId,
        string $description,
        ?int $targetNpcId = null,
    ): void {
        $now = now();
        $this->actionBatch[] = [
            'type'             => $type,
            'verb'             => $verb,
            'source_npc_id'    => $npc->id,
            'target_npc_id'    => $targetNpcId,
            'target_object_id' => $objectId,
            'place_id'         => $placeId,
            'tick'             => $tick,
            'duration'         => 1,
            'difficulty'       => 0,
            'outcome'          => 'success',
            'status'           => 'done',
            'description'      => $description,
            'created_at'       => $now,
            'updated_at'       => $now,
        ];
    }

    public function walkTowardsPlace(SimNpc $npc, SimPlace $place, int $tick, string $label): void
    {
        $tx = $place->x + intdiv($place->width, 2);
        $ty = $place->y + intdiv($place->height, 2);
        $this->stepTowards($npc, $tx, $ty);
        $npc->place_id = $this->placeAt($npc->x, $npc->y);

        $npc->current_action = 'walking';
        $npc->current_action_target = $place->name;
        $arrived = $npc->place_id === $place->id;
        $this->log(
            $npc, $tick, 'travel', 'walk_to', null, $place->id,
            ($arrived ? "arrived at {$place->name}" : "heading to {$place->name}") . " ({$label})"
        );
    }

    public function idle(SimNpc $npc, int $tick): void
    {
        $npc->x = max(0, min(self::GRID - 1, $npc->x + random_int(-1, 1)));
        $npc->y = max(0, min(self::GRID - 1, $npc->y + random_int(-1, 1)));
        $npc->place_id = $this->placeAt($npc->x, $npc->y);
        $npc->current_action = 'idle';
        $npc->current_action_target = null;
        $this->log($npc, $tick, 'idle', 'walk_to', null, $npc->place_id, 'wandered aimlessly');
    }

    public function placeAt(int $x, int $y): ?int
    {
        foreach ($this->places as $p) {
            if ($x >= $p->x && $x < $p->x + $p->width
                && $y >= $p->y && $y < $p->y + $p->height) {
                return $p->id;
            }
        }
        return null;
    }

    public function step(int $from, int $to): int
    {
        return $from === $to ? 0 : ($from < $to ? 1 : -1);
    }

    private function stepTowards(SimNpc $npc, int $tx, int $ty): void
    {
        $npc->x = max(0, min(self::GRID - 1, $npc->x + $this->step($npc->x, $tx)));
        $npc->y = max(0, min(self::GRID - 1, $npc->y + $this->step($npc->y, $ty)));
    }
}
