<?php

namespace App\Services\Simulation;

use App\Models\Simulation\SimHousehold;
use App\Models\Simulation\SimNpc;
use App\Models\Simulation\SimPlace;
use App\Models\Simulation\SimRelationship;
use App\Models\Simulation\SimState;
use App\Services\Simulation\Handler\BehaviorHandler;
use App\Services\Simulation\Handler\DeathHandler;
use App\Services\Simulation\Handler\EventHandler;
use App\Services\Simulation\Handler\JusticeHandler;
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
    private const GOAL_PURPOSE_ON_COMPLETE = 30;
    private const GOAL_ASSIGN_THRESHOLD = 45;
    private const HYGIENE_MEND_THRESHOLD = 40;

    private const ILLNESS_CHANCE_PER_TICK = 2;
    private const ILLNESS_HYGIENE_THRESHOLD = 30;
    private const ILLNESS_LOW_HYGIENE_CHANCE = 12;
    private const ILLNESS_DECAY_MULTIPLIER = 2;
    private const ILLNESS_NATURAL_RECOVERY_TICKS = 15;

    /** Passive need bonuses applied when an NPC acts at a specialized place. */
    private const PLACE_BONUSES = [
        'tavern'      => ['social_need' => 8],
        'town_square' => ['social_need' => 5],
        'barracks'    => ['safety' => 6],
    ];

    /** @var array<int, array<string, mixed>> */
    public array $actionBatch = [];

    /** @var Collection<int, SimNpc> */
    public Collection $npcById;

    /** @var Collection<int, SimPlace> */
    public Collection $placesById;

    /** @var Collection<int, SimPlace> */
    public Collection $places;

    public string $timeOfDay = 'morning';

    /** @var Collection<int, SimHousehold> */
    public Collection $householdsById;

    /** @var array<int, array<int, SimRelationship>> */
    private array $relationships = [];

    private readonly SurvivalHandler $survival;
    private readonly WorkHandler $work;
    private readonly BehaviorHandler $behavior;
    private readonly DeathHandler $death;
    private readonly JusticeHandler $justice;
    private readonly EventHandler $events;

    public function __construct()
    {
        $this->survival = new SurvivalHandler($this);
        $this->work = new WorkHandler($this);
        $this->behavior = new BehaviorHandler($this);
        $this->death = new DeathHandler($this);
        $this->justice = new JusticeHandler($this);
        $this->events = new EventHandler($this);
    }

    public function survival(): SurvivalHandler
    {
        return $this->survival;
    }

    public function behavior(): BehaviorHandler
    {
        return $this->behavior;
    }

    public function justice(): JusticeHandler
    {
        return $this->justice;
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
        $this->householdsById = SimHousehold::all()->keyBy('id');
        $this->loadRelationships();

        DB::transaction(function () use ($state) {
            $this->justice->resetTick();
            foreach ($this->npcById as $npc) {
                $this->processIllness($npc, $state->tick);
                $this->decayNeeds($npc);
                $this->processGoal($npc, $state->tick);
                $this->actFor($npc, $state->tick);
                if ($npc->current_action !== 'dead' && $npc->current_action !== 'walking') {
                    $this->applyPlaceBonus($npc);
                }
            }
            $this->justice->processJustice($state->tick);
            $this->events->maybeTriggerEvent($state->tick);
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
        $sick = $npc->illness !== null;
        $multiplier = $sick ? self::ILLNESS_DECAY_MULTIPLIER : 1;

        foreach (self::DECAY as $need => $amount) {
            $npc->{$need} = max(0, $npc->{$need} - ($amount * $multiplier));
        }

        $lowest = PHP_INT_MAX;
        $lowestKey = null;
        foreach (self::DECAY as $k => $_) {
            if ($npc->{$k} < $lowest) {
                $lowest = $npc->{$k};
                $lowestKey = $k;
            }
        }

        if ($sick) {
            $npc->mood = 'sick';
        } elseif ($lowest < 30) {
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

        // Sick NPC tries to find medicine
        if ($npc->illness !== null && $this->behavior->trySeekHealing($npc, $tick)) {
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

    private function processGoal(SimNpc $npc, int $tick): void
    {
        if ($npc->current_action === 'dead') {
            return;
        }

        // Check completion of current goal
        if ($npc->goal_type !== null) {
            $completed = match ($npc->goal_type) {
                'save_wealth' => $npc->wealth >= $npc->goal_target,
                'make_friend' => $this->hasPositiveRelationship($npc->id, $npc->goal_target),
                'sell_goods'  => $npc->goal_progress >= $npc->goal_target,
                'seek_safety' => $npc->safety >= 70,
                default       => false,
            };

            if ($completed) {
                $label = match ($npc->goal_type) {
                    'save_wealth' => "saved {$npc->goal_target}c",
                    'make_friend' => 'made a new friend',
                    'sell_goods'  => "sold {$npc->goal_target} goods",
                    'seek_safety' => 'feels safe again',
                    default       => 'achieved a goal',
                };
                $npc->purpose = min(100, $npc->purpose + self::GOAL_PURPOSE_ON_COMPLETE);
                $npc->goal_type = null;
                $npc->goal_target = 0;
                $npc->goal_progress = 0;
                $this->log($npc, $tick, 'social', 'celebrate', null, $npc->place_id, "achieved goal: {$label}");
            }
            return;
        }

        // Assign a new goal when purpose is low
        if ($npc->purpose >= self::GOAL_ASSIGN_THRESHOLD) {
            return;
        }

        if ($npc->safety < 40) {
            $npc->goal_type = 'seek_safety';
            $npc->goal_target = 70;
        } elseif ($npc->wealth < 10 && $npc->conscientiousness >= 5) {
            $npc->goal_type = 'save_wealth';
            $npc->goal_target = $npc->wealth + random_int(15, 30);
        } elseif ($npc->social_need < 50 && $npc->agreeableness >= 5) {
            $targetNpc = $this->npcById
                ->filter(fn (SimNpc $o) => $o->id !== $npc->id && $o->current_action !== 'dead')
                ->random();
            if ($targetNpc !== null) {
                $npc->goal_type = 'make_friend';
                $npc->goal_target = $targetNpc->id;
            }
        } elseif (in_array($npc->archetype, ['market_craftsman', 'service_provider', 'household_producer'], true)) {
            $npc->goal_type = 'sell_goods';
            $npc->goal_target = random_int(2, 5);
            $npc->goal_progress = 0;
        } else {
            return;
        }

        $label = match ($npc->goal_type) {
            'save_wealth' => "save {$npc->goal_target}c",
            'make_friend' => 'befriend ' . ($this->npcById[$npc->goal_target]->name ?? 'someone'),
            'sell_goods'  => "sell {$npc->goal_target} goods",
            'seek_safety' => 'feel safe',
            default       => 'something',
        };
        $this->log($npc, $tick, 'social', 'observe', null, $npc->place_id, "set goal: {$label}");
    }

    private function hasPositiveRelationship(int $fromId, int $toId): bool
    {
        $rel = $this->getRelationship($fromId, $toId);
        return $rel !== null && $rel->trust >= 10;
    }

    private function processIllness(SimNpc $npc, int $tick): void
    {
        if ($npc->current_action === 'dead') {
            return;
        }

        // Natural recovery
        if ($npc->illness !== null) {
            $sickDuration = $tick - ($npc->illness_since_tick ?? $tick);
            if ($sickDuration >= self::ILLNESS_NATURAL_RECOVERY_TICKS) {
                $npc->illness = null;
                $npc->illness_since_tick = null;
                $this->log($npc, $tick, 'idle', 'rest', null, $npc->place_id, 'recovered from illness');
            }
            return;
        }

        // Chance to get sick — higher when hygiene is low
        $chance = self::ILLNESS_CHANCE_PER_TICK;
        if ($npc->hygiene < self::ILLNESS_HYGIENE_THRESHOLD) {
            $chance = self::ILLNESS_LOW_HYGIENE_CHANCE;
        }

        if (random_int(1, 100) <= $chance) {
            $illnesses = ['fever', 'cough', 'gut_rot', 'chills'];
            $npc->illness = $illnesses[array_rand($illnesses)];
            $npc->illness_since_tick = $tick;
            $npc->rest = max(0, $npc->rest - 15);

            $label = str_replace('_', ' ', $npc->illness);
            $this->log($npc, $tick, 'idle', 'rest', null, $npc->place_id, "fell ill with {$label}");
        }
    }

    public function applyPlaceBonus(SimNpc $npc): void
    {
        if ($npc->place_id === null) {
            return;
        }
        $place = $this->placesById[$npc->place_id] ?? null;
        if ($place === null) {
            return;
        }
        $bonuses = self::PLACE_BONUSES[$place->subtype] ?? null;
        if ($bonuses === null) {
            return;
        }
        foreach ($bonuses as $need => $amount) {
            $npc->{$need} = min(100, $npc->{$need} + $amount);
        }
    }

    /**
     * @return int[]
     */
    public function getHouseholdMemberIds(SimNpc $npc): array
    {
        if ($npc->household_id === null) {
            return [];
        }
        return $this->npcById
            ->filter(fn (SimNpc $other) => $other->household_id === $npc->household_id && $other->id !== $npc->id)
            ->keys()
            ->all();
    }

    public function distance(int $x1, int $y1, int $x2, int $y2): int
    {
        return abs($x1 - $x2) + abs($y1 - $y2);
    }

    public function npcDistance(SimNpc $a, SimNpc $b): int
    {
        return $this->distance($a->x, $a->y, $b->x, $b->y);
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
