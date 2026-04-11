<?php

namespace App\Services\Simulation;

use App\Models\Simulation\SimNpc;
use App\Models\Simulation\SimObject;
use App\Models\Simulation\SimPlace;
use App\Models\Simulation\SimState;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class Ticker
{
    // Survival-lite: needs decay slowly so NPCs spend most ticks on
    // work/social/trade, not on eat/drink cycles. They still matter —
    // ignore them long enough and you die — just not the whole game loop.
    private const DECAY = [
        'hunger'      => 2,
        'thirst'      => 2,
        'rest'        => 1,
        'hygiene'     => 1,
        'safety'      => 0,
        'social_need' => 1,
        'purpose'     => 2,
    ];

    private const TIMES = [
        'dawn', 'morning', 'noon', 'afternoon', 'dusk', 'evening', 'night', 'midnight',
    ];

    private const GRID = 60;

    private const CRITICAL_THRESHOLD = 35;
    private const SELLER_PURPOSE_ON_SALE = 20;
    private const HOUSEHOLD_PURPOSE_PER_PRODUCE = 8;
    private const RENT_EXTRACTOR_PURPOSE_ON_COLLECT = 15;
    private const DEPENDENT_PURPOSE_ON_BEG = 5;

    // Humans-not-robots gates (OCEAN-driven, no selfishness axis)
    private const SHIRK_CONSCIENTIOUSNESS_MAX = 4;   // lazy NPCs skip duty
    private const HELP_AGREEABLENESS_MIN = 7;        // kind NPCs assist others
    private const STEAL_AGREEABLENESS_MAX = 4;       // cold NPCs steal
    private const STEAL_DESPERATE_HUNGER = 25;       // any NPC steals if this hungry
    private const HELP_RADIUS = 6;
    private const STEAL_RADIUS = 8;

    // Universal skill tuning
    private const FORAGE_HUNGER_GAIN = 12;
    private const MEND_HYGIENE_GAIN = 15;
    private const PRAY_PURPOSE_GAIN = 12;
    private const PRAY_SAFETY_GAIN = 6;
    private const HELP_PURPOSE_GAIN = 8;
    private const HELP_SOCIAL_GAIN = 10;
    private const CHARITY_PURPOSE_GAIN = 15;

    private const PURPOSE_PRAY_THRESHOLD = 40;
    private const HYGIENE_MEND_THRESHOLD = 40;

    // Death — min survival need at 0 for this many consecutive ticks
    private const DEATH_GRACE_TICKS = 5;

    /** @var array<int, array<string, mixed>> */
    private array $actionBatch = [];

    /** @var Collection<int, SimNpc> */
    private Collection $npcById;

    /** @var Collection<int, SimPlace> */
    private Collection $placesById;

    /** @var Collection<int, SimPlace> */
    private Collection $places;

    public function tick(): array
    {
        $state = SimState::current();
        $state->tick++;
        $state->time_of_day = self::TIMES[$state->tick % count(self::TIMES)];
        $state->save();

        $this->actionBatch = [];
        $this->npcById = SimNpc::all()->keyBy('id');
        $this->places = SimPlace::all();
        $this->placesById = $this->places->keyBy('id');

        DB::transaction(function () use ($state) {
            foreach ($this->npcById as $npc) {
                $this->decayNeeds($npc);
                $this->actFor($npc, $state->tick);
            }
            foreach ($this->npcById as $npc) {
                $npc->save();
            }
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

        if ($this->checkDeath($npc, $tick)) {
            return;
        }

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
                'thirst' => $this->tryDrink($npc, $tick),
                'hunger' => $this->tryEat($npc, $tick),
                'rest'   => $this->trySleep($npc, $tick),
            };
            return;
        }

        // Lazy NPCs skip duty to self-care when moderately uncomfortable
        if ($this->shouldShirkWork($npc)) {
            $this->selfCare($npc, $tick);
            return;
        }

        // Kind NPCs help suffering neighbours when their own needs are OK
        if ($this->shouldHelpOthers($npc) && $this->tryHelp($npc, $tick)) {
            return;
        }

        // Low purpose → pray at shrine if one exists
        if ($npc->purpose < self::PURPOSE_PRAY_THRESHOLD && $this->pray($npc, $tick)) {
            return;
        }

        // Low hygiene → mend clothes
        if ($npc->hygiene < self::HYGIENE_MEND_THRESHOLD && $this->mend($npc, $tick)) {
            return;
        }

        match ($npc->archetype) {
            'household_producer' => $this->householdLoop($npc, $tick),
            'market_craftsman'   => $this->produceLoop($npc, $tick),
            'service_provider'   => $this->produceLoop($npc, $tick),
            'rent_extractor'     => $this->rentLoop($npc, $tick),
            'dependent'          => $this->dependentLoop($npc, $tick),
            default              => $this->idle($npc, $tick),
        };
    }

    // ---------------------------------------------------------------
    //  Critical survival
    // ---------------------------------------------------------------

    private function tryDrink(SimNpc $npc, int $tick): void
    {
        $well = $this->places
            ->filter(fn (SimPlace $p) => in_array($p->subtype, ['well', 'river', 'stream', 'pond'], true))
            ->sortBy(fn (SimPlace $p) => abs($p->x - $npc->x) + abs($p->y - $npc->y))
            ->first();
        if (!$well) {
            $this->idle($npc, $tick);
            return;
        }

        if (abs($npc->x - $well->x) + abs($npc->y - $well->y) > 1) {
            $this->walkTowardsPlace($npc, $well, $tick, 'water');
            return;
        }

        $before = $npc->thirst;
        $npc->thirst = min(100, $npc->thirst + 55);
        $gained = $npc->thirst - $before;
        $npc->current_action = 'drinking';
        $npc->current_action_target = $well->name;
        $this->log($npc, $tick, 'satisfy_need', 'drink', null, $well->id, "drew water at {$well->name} (thirst +{$gained})");
    }

    private function tryEat(SimNpc $npc, int $tick): void
    {
        // 1. Own food (including own for_sale stock — the baker eats his own bread)
        $own = SimObject::where('owner_npc_id', $npc->id)
            ->where('type', 'food')
            ->first();
        if ($own) {
            $this->consumeFood($npc, $tick, $own);
            return;
        }

        // 2. Buy affordable food at current place
        $here = SimObject::where('place_id', $npc->place_id)
            ->where('for_sale', true)
            ->where('type', 'food')
            ->where('price', '<=', $npc->wealth)
            ->orderBy('price')
            ->first();
        if ($here) {
            $this->purchase($npc, $here, $tick);
            $this->consumeFood($npc, $tick, $here);
            return;
        }

        // 3. Walk to nearest affordable shop (skip if too desperate to travel)
        if ($npc->hunger >= 20) {
            $shop = $this->findNearestFoodShop($npc);
            if ($shop) {
                $this->walkTowardsPlace($npc, $shop, $tick, 'food market');
                return;
            }
        }

        // 4. Steal from someone's for-sale stock (gated by morals or desperation)
        if ($this->trySteal($npc, $tick)) {
            return;
        }

        // 5. Forage — universal fallback, every human can scrape roots
        $this->forage($npc, $tick);
    }

    private function trySleep(SimNpc $npc, int $tick): void
    {
        $bed = SimObject::where('place_id', $npc->place_id)
            ->where('subtype', 'bed')
            ->first();
        if ($bed) {
            $restore = $bed->affordances['rest'] ?? 40;
            $before = $npc->rest;
            $npc->rest = min(100, $npc->rest + $restore);
            $gained = $npc->rest - $before;
            $npc->current_action = 'sleeping';
            $npc->current_action_target = $bed->name;
            $this->log($npc, $tick, 'rest', 'sleep', $bed->id, $npc->place_id, "slept on {$bed->name} (rest +{$gained})");
            return;
        }

        if ($npc->workplace_id && $npc->place_id !== $npc->workplace_id) {
            $wp = $this->placesById[$npc->workplace_id] ?? null;
            if ($wp) {
                $this->walkTowardsPlace($npc, $wp, $tick, 'bed at workplace');
                return;
            }
        }

        $anyBed = SimObject::where('subtype', 'bed')->whereNotNull('place_id')->get();
        $closest = $anyBed
            ->map(fn (SimObject $o) => [
                'obj' => $o,
                'place' => $this->placesById[$o->place_id] ?? null,
            ])
            ->filter(fn (array $p) => $p['place'] !== null)
            ->sortBy(fn (array $p) => abs($p['place']->x - $npc->x) + abs($p['place']->y - $npc->y))
            ->first();

        if ($closest) {
            $this->walkTowardsPlace($npc, $closest['place'], $tick, 'any bed');
            return;
        }

        $npc->current_action = 'exhausted';
        $this->log($npc, $tick, 'idle', 'rest', null, $npc->place_id, 'no bed — collapsing from exhaustion');
    }

    private function consumeFood(SimNpc $npc, int $tick, SimObject $food): void
    {
        $affordances = $food->affordances ?? [];
        $applied = [];
        foreach ($affordances as $need => $amount) {
            if (!array_key_exists($need, self::DECAY)) {
                continue;
            }
            $before = $npc->{$need};
            $npc->{$need} = min(100, $npc->{$need} + $amount);
            $applied[] = "$need +" . ($npc->{$need} - $before);
        }
        $npc->current_action = 'eating';
        $npc->current_action_target = $food->name;
        $this->log($npc, $tick, 'satisfy_need', 'eat', $food->id, $npc->place_id, "ate {$food->name} (" . implode(', ', $applied) . ')');
        $food->delete();
    }

    private function purchase(SimNpc $buyer, SimObject $item, int $tick): void
    {
        $price = $item->price ?? 0;
        $sellerId = $item->owner_npc_id;
        $seller = $sellerId ? ($this->npcById[$sellerId] ?? null) : null;

        $buyer->wealth -= $price;

        if ($seller) {
            $seller->wealth += $price;
            $seller->purpose = min(100, $seller->purpose + self::SELLER_PURPOSE_ON_SALE);
            $seller->current_action = 'selling';
            $seller->current_action_target = $buyer->name;
            $this->log(
                $seller, $tick, 'trade', 'sell', $item->id, $item->place_id,
                "sold {$item->name} to {$buyer->name} for {$price}c (wealth → {$seller->wealth})"
            );
        }

        $item->owner_npc_id = $buyer->id;
        $item->for_sale = false;
        $item->place_id = null;
        $item->x = null;
        $item->y = null;
        $item->save();

        $sellerName = $seller ? $seller->name : 'abandoned stock';
        $this->log(
            $buyer, $tick, 'trade', 'buy', $item->id, $buyer->place_id,
            "bought {$item->name} from {$sellerName} for {$price}c (wealth → {$buyer->wealth})"
        );
    }

    private function findNearestFoodShop(SimNpc $npc): ?SimPlace
    {
        if ($npc->wealth <= 0) {
            return null;
        }
        $placeIds = SimObject::where('for_sale', true)
            ->where('type', 'food')
            ->where('price', '<=', $npc->wealth)
            ->whereNotNull('place_id')
            ->distinct()
            ->pluck('place_id')
            ->all();
        if (empty($placeIds)) {
            return null;
        }
        return $this->places
            ->whereIn('id', $placeIds)
            ->sortBy(fn (SimPlace $p) => abs($p->x - $npc->x) + abs($p->y - $npc->y))
            ->first();
    }

    // ---------------------------------------------------------------
    //  Archetype loops
    // ---------------------------------------------------------------

    private function householdLoop(SimNpc $npc, int $tick): void
    {
        if (!$npc->workplace_id) {
            $this->idle($npc, $tick);
            return;
        }

        if ($npc->place_id !== $npc->workplace_id) {
            $wp = $this->placesById[$npc->workplace_id] ?? null;
            if ($wp) {
                $this->walkTowardsPlace($npc, $wp, $tick, 'plot');
                return;
            }
        }

        $recipe = config('simulation_roles.recipes.' . $npc->profession);
        if (!$recipe) {
            $this->idle($npc, $tick);
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
        $this->log($npc, $tick, 'work', 'plow', null, $npc->place_id, "tending plot (full stock {$stock})");
    }

    private function produceLoop(SimNpc $npc, int $tick): void
    {
        if (!$npc->workplace_id) {
            $this->idle($npc, $tick);
            return;
        }

        if ($npc->place_id !== $npc->workplace_id) {
            $wp = $this->placesById[$npc->workplace_id] ?? null;
            if ($wp) {
                $this->walkTowardsPlace($npc, $wp, $tick, 'workshop');
                return;
            }
        }

        $recipe = config('simulation_roles.recipes.' . $npc->profession);
        if (!$recipe) {
            $npc->current_action = 'waiting';
            $this->log($npc, $tick, 'work', 'observe', null, $npc->place_id, 'no recipe — waiting');
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
        $this->log($npc, $tick, 'work', 'observe', null, $npc->place_id, "stock full ({$stock}) — waiting for buyer");
    }

    private function rentLoop(SimNpc $npc, int $tick): void
    {
        if (!$npc->workplace_id) {
            $this->idle($npc, $tick);
            return;
        }

        if ($npc->place_id !== $npc->workplace_id) {
            $wp = $this->placesById[$npc->workplace_id] ?? null;
            if ($wp) {
                $this->walkTowardsPlace($npc, $wp, $tick, 'office');
                return;
            }
        }

        $cfg = config('simulation_roles.rent_extractor');
        $interval = $cfg['tribute_interval'];

        if (($tick - ($npc->last_work_tick ?? 0)) < $interval) {
            $npc->current_action = 'presiding';
            $npc->current_action_target = null;
            $this->log($npc, $tick, 'work', 'observe', null, $npc->place_id, 'presiding over domain');
            return;
        }

        $radius = $cfg['tribute_radius'];
        $amount = $cfg['tribute_amount'];
        $minVictim = $cfg['min_victim_wealth'];

        $victims = $this->npcById
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
            $this->log(
                $victim, $tick, 'trade', 'give', null, $victim->place_id,
                "paid {$paid}c tribute to {$npc->name}"
            );
        }

        if ($collected > 0) {
            $npc->purpose = min(100, $npc->purpose + self::RENT_EXTRACTOR_PURPOSE_ON_COLLECT);
            $npc->current_action = 'collecting';
            $npc->last_work_tick = $tick;
            $this->log(
                $npc, $tick, 'trade', 'take', null, $npc->place_id,
                "collected {$collected}c tribute from " . $victims->count() . ' subject(s)'
            );
        } else {
            $npc->current_action = 'presiding';
            $this->log($npc, $tick, 'work', 'observe', null, $npc->place_id, 'no tribute to collect nearby');
        }
    }

    private function dependentLoop(SimNpc $npc, int $tick): void
    {
        $square = $this->places->firstWhere('subtype', 'town_square');
        if ($square && $npc->place_id !== $square->id) {
            $this->walkTowardsPlace($npc, $square, $tick, 'begging pitch');
            return;
        }

        $cfg = config('simulation_roles.dependent');
        $radius = $cfg['beg_radius'];
        $minDonor = $cfg['min_donor_wealth'];
        $amount = $cfg['beg_amount'];

        $donor = $this->npcById
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
            $this->log($npc, $tick, 'trade', 'take', null, $npc->place_id, "begged {$paid}c from {$donor->name}");
            $this->log($donor, $tick, 'trade', 'give', null, $donor->place_id, "gave {$paid}c to {$npc->name}");
            return;
        }

        $npc->current_action = 'begging';
        $npc->current_action_target = null;
        $this->log($npc, $tick, 'idle', 'observe', null, $npc->place_id, 'no charitable donors nearby');
    }

    private function idle(SimNpc $npc, int $tick): void
    {
        $npc->x = max(0, min(self::GRID - 1, $npc->x + random_int(-1, 1)));
        $npc->y = max(0, min(self::GRID - 1, $npc->y + random_int(-1, 1)));
        $npc->place_id = $this->placeAt($npc->x, $npc->y);
        $npc->current_action = 'idle';
        $npc->current_action_target = null;
        $this->log($npc, $tick, 'idle', 'walk_to', null, $npc->place_id, 'wandered aimlessly');
    }

    // ---------------------------------------------------------------
    //  Universal skills & social behaviors
    //  Every NPC can do these regardless of archetype. Gated by OCEAN
    //  traits so emergent behavior comes from personality, not config.
    //  TODO: extract into UniversalSkills service once the library grows.
    // ---------------------------------------------------------------

    private function checkDeath(SimNpc $npc, int $tick): bool
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

        // Drop inventory at last location as abandoned stock
        SimObject::where('owner_npc_id', $npc->id)->update([
            'owner_npc_id' => null,
            'place_id'     => $npc->place_id,
            'x'            => $npc->x,
            'y'            => $npc->y,
            'for_sale'     => false,
        ]);

        $npc->current_action = 'dead';
        $npc->current_action_target = $cause;
        $this->log(
            $npc, $tick, 'idle', 'rest', null, $npc->place_id,
            "died of {$cause}"
        );
    }

    private function shouldShirkWork(SimNpc $npc): bool
    {
        if ($npc->conscientiousness > self::SHIRK_CONSCIENTIOUSNESS_MAX) {
            return false;
        }
        return $npc->rest < 40 || $npc->hunger < 40;
    }

    private function selfCare(SimNpc $npc, int $tick): void
    {
        if ($npc->hunger <= $npc->rest) {
            $this->tryEat($npc, $tick);
            return;
        }
        $this->trySleep($npc, $tick);
    }

    private function shouldHelpOthers(SimNpc $npc): bool
    {
        if ($npc->agreeableness < self::HELP_AGREEABLENESS_MIN) {
            return false;
        }
        if ($npc->hunger < 55 || $npc->thirst < 55 || $npc->rest < 55) {
            return false;
        }
        return true;
    }

    private function tryHelp(SimNpc $npc, int $tick): bool
    {
        $sufferer = $this->npcById
            ->filter(function (SimNpc $other) use ($npc) {
                if ($other->id === $npc->id) {
                    return false;
                }
                if ($other->current_action === 'dead') {
                    return false;
                }
                $worst = min($other->hunger, $other->thirst, $other->rest);
                if ($worst >= 40) {
                    return false;
                }
                return (abs($other->x - $npc->x) + abs($other->y - $npc->y)) <= self::HELP_RADIUS;
            })
            ->sortBy(fn (SimNpc $other) => min($other->hunger, $other->thirst, $other->rest))
            ->first();

        if (!$sufferer) {
            return false;
        }

        $dist = abs($sufferer->x - $npc->x) + abs($sufferer->y - $npc->y);
        if ($dist > 1) {
            $npc->x += $this->step($npc->x, $sufferer->x);
            $npc->y += $this->step($npc->y, $sufferer->y);
            $npc->place_id = $this->placeAt($npc->x, $npc->y);
            $npc->current_action = 'walking';
            $npc->current_action_target = $sufferer->name;
            $this->log(
                $npc, $tick, 'social', 'walk_to', null, $npc->place_id,
                "rushing to help {$sufferer->name}"
            );
            return true;
        }

        // If sufferer is hungry and helper has spare food, donate one
        if ($sufferer->hunger < 40) {
            $spare = SimObject::where('owner_npc_id', $npc->id)
                ->where('type', 'food')
                ->first();
            if ($spare) {
                $spare->owner_npc_id = $sufferer->id;
                $spare->for_sale = false;
                $spare->place_id = null;
                $spare->x = null;
                $spare->y = null;
                $spare->save();

                $npc->purpose = min(100, $npc->purpose + self::CHARITY_PURPOSE_GAIN);
                $npc->current_action = 'giving';
                $npc->current_action_target = $sufferer->name;
                $this->log(
                    $npc, $tick, 'social', 'give', $spare->id, $npc->place_id,
                    "gave {$spare->name} to {$sufferer->name} (charity)"
                );
                return true;
            }
        }

        // Keep company — both sides gain social, helper gains purpose
        $npc->purpose = min(100, $npc->purpose + self::HELP_PURPOSE_GAIN);
        $sufferer->social_need = min(100, $sufferer->social_need + self::HELP_SOCIAL_GAIN);
        $npc->current_action = 'helping';
        $npc->current_action_target = $sufferer->name;
        $this->log(
            $npc, $tick, 'social', 'greet', null, $npc->place_id,
            "kept company with {$sufferer->name}"
        );
        return true;
    }

    private function forage(SimNpc $npc, int $tick): void
    {
        $before = $npc->hunger;
        $npc->hunger = min(100, $npc->hunger + self::FORAGE_HUNGER_GAIN);
        $gain = $npc->hunger - $before;
        $npc->current_action = 'foraging';
        $npc->current_action_target = 'wild roots';
        $this->log(
            $npc, $tick, 'work', 'forage', null, $npc->place_id,
            "foraged wild roots (hunger +{$gain})"
        );
    }

    private function trySteal(SimNpc $npc, int $tick): bool
    {
        $amoral = $npc->agreeableness <= self::STEAL_AGREEABLENESS_MAX;
        $desperate = $npc->hunger < self::STEAL_DESPERATE_HUNGER;
        if (!$amoral && !$desperate) {
            return false;
        }

        $target = SimObject::where('type', 'food')
            ->where('owner_npc_id', '!=', $npc->id)
            ->whereNotNull('owner_npc_id')
            ->whereNotNull('x')
            ->whereNotNull('y')
            ->get()
            ->filter(fn (SimObject $o) => (abs($o->x - $npc->x) + abs($o->y - $npc->y)) <= self::STEAL_RADIUS)
            ->sortBy(fn (SimObject $o) => abs($o->x - $npc->x) + abs($o->y - $npc->y))
            ->first();

        if (!$target) {
            return false;
        }

        $victim = $this->npcById[$target->owner_npc_id] ?? null;
        if (!$victim || $victim->current_action === 'dead') {
            // Owner gone — just take it, no resistance
            $this->completeTheft($npc, $target, null, $tick, silent: true);
            return true;
        }

        $dist = abs($target->x - $npc->x) + abs($target->y - $npc->y);
        if ($dist > 1) {
            $npc->x += $this->step($npc->x, $target->x);
            $npc->y += $this->step($npc->y, $target->y);
            $npc->place_id = $this->placeAt($npc->x, $npc->y);
            $npc->current_action = 'sneaking';
            $npc->current_action_target = $target->name;
            $this->log(
                $npc, $tick, 'travel', 'sneak', $target->id, $npc->place_id,
                "sneaking towards {$target->name} at {$victim->name}'s pitch"
            );
            return true;
        }

        // INT check vs DEX + d6
        $roll = $npc->int - ($victim->dex + random_int(1, 6));
        if ($roll >= 0) {
            $this->completeTheft($npc, $target, $victim, $tick, silent: false);
            return true;
        }

        // Caught — safety hit on thief, purpose hit on victim (violation)
        $npc->safety = max(0, $npc->safety - 20);
        $victim->purpose = max(0, $victim->purpose - 10);
        $npc->current_action = 'fleeing';
        $npc->current_action_target = $victim->name;
        $this->log(
            $npc, $tick, 'combat', 'steal', $target->id, $npc->place_id,
            "botched theft of {$target->name} — caught by {$victim->name}"
        );
        return true;
    }

    private function completeTheft(
        SimNpc $thief,
        SimObject $item,
        ?SimNpc $victim,
        int $tick,
        bool $silent,
    ): void {
        $item->owner_npc_id = $thief->id;
        $item->for_sale = false;
        $item->place_id = null;
        $item->x = null;
        $item->y = null;
        $item->save();

        $thief->current_action = 'stealing';
        $thief->current_action_target = $item->name;

        $victimName = $victim ? $victim->name : 'abandoned stock';
        $tag = $silent ? '(abandoned)' : "(from {$victimName})";
        $this->log(
            $thief, $tick, 'combat', 'steal', $item->id, $thief->place_id,
            "stole {$item->name} {$tag}"
        );

        if ($victim && !$silent) {
            $victim->purpose = max(0, $victim->purpose - 5);
            $this->log(
                $victim, $tick, 'combat', 'observe', $item->id, $victim->place_id,
                "lost {$item->name} to a thief"
            );
        }
    }

    private function pray(SimNpc $npc, int $tick): bool
    {
        $shrine = $this->places->firstWhere('subtype', 'shrine');
        if (!$shrine) {
            return false;
        }

        $distToShrine = abs($shrine->x - $npc->x) + abs($shrine->y - $npc->y);
        if ($distToShrine > 12) {
            return false;
        }

        if ($npc->place_id !== $shrine->id) {
            $this->walkTowardsPlace($npc, $shrine, $tick, 'shrine');
            return true;
        }

        $before = $npc->purpose;
        $npc->purpose = min(100, $npc->purpose + self::PRAY_PURPOSE_GAIN);
        $npc->safety = min(100, $npc->safety + self::PRAY_SAFETY_GAIN);
        $gain = $npc->purpose - $before;
        $npc->current_action = 'praying';
        $npc->current_action_target = $shrine->name;
        $this->log(
            $npc, $tick, 'social', 'pray', null, $shrine->id,
            "prayed at {$shrine->name} (purpose +{$gain})"
        );
        return true;
    }

    private function mend(SimNpc $npc, int $tick): bool
    {
        $before = $npc->hygiene;
        $npc->hygiene = min(100, $npc->hygiene + self::MEND_HYGIENE_GAIN);
        $gain = $npc->hygiene - $before;
        $npc->current_action = 'mending';
        $npc->current_action_target = null;
        $this->log(
            $npc, $tick, 'crafting', 'repair', null, $npc->place_id,
            "mended clothes and washed (hygiene +{$gain})"
        );
        return true;
    }

    // ---------------------------------------------------------------
    //  Production / movement primitives
    // ---------------------------------------------------------------

    private function produceItem(SimNpc $npc, array $recipe, int $tick, bool $forSale): void
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
        $this->log($npc, $tick, 'crafting', $verb, $item->id, $npc->place_id, "{$verb} {$item->name} ({$stateLabel})");
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

    private function walkTowardsPlace(SimNpc $npc, SimPlace $place, int $tick, string $label): void
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

    private function stepTowards(SimNpc $npc, int $tx, int $ty): void
    {
        $npc->x = max(0, min(self::GRID - 1, $npc->x + $this->step($npc->x, $tx)));
        $npc->y = max(0, min(self::GRID - 1, $npc->y + $this->step($npc->y, $ty)));
    }

    private function placeAt(int $x, int $y): ?int
    {
        foreach ($this->places as $p) {
            if ($x >= $p->x && $x < $p->x + $p->width
                && $y >= $p->y && $y < $p->y + $p->height) {
                return $p->id;
            }
        }
        return null;
    }

    private function step(int $from, int $to): int
    {
        return $from === $to ? 0 : ($from < $to ? 1 : -1);
    }

    private function log(
        SimNpc $npc,
        int $tick,
        string $type,
        string $verb,
        ?int $objectId,
        ?int $placeId,
        string $description,
    ): void {
        $now = now();
        $this->actionBatch[] = [
            'type'             => $type,
            'verb'             => $verb,
            'source_npc_id'    => $npc->id,
            'target_npc_id'    => null,
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
}
