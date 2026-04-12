<?php

namespace App\Services\Simulation\Handler;

use App\Models\Simulation\SimNpc;
use App\Models\Simulation\SimObject;
use App\Models\Simulation\SimPlace;
use App\Services\Simulation\Ticker;

class SurvivalHandler
{
    private const FORAGE_HUNGER_GAIN = 12;
    private const SELLER_PURPOSE_ON_SALE = 20;

    public function __construct(private readonly Ticker $ticker) {}

    public function tryDrink(SimNpc $npc, int $tick): void
    {
        $well = $this->ticker->places
            ->filter(fn (SimPlace $p) => in_array($p->subtype, ['well', 'river', 'stream', 'pond'], true))
            ->sortBy(fn (SimPlace $p) => $this->ticker->distance($p->x, $p->y, $npc->x, $npc->y))
            ->first();

        if (!$well) {
            $this->ticker->idle($npc, $tick);
            return;
        }

        if ($this->ticker->distance($npc->x, $npc->y, $well->x, $well->y) > 1) {
            $this->ticker->walkTowardsPlace($npc, $well, $tick, 'water');
            return;
        }

        $before = $npc->thirst;
        $npc->thirst = min(100, $npc->thirst + 55);
        $gained = $npc->thirst - $before;
        $npc->current_action = 'drinking';
        $npc->current_action_target = $well->name;
        $this->ticker->log($npc, $tick, 'satisfy_need', 'drink', null, $well->id, "drew water at {$well->name} (thirst +{$gained})");
    }

    public function tryEat(SimNpc $npc, int $tick): void
    {
        // 1. Own food (including own for-sale stock)
        $own = SimObject::where('owner_npc_id', $npc->id)
            ->where('type', 'food')
            ->first();
        if ($own) {
            $this->consumeFood($npc, $tick, $own);
            return;
        }

        // 1b. Eat from household member's stock
        $householdIds = $this->ticker->getHouseholdMemberIds($npc);
        if (count($householdIds) > 0) {
            $shared = SimObject::whereIn('owner_npc_id', $householdIds)
                ->where('type', 'food')
                ->first();
            if ($shared) {
                $donor = $this->ticker->npcById[$shared->owner_npc_id] ?? null;
                $donorName = $donor ? $donor->name : 'household';
                $shared->owner_npc_id = $npc->id;
                $shared->save();
                $this->ticker->log(
                    $npc, $tick, 'social', 'take', $shared->id, $npc->place_id,
                    "took {$shared->name} from household ({$donorName}'s stock)"
                );
                $this->consumeFood($npc, $tick, $shared);
                return;
            }
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

        // 3. Walk to nearest affordable shop
        if ($npc->hunger >= 20) {
            $shop = $this->findNearestFoodShop($npc);
            if ($shop) {
                $this->ticker->walkTowardsPlace($npc, $shop, $tick, 'food market');
                return;
            }
        }

        // 4. Steal (gated by morals or desperation)
        if ($this->ticker->behavior()->trySteal($npc, $tick)) {
            return;
        }

        // 5. Forage — universal fallback
        $this->forage($npc, $tick);
    }

    public function trySleep(SimNpc $npc, int $tick): void
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
            $this->ticker->log($npc, $tick, 'rest', 'sleep', $bed->id, $npc->place_id, "slept on {$bed->name} (rest +{$gained})");
            return;
        }

        // Try household home first, then workplace
        $homeId = null;
        if ($npc->household_id !== null) {
            $household = $this->ticker->householdsById[$npc->household_id] ?? null;
            $homeId = $household?->home_place_id;
        }
        $targetId = $homeId ?? $npc->workplace_id;

        if ($targetId !== null && $npc->place_id !== $targetId) {
            $target = $this->ticker->placesById[$targetId] ?? null;
            if ($target !== null) {
                $label = $homeId !== null ? 'bed at home' : 'bed at workplace';
                $this->ticker->walkTowardsPlace($npc, $target, $tick, $label);
                return;
            }
        }

        $anyBed = SimObject::where('subtype', 'bed')->whereNotNull('place_id')->get();
        $closest = $anyBed
            ->map(fn (SimObject $o) => [
                'obj'   => $o,
                'place' => $this->ticker->placesById[$o->place_id] ?? null,
            ])
            ->filter(fn (array $p) => $p['place'] !== null)
            ->sortBy(fn (array $p) => $this->ticker->distance($p['place']->x, $p['place']->y, $npc->x, $npc->y))
            ->first();

        if ($closest) {
            $this->ticker->walkTowardsPlace($npc, $closest['place'], $tick, 'any bed');
            return;
        }

        $npc->current_action = 'exhausted';
        $this->ticker->log($npc, $tick, 'idle', 'rest', null, $npc->place_id, 'no bed — collapsing from exhaustion');
    }

    public function consumeFood(SimNpc $npc, int $tick, SimObject $food): void
    {
        $affordances = $food->affordances ?? [];
        $applied = [];
        foreach ($affordances as $need => $amount) {
            if (!array_key_exists($need, Ticker::DECAY)) {
                continue;
            }
            $before = $npc->{$need};
            $npc->{$need} = min(100, $npc->{$need} + $amount);
            $applied[] = "$need +" . ($npc->{$need} - $before);
        }
        $npc->current_action = 'eating';
        $npc->current_action_target = $food->name;
        $this->ticker->log($npc, $tick, 'satisfy_need', 'eat', $food->id, $npc->place_id, "ate {$food->name} (" . implode(', ', $applied) . ')');
        $food->delete();
    }

    public function purchase(SimNpc $buyer, SimObject $item, int $tick): void
    {
        $price = $item->price ?? 0;
        $sellerId = $item->owner_npc_id;
        $seller = $sellerId ? ($this->ticker->npcById[$sellerId] ?? null) : null;

        $buyer->wealth -= $price;

        if ($seller) {
            $seller->wealth += $price;
            $seller->purpose = min(100, $seller->purpose + self::SELLER_PURPOSE_ON_SALE);
            if ($seller->goal_type === 'sell_goods') {
                $seller->goal_progress++;
            }
            $seller->current_action = 'selling';
            $seller->current_action_target = $buyer->name;
            $this->ticker->log(
                $seller, $tick, 'trade', 'sell', $item->id, $item->place_id,
                "sold {$item->name} to {$buyer->name} for {$price}c (wealth → {$seller->wealth})"
            );
            $this->ticker->modifyRelationship($buyer->id, $seller->id, 3, 0, 'trade', $tick);
            $this->ticker->modifyRelationship($seller->id, $buyer->id, 3, 0, 'trade', $tick);
        }

        $item->owner_npc_id = $buyer->id;
        $item->for_sale = false;
        $item->place_id = null;
        $item->x = null;
        $item->y = null;
        $item->save();

        $sellerName = $seller ? $seller->name : 'abandoned stock';
        $this->ticker->log(
            $buyer, $tick, 'trade', 'buy', $item->id, $buyer->place_id,
            "bought {$item->name} from {$sellerName} for {$price}c (wealth → {$buyer->wealth})"
        );
    }

    public function forage(SimNpc $npc, int $tick): void
    {
        $before = $npc->hunger;
        $npc->hunger = min(100, $npc->hunger + self::FORAGE_HUNGER_GAIN);
        $gain = $npc->hunger - $before;
        $npc->current_action = 'foraging';
        $npc->current_action_target = 'wild roots';
        $this->ticker->log(
            $npc, $tick, 'work', 'forage', null, $npc->place_id,
            "foraged wild roots (hunger +{$gain})"
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
        if (count($placeIds) === 0) {
            return null;
        }
        return $this->ticker->places
            ->whereIn('id', $placeIds)
            ->sortBy(fn (SimPlace $p) => $this->ticker->distance($p->x, $p->y, $npc->x, $npc->y))
            ->first();
    }
}
