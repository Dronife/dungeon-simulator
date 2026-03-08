<?php

namespace App\Services;

class SeedGenerator
{
    public function generateStats(): array
    {
        $con = random_int(5, 19);
        $hp = $con * 2 + 10;
        return [
            'str' => random_int(5, 19),
            'dex' => random_int(5, 19),
            'con' => $con,
            'int' => random_int(5, 19),
            'wis' => random_int(5, 19),
            'cha' => random_int(5, 19),
            'hp' => $hp,
            'max_hp' => $hp,
            'trauma_severity' => random_int(1, 6),
            'goal_severity' => random_int(1, 6),
            'intention_severity' => random_int(1, 10),
            'personality_severity' => random_int(1, 10),
        ];
    }

    public function getAttributeCount(float $temperature): int
    {
        if ($temperature >= 0 && $temperature <= 0.2) {
            return random_int(1, 3);
        }

        if ($temperature >= 0.21 && $temperature <= .45) {
            return random_int(3, 6);
        }

        return random_int(7, 11);
    }

    public function getAttributeNames(int $amount): string
    {
        $attributes = ['personality', 'traits', 'trauma', 'hobbies', 'routines', 'job', 'skills', 'goals', 'secrets', 'limits', 'intentions'];

        shuffle($attributes);

        return implode(', ', array_slice($attributes, 0, $amount));
    }

    public function getPredefinedLoreStats(int $amount = 1): string
    {
        $lore = [];
        for ($i = 0; $i < $amount; $i++) {
            $lore[] = [
                'description' => random_int(1, 100),
                'known_how' => random_int(1, 100),
                'reason' => random_int(1, 100),
                'occurrence' => random_int(1, 100),
                'grounding' => random_int(1, 100),
                'chaos' => random_int(1, 100),
            ];

            array_merge($lore, $this->generateHookSeed());
        }

        return json_encode($lore);
    }

    public function generateHookSeed(): array
    {
        $subjects = [
            'blacksmith', 'beekeeper', 'river', 'cemetery', 'festival', 'bridge', 'twins', 'well',
            'lighthouse keeper', 'tannery', 'orphanage', 'aqueduct', 'bell tower', 'ferryman',
            'herbalist', 'quarry', 'windmill', 'midwife', 'granary', 'shrine',
        ];

        $actions = [
            'disappearing', 'multiplying', 'singing', 'reversing', 'fermenting', 'migrating',
            'refusing', 'petrifying', 'blooming', 'whispering', 'splitting', 'rusting',
            'growing', 'shrinking', 'freezing', 'echoing', 'bleeding', 'hatching',
            'orbiting', 'unraveling',
        ];

        $scales = [
            'one person', 'a family', 'a street', 'the whole town', 'the region',
            'a single building', 'a trade route', 'an underground network', 'a bloodline', 'a guild',
        ];

        $tones = [
            'comedic', 'ominous', 'mundane-gone-wrong', 'absurd', 'tragic',
            'petty', 'eerie', 'bureaucratic', 'heartwarming', 'paranoid',
        ];

        $catalysts = [
            'a bet gone wrong', 'a mistranslated scroll', 'a dying wish', 'a failed recipe',
            'an inheritance dispute', 'a broken oath', 'a seasonal shift', 'a returned exile',
            "a child's prank", 'an unpaid debt',
        ];

        $sensoryDetails = [
            'a smell no one can place', 'a sound only at night', 'a color that shouldn\'t exist',
            'a taste in the water', 'a warmth from underground', 'a shadow with no source',
            'a vibration in the walls', 'a silence that moves', 'a texture on every surface',
            'a light that follows',
        ];

        $complications = [
            'no one agrees it\'s happening', 'the obvious suspect is beloved',
            'fixing it breaks something else', 'it\'s technically legal',
            'someone is profiting from it', 'it happened before and was covered up',
            'the evidence keeps changing', 'only outsiders notice',
            'it\'s getting worse slowly', 'the solution requires something forbidden',
        ];

        $timeframes = [
            'started this morning', 'been going on for weeks', 'happens every full moon',
            'began when someone died', 'only during rain', 'since the new mayor arrived',
            'overnight without warning', 'creeping in over years', 'every third day exactly',
            'nobody remembers when it started',
        ];

        $types = ['threat', 'rumor', 'faction', 'local_color'];

        $clues = [
            'common knowledge', 'whispered rumors', 'no one talks about it',
            'only outsiders notice', 'speculations', 'one person claims to know',
            'hard to miss', 'people avoid the topic', 'conflicting stories',
            'noticed recently',
        ];

        $stakes = [
            'trade route shuts down', 'people start leaving', 'food supply threatened',
            'trust between neighbors breaks', 'someone powerful takes notice',
            'the problem spreads', 'an innocent gets blamed', 'prices skyrocket',
            'old grudges resurface', 'a ritual opportunity is lost',
        ];

        return [
            'type' => $types[array_rand($types)],
            'subject' => $subjects[array_rand($subjects)],
            'action' => $actions[array_rand($actions)],
            'scale' => $scales[array_rand($scales)],
            'tone' => $tones[array_rand($tones)],
            'catalyst' => $catalysts[array_rand($catalysts)],
            'sensory_detail' => $sensoryDetails[array_rand($sensoryDetails)],
            'complication' => $complications[array_rand($complications)],
            'timeframe' => $timeframes[array_rand($timeframes)],
            'clue' => $clues[array_rand($clues)],
            'stakes' => $stakes[array_rand($stakes)],
        ];
    }

    public function generateHookSeeds(int $count = 3): array
    {
        $seeds = [];
        for ($i = 0; $i < $count; $i++) {
            $seeds[] = $this->generateHookSeed();
        }

        return $seeds;
    }

    public function worldExplanationPredefined(): string
    {
        $world = [
            'magic' => random_int(1, 100),
            'gods' => random_int(1, 100),
            'physics' => random_int(1, 100),
            'specific_rules' => random_int(1, 100),
            'current_location' => random_int(1, 100),
        ];

        return json_encode($world);
    }

    public function getPredefinedCharacter(array $character = []): string
    {
        if (!empty($character)) {
            return json_encode($character);
        }

        $races = [
            'Human', 'Elf', 'Dwarf', 'Halfling', 'Dragonborn', 'Gnome',
            'Half-Elf', 'Half-Orc', 'Tiefling', 'Aarakocra', 'Genasi',
            'Gith', 'Tabaxi', 'Warforged'
        ];

        $race = $races[array_rand($races)];
        $muscle = random_int(1, 10);
        $fat = random_int(1, 10);
        $beauty = random_int(1, 10);

        $bodyType = $this->calculateBodyType($muscle, $fat);
        $face = $this->calculateFace($beauty);
        $distinctiveFeature = $this->getRaceFeature($race);

        return json_encode([
            'race' => $race,
            'muscle_index' => $muscle,
            'fat_index' => $fat,
            'beauty_index' => $beauty,
            'body_type' => $bodyType,
            'facial_structure' => $face,
            'distinctive_trait' => $distinctiveFeature,
            'overall_summary' => "A {$bodyType} {$race} with {$face} features and {$distinctiveFeature}."
        ]);
    }

    private function calculateBodyType(int $muscle, int $fat): string
    {
        if ($muscle <= 3) {
            if ($fat <= 3) return 'Gaunt / Skeletal';
            if ($fat <= 6) return 'Scrawny / Soft';
            return 'Doughy / Obese';
        }

        if ($muscle <= 7) {
            if ($fat <= 3) return 'Lean / Wiry';
            if ($fat <= 6) return 'Average / Proportionate';
            return 'Chubby / Stout';
        }

        if ($fat <= 3) return 'Ripped / Vascular';
        if ($fat <= 6) return 'Athletic / Muscular';
        return 'Burly / Powerlifter Build';
    }

    private function calculateFace(int $beauty): string
    {
        if ($beauty <= 3) {
            $flaws = ['asymmetrical features', 'pockmarked skin', 'a broken nose', 'deep scarring'];
            return 'Unattractive, defined by ' . $flaws[array_rand($flaws)];
        }

        if ($beauty <= 7) {
            return 'Plain / Common looking';
        }

        $features = ['striking symmetry', 'piercing eyes', 'chiseled jawline', 'ethereal elegance'];
        return 'Stunning, defined by ' . $features[array_rand($features)];
    }

    private function getRaceFeature(string $race): string
    {
        return match ($race) {
            'Dragonborn' => 'scales of ' . ['bronze', 'green', 'red', 'silver', 'gold'][rand(0, 4)],
            'Tiefling' => 'horns that are ' . ['curled like a ram', 'straight and pointed', 'broken'][rand(0, 2)],
            'Warforged' => 'plating made of ' . ['polished steel', 'rusted iron', 'darkwood', 'stone'][rand(0, 3)],
            'Tabaxi' => 'fur with a ' . ['calico', 'leopard spot', 'solid black', 'striped'][rand(0, 3)] . ' pattern',
            'Aarakocra' => 'plumage resembling a ' . ['eagle', 'parrot', 'owl', 'crow'][rand(0, 3)],
            default => 'distinctive ' . ['tattoos', 'scars', 'piercings', 'eye color'][rand(0, 3)],
        };
    }
}