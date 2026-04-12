<?php

namespace App\Services\Simulation\Handler;

use App\Models\Simulation\SimNpc;
use App\Models\Simulation\SimObject;
use App\Services\Simulation\Ticker;

class WorkHandler
{
    private const HOUSEHOLD_PURPOSE_PER_PRODUCE = 8;
    private const RENT_EXTRACTOR_PURPOSE_ON_COLLECT = 15;
    private const DEPENDENT_PURPOSE_ON_BEG = 5;

    public function __construct(private readonly Ticker $ticker) {}

    public function householdLoop(SimNpc $npc, int $tick): void
    {
        if (!$npc->workplace_id) {
            $this->ticker->idle($npc, $tick);
            return;
        }

        if ($npc->place_id !== $npc->workplace_id) {
            $wp = $this->ticker->placesById[$npc->workplace_id] ?? null;
            if ($wp) {
                $this->ticker->walkTowardsPlace($npc, $wp, $tick, 'plot');
                return;
            }
        }

        $recipe = config('simulation_roles.recipes.' . $npc->profession);
        if (!$recipe) {
            $this->ticker->idle($npc, $tick);
            return;
        }

        $stock = SimObject::where('owner_npc_id', $npc->id)
            ->where('type', $recipe['type'])
            ->count();

        if ($stock < $recipe['max_stock']) {
            $this->produceItem($npc, $recipe, $tick, forSale: true);
            $npc->purpose = min(100, $npc->purpose + self::HOUSEHOLD_PURPOSE_PER_PRODUCE);
            return;
        }

        $npc->current_action = 'tending';
        $npc->current_action_target = null;
        $this->ticker->log($npc, $tick, 'work', 'plow', null, $npc->place_id, "tending plot (full stock {$stock})");
    }

    public function produceLoop(SimNpc $npc, int $tick): void
    {
        if (!$npc->workplace_id) {
            $this->ticker->idle($npc, $tick);
            return;
        }

        if ($npc->place_id !== $npc->workplace_id) {
            $wp = $this->ticker->placesById[$npc->workplace_id] ?? null;
            if ($wp) {
                $this->ticker->walkTowardsPlace($npc, $wp, $tick, 'workshop');
                return;
            }
        }

        $recipe = config('simulation_roles.recipes.' . $npc->profession);
        if (!$recipe) {
            $npc->current_action = 'waiting';
            $this->ticker->log($npc, $tick, 'work', 'observe', null, $npc->place_id, 'no recipe — waiting');
            return;
        }

        $stock = SimObject::where('owner_npc_id', $npc->id)
            ->where('place_id', $npc->workplace_id)
            ->where('for_sale', true)
            ->count();

        if ($stock < $recipe['max_stock']) {
            $this->produceItem($npc, $recipe, $tick, forSale: true);
            return;
        }

        $npc->current_action = 'waiting';
        $npc->current_action_target = null;
        $this->ticker->log($npc, $tick, 'work', 'observe', null, $npc->place_id, "stock full ({$stock}) — waiting for buyer");
    }

    public function rentLoop(SimNpc $npc, int $tick): void
    {
        if (!$npc->workplace_id) {
            $this->ticker->idle($npc, $tick);
            return;
        }

        if ($npc->place_id !== $npc->workplace_id) {
            $wp = $this->ticker->placesById[$npc->workplace_id] ?? null;
            if ($wp) {
                $this->ticker->walkTowardsPlace($npc, $wp, $tick, 'office');
                return;
            }
        }

        $cfg = config('simulation_roles.rent_extractor');
        $interval = $cfg['tribute_interval'];

        if (($tick - ($npc->last_work_tick ?? 0)) < $interval) {
            $npc->current_action = 'presiding';
            $npc->current_action_target = null;
            $this->ticker->log($npc, $tick, 'work', 'observe', null, $npc->place_id, 'presiding over domain');
            return;
        }

        $radius = $cfg['tribute_radius'];
        $amount = $cfg['tribute_amount'];
        $minVictim = $cfg['min_victim_wealth'];

        $victims = $this->ticker->npcById
            ->filter(fn (SimNpc $other) =>
                $other->id !== $npc->id
                && $other->wealth >= $minVictim
                && (abs($other->x - $npc->x) + abs($other->y - $npc->y)) <= $radius
            )
            ->values();

        $collected = 0;
        foreach ($victims as $victim) {
            $paid = min($amount, $victim->wealth);
            if ($paid <= 0) {
                continue;
            }
            $victim->wealth -= $paid;
            $npc->wealth += $paid;
            $collected += $paid;
            $this->ticker->log(
                $victim, $tick, 'trade', 'give', null, $victim->place_id,
                "paid {$paid}c tribute to {$npc->name}"
            );
            $this->ticker->modifyRelationship($victim->id, $npc->id, -5, 10, 'tribute', $tick);
        }

        if ($collected > 0) {
            $npc->purpose = min(100, $npc->purpose + self::RENT_EXTRACTOR_PURPOSE_ON_COLLECT);
            $npc->current_action = 'collecting';
            $npc->last_work_tick = $tick;
            $this->ticker->log(
                $npc, $tick, 'trade', 'take', null, $npc->place_id,
                "collected {$collected}c tribute from " . $victims->count() . ' subject(s)'
            );
        } else {
            $npc->current_action = 'presiding';
            $this->ticker->log($npc, $tick, 'work', 'observe', null, $npc->place_id, 'no tribute to collect nearby');
        }
    }

    public function dependentLoop(SimNpc $npc, int $tick): void
    {
        $square = $this->ticker->places->firstWhere('subtype', 'town_square');
        if ($square && $npc->place_id !== $square->id) {
            $this->ticker->walkTowardsPlace($npc, $square, $tick, 'begging pitch');
            return;
        }

        $cfg = config('simulation_roles.dependent');
        $radius = $cfg['beg_radius'];
        $minDonor = $cfg['min_donor_wealth'];
        $amount = $cfg['beg_amount'];

        $donor = $this->ticker->npcById
            ->filter(fn (SimNpc $other) =>
                $other->id !== $npc->id
                && $other->archetype !== 'dependent'
                && $other->wealth >= $minDonor
                && (abs($other->x - $npc->x) + abs($other->y - $npc->y)) <= $radius
            )
            ->sortBy(fn (SimNpc $other) => abs($other->x - $npc->x) + abs($other->y - $npc->y))
            ->first();

        if ($donor) {
            $paid = min($amount, $donor->wealth);
            $donor->wealth -= $paid;
            $npc->wealth += $paid;
            $npc->purpose = min(100, $npc->purpose + self::DEPENDENT_PURPOSE_ON_BEG);
            $npc->current_action = 'begging';
            $npc->current_action_target = $donor->name;
            $this->ticker->log($npc, $tick, 'trade', 'take', null, $npc->place_id, "begged {$paid}c from {$donor->name}");
            $this->ticker->log($donor, $tick, 'trade', 'give', null, $donor->place_id, "gave {$paid}c to {$npc->name}");
            return;
        }

        $npc->current_action = 'begging';
        $npc->current_action_target = null;
        $this->ticker->log($npc, $tick, 'idle', 'observe', null, $npc->place_id, 'no charitable donors nearby');
    }

    /**
     * @param array<string, mixed> $recipe
     */
    public function produceItem(SimNpc $npc, array $recipe, int $tick, bool $forSale): void
    {
        $item = SimObject::create([
            'name'         => $recipe['name'],
            'type'         => $recipe['type'],
            'subtype'      => $recipe['subtype'],
            'material'     => $recipe['material'],
            'quality'      => 'common',
            'wear'         => 'pristine',
            'weight'       => $recipe['weight'] ?? 1,
            'value'        => $recipe['value'] ?? 1,
            'owner_npc_id' => $npc->id,
            'place_id'     => $npc->workplace_id,
            'x'            => $npc->x,
            'y'            => $npc->y,
            'for_sale'     => $forSale,
            'price'        => $recipe['price'],
            'affordances'  => !empty($recipe['affordances']) ? $recipe['affordances'] : null,
        ]);

        $verb = $this->workVerb($npc->profession);
        $npc->current_action = $verb . 'ing';
        $npc->current_action_target = $item->name;
        $npc->last_work_tick = $tick;
        $stateLabel = $forSale ? "for sale at {$recipe['price']}c" : 'stored';
        $this->ticker->log($npc, $tick, 'crafting', $verb, $item->id, $npc->place_id, "{$verb} {$item->name} ({$stateLabel})");
    }

    private function workVerb(string $profession): string
    {
        return match ($profession) {
            'blacksmith'             => 'forge',
            'baker'                  => 'cook',
            'butcher'                => 'craft',
            'brewer'                 => 'brew',
            'weaver'                 => 'sew',
            'tailor'                 => 'sew',
            'cobbler'                => 'sew',
            'tanner'                 => 'craft',
            'carpenter', 'cooper'    => 'craft',
            'potter'                 => 'craft',
            'alchemist', 'herbalist' => 'brew',
            'cook', 'innkeeper'      => 'cook',
            'scribe'                 => 'craft',
            'gravedigger'            => 'craft',
            'farmer', 'shepherd'     => 'harvest',
            'fisher'                 => 'fish',
            'hunter'                 => 'hunt',
            default                  => 'craft',
        };
    }
}
