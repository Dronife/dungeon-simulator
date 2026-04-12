<?php

namespace App\Services\Simulation\Handler;

use App\Models\Simulation\SimNpc;
use App\Models\Simulation\SimObject;
use App\Models\Simulation\SimPlace;
use App\Services\Simulation\Ticker;

class EventHandler
{
    private const EVENT_INTERVAL = 8;
    private const EVENT_CHANCE = 60;

    public function __construct(private readonly Ticker $ticker) {}

    public function maybeTriggerEvent(int $tick): void
    {
        if ($tick % self::EVENT_INTERVAL !== 0) {
            return;
        }

        if (random_int(1, 100) > self::EVENT_CHANCE) {
            return;
        }

        $events = ['fire', 'bad_harvest', 'traveling_merchant', 'blessing', 'brawl'];
        $event = $events[array_rand($events)];

        match ($event) {
            'fire'                => $this->fire($tick),
            'bad_harvest'         => $this->badHarvest($tick),
            'traveling_merchant'  => $this->travelingMerchant($tick),
            'blessing'            => $this->blessing($tick),
            'brawl'               => $this->brawl($tick),
        };
    }

    private function fire(int $tick): void
    {
        $buildings = $this->ticker->places->filter(
            fn (SimPlace $p) => $p->type === 'building' && !in_array($p->subtype, ['shrine', 'barracks'], true)
        );
        $target = $buildings->random();
        if ($target === null) {
            return;
        }

        $destroyed = SimObject::where('place_id', $target->id)
            ->where('for_sale', true)
            ->limit(3)
            ->get();

        $count = $destroyed->count();
        foreach ($destroyed as $item) {
            $item->delete();
        }

        // NPCs at this place lose safety
        foreach ($this->ticker->npcById as $npc) {
            if ($npc->place_id === $target->id && $npc->current_action !== 'dead') {
                $npc->safety = max(0, $npc->safety - 20);
                $npc->rest = max(0, $npc->rest - 10);
            }
        }

        $this->logEvent($tick, "fire broke out at {$target->name} — {$count} goods destroyed");
    }

    private function badHarvest(int $tick): void
    {
        $farmers = $this->ticker->npcById->filter(
            fn (SimNpc $npc) => $npc->archetype === 'household_producer' && $npc->current_action !== 'dead'
        );

        if ($farmers->isEmpty()) {
            return;
        }

        $victim = $farmers->random();
        $spoiled = SimObject::where('owner_npc_id', $victim->id)
            ->where('type', 'food')
            ->limit(2)
            ->get();

        $count = $spoiled->count();
        foreach ($spoiled as $item) {
            $item->delete();
        }

        $victim->purpose = max(0, $victim->purpose - 15);

        $this->logEvent($tick, "blight struck {$victim->name}'s stores — {$count} food lost");
    }

    private function travelingMerchant(int $tick): void
    {
        $square = $this->ticker->places->firstWhere('subtype', 'town_square');
        if ($square === null) {
            return;
        }

        $goods = [
            ['name' => 'exotic spice',    'type' => 'food',       'subtype' => 'spice',    'material' => 'herb',    'affordances' => ['hunger' => 35, 'purpose' => 10], 'price' => 8],
            ['name' => 'foreign wine',     'type' => 'drink',      'subtype' => 'wine',     'material' => 'glass',   'affordances' => ['thirst' => 30, 'social_need' => 15], 'price' => 10],
            ['name' => 'traveler\'s cloak','type' => 'clothing',   'subtype' => 'cloak',    'material' => 'wool',    'affordances' => ['safety' => 12, 'hygiene' => 15], 'price' => 15],
        ];

        $chosen = $goods[array_rand($goods)];
        $quantity = random_int(2, 4);

        for ($i = 0; $i < $quantity; $i++) {
            SimObject::create([
                'name'        => $chosen['name'],
                'type'        => $chosen['type'],
                'subtype'     => $chosen['subtype'],
                'material'    => $chosen['material'],
                'quality'     => 'fine',
                'wear'        => 'pristine',
                'weight'      => 1,
                'value'       => $chosen['price'],
                'place_id'    => $square->id,
                'x'           => $square->x + random_int(0, max(0, $square->width - 1)),
                'y'           => $square->y + random_int(0, max(0, $square->height - 1)),
                'for_sale'    => true,
                'price'       => $chosen['price'],
                'affordances' => $chosen['affordances'],
            ]);
        }

        $this->logEvent($tick, "a traveling merchant arrived at {$square->name} selling {$quantity}x {$chosen['name']}");
    }

    private function blessing(int $tick): void
    {
        $shrine = $this->ticker->places->firstWhere('subtype', 'shrine');
        if ($shrine === null) {
            return;
        }

        foreach ($this->ticker->npcById as $npc) {
            if ($npc->current_action === 'dead') {
                continue;
            }
            if ($this->ticker->distance($npc->x, $npc->y, $shrine->x, $shrine->y) <= 8) {
                $npc->purpose = min(100, $npc->purpose + 10);
                $npc->safety = min(100, $npc->safety + 10);
            }
        }

        $this->logEvent($tick, "a warm light emanated from {$shrine->name} — nearby villagers felt at peace");
    }

    private function brawl(int $tick): void
    {
        $tavern = $this->ticker->places->firstWhere('subtype', 'tavern');
        if ($tavern === null) {
            return;
        }

        $patrons = $this->ticker->npcById->filter(
            fn (SimNpc $npc) => $npc->place_id === $tavern->id && $npc->current_action !== 'dead'
        )->values();

        if ($patrons->count() < 2) {
            return;
        }

        /** @var SimNpc $instigator */
        $instigator = $patrons->sortBy('agreeableness')->first();
        /** @var SimNpc $defender */
        $defender = $patrons->filter(fn (SimNpc $n) => $n->id !== $instigator->id)->random();

        $instigator->safety = max(0, $instigator->safety - 10);
        $defender->safety = max(0, $defender->safety - 10);
        $instigator->social_need = min(100, $instigator->social_need + 5);

        $this->ticker->modifyRelationship($defender->id, $instigator->id, -8, 10, 'brawl', $tick);
        $this->ticker->modifyRelationship($instigator->id, $defender->id, -5, 5, 'brawl', $tick);

        $this->ticker->log(
            $instigator, $tick, 'combat', 'attack', null, $tavern->id,
            "started a brawl with {$defender->name} at {$tavern->name}"
        );
        $this->ticker->log(
            $defender, $tick, 'combat', 'defend', null, $tavern->id,
            "got into a brawl with {$instigator->name} at {$tavern->name}"
        );
    }

    private function logEvent(int $tick, string $description): void
    {
        $now = now();
        $this->ticker->actionBatch[] = [
            'type'             => 'event',
            'verb'             => 'event',
            'source_npc_id'    => $this->ticker->npcById->first()->id,
            'target_npc_id'    => null,
            'target_object_id' => null,
            'place_id'         => null,
            'tick'             => $tick,
            'duration'         => 1,
            'difficulty'       => 0,
            'outcome'          => 'success',
            'status'           => 'done',
            'description'      => "[EVENT] {$description}",
            'created_at'       => $now,
            'updated_at'       => $now,
        ];
    }
}
