<?php

namespace App\Services\Simulation\Handler;

use App\Models\Simulation\SimNpc;
use App\Models\Simulation\SimObject;
use App\Services\Simulation\Ticker;

class BehaviorHandler
{
    private const SHIRK_CONSCIENTIOUSNESS_MAX = 4;
    private const HELP_AGREEABLENESS_MIN = 7;
    private const STEAL_AGREEABLENESS_MAX = 4;
    private const STEAL_DESPERATE_HUNGER = 25;
    private const HELP_RADIUS = 6;
    private const STEAL_RADIUS = 8;
    private const MEND_HYGIENE_GAIN = 15;
    private const PRAY_PURPOSE_GAIN = 12;
    private const PRAY_SAFETY_GAIN = 6;
    private const HELP_PURPOSE_GAIN = 8;
    private const HELP_SOCIAL_GAIN = 10;
    private const CHARITY_PURPOSE_GAIN = 15;

    public function __construct(private Ticker $ticker) {}

    public function shouldShirkWork(SimNpc $npc): bool
    {
        if ($npc->conscientiousness > self::SHIRK_CONSCIENTIOUSNESS_MAX) {
            return false;
        }
        return $npc->rest < 40 || $npc->hunger < 40;
    }

    public function selfCare(SimNpc $npc, int $tick): void
    {
        if ($npc->hunger <= $npc->rest) {
            $this->ticker->survival()->tryEat($npc, $tick);
            return;
        }
        $this->ticker->survival()->trySleep($npc, $tick);
    }

    public function shouldHelpOthers(SimNpc $npc): bool
    {
        if ($npc->agreeableness < self::HELP_AGREEABLENESS_MIN) {
            return false;
        }
        if ($npc->hunger < 55 || $npc->thirst < 55 || $npc->rest < 55) {
            return false;
        }
        return true;
    }

    public function tryHelp(SimNpc $npc, int $tick): bool
    {
        $sufferer = $this->ticker->npcById
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
            $npc->x += $this->ticker->step($npc->x, $sufferer->x);
            $npc->y += $this->ticker->step($npc->y, $sufferer->y);
            $npc->place_id = $this->ticker->placeAt($npc->x, $npc->y);
            $npc->current_action = 'walking';
            $npc->current_action_target = $sufferer->name;
            $this->ticker->log(
                $npc, $tick, 'social', 'walk_to', null, $npc->place_id,
                "rushing to help {$sufferer->name}"
            );
            return true;
        }

        // Donate spare food to hungry neighbor
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
                $this->ticker->log(
                    $npc, $tick, 'social', 'give', $spare->id, $npc->place_id,
                    "gave {$spare->name} to {$sufferer->name} (charity)"
                );
                $this->ticker->modifyRelationship($sufferer->id, $npc->id, 20, -5, 'received_food', $tick);
                return true;
            }
        }

        // Keep company — both sides gain social, helper gains purpose
        $npc->purpose = min(100, $npc->purpose + self::HELP_PURPOSE_GAIN);
        $sufferer->social_need = min(100, $sufferer->social_need + self::HELP_SOCIAL_GAIN);
        $npc->current_action = 'helping';
        $npc->current_action_target = $sufferer->name;
        $this->ticker->log(
            $npc, $tick, 'social', 'greet', null, $npc->place_id,
            "kept company with {$sufferer->name}"
        );
        $this->ticker->modifyRelationship($sufferer->id, $npc->id, 5, 0, 'companionship', $tick);
        $this->ticker->modifyRelationship($npc->id, $sufferer->id, 5, 0, 'companionship', $tick);
        return true;
    }

    public function trySteal(SimNpc $npc, int $tick): bool
    {
        // Night loosens moral inhibitions (+1 to agreeableness gate)
        $nightBonus = $this->ticker->isNightTime() ? 1 : 0;
        $amoral = $npc->agreeableness <= (self::STEAL_AGREEABLENESS_MAX + $nightBonus);
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
            ->filter(function (SimObject $o) use ($npc) {
                // Skip targets whose owners we fear (learned from past failure)
                $rel = $this->ticker->getRelationship($npc->id, $o->owner_npc_id);
                return !$rel || $rel->fear <= 30;
            })
            ->sortBy(fn (SimObject $o) => abs($o->x - $npc->x) + abs($o->y - $npc->y))
            ->first();

        if (!$target) {
            return false;
        }

        $victim = $this->ticker->npcById[$target->owner_npc_id] ?? null;
        if (!$victim || $victim->current_action === 'dead') {
            $this->completeTheft($npc, $target, null, $tick, silent: true);
            return true;
        }

        $dist = abs($target->x - $npc->x) + abs($target->y - $npc->y);
        if ($dist > 1) {
            $npc->x += $this->ticker->step($npc->x, $target->x);
            $npc->y += $this->ticker->step($npc->y, $target->y);
            $npc->place_id = $this->ticker->placeAt($npc->x, $npc->y);
            $npc->current_action = 'sneaking';
            $npc->current_action_target = $target->name;
            $this->ticker->log(
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

        // Caught — safety hit on thief, purpose hit on victim
        $npc->safety = max(0, $npc->safety - 20);
        $victim->purpose = max(0, $victim->purpose - 10);
        $npc->current_action = 'fleeing';
        $npc->current_action_target = $victim->name;
        $this->ticker->log(
            $npc, $tick, 'combat', 'steal', $target->id, $npc->place_id,
            "botched theft of {$target->name} — caught by {$victim->name}"
        );
        $this->ticker->modifyRelationship($victim->id, $npc->id, -20, 0, 'caught_thief', $tick);
        $this->ticker->modifyRelationship($npc->id, $victim->id, 0, 15, 'caught_stealing', $tick);
        return true;
    }

    public function pray(SimNpc $npc, int $tick): bool
    {
        $shrine = $this->ticker->places->firstWhere('subtype', 'shrine');
        if (!$shrine) {
            return false;
        }

        $distToShrine = abs($shrine->x - $npc->x) + abs($shrine->y - $npc->y);
        if ($distToShrine > 12) {
            return false;
        }

        if ($npc->place_id !== $shrine->id) {
            $this->ticker->walkTowardsPlace($npc, $shrine, $tick, 'shrine');
            return true;
        }

        $before = $npc->purpose;
        $npc->purpose = min(100, $npc->purpose + self::PRAY_PURPOSE_GAIN);
        $npc->safety = min(100, $npc->safety + self::PRAY_SAFETY_GAIN);
        $gain = $npc->purpose - $before;
        $npc->current_action = 'praying';
        $npc->current_action_target = $shrine->name;
        $this->ticker->log(
            $npc, $tick, 'social', 'pray', null, $shrine->id,
            "prayed at {$shrine->name} (purpose +{$gain})"
        );
        return true;
    }

    public function mend(SimNpc $npc, int $tick): bool
    {
        $before = $npc->hygiene;
        $npc->hygiene = min(100, $npc->hygiene + self::MEND_HYGIENE_GAIN);
        $gain = $npc->hygiene - $before;
        $npc->current_action = 'mending';
        $npc->current_action_target = null;
        $this->ticker->log(
            $npc, $tick, 'crafting', 'repair', null, $npc->place_id,
            "mended clothes and washed (hygiene +{$gain})"
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
        $this->ticker->log(
            $thief, $tick, 'combat', 'steal', $item->id, $thief->place_id,
            "stole {$item->name} {$tag}"
        );

        if ($victim && !$silent) {
            $victim->purpose = max(0, $victim->purpose - 5);
            $this->ticker->log(
                $victim, $tick, 'combat', 'observe', $item->id, $victim->place_id,
                "lost {$item->name} to a thief"
            );
            $this->ticker->modifyRelationship($victim->id, $thief->id, -30, 20, 'theft_victim', $tick);
        }
    }
}
