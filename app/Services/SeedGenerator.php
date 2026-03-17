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

    public function traitSeed(string $field): array
    {
        $seed = match ($field) {
            'personality' => $this->personalitySeed(),
            'traits' => $this->traitsSeed(),
            'trauma' => $this->traumaSeed(),
            'hobbies' => $this->hobbiesSeed(),
            'routines' => $this->routinesSeed(),
            'job' => $this->jobSeed(),
            'skills' => $this->skillsSeed(),
            'goals' => $this->goalsSeed(),
            'secrets' => $this->secretsSeed(),
            'limits' => $this->limitsSeed(),
            'intentions' => $this->intentionsSeed(),
            default => [],
        };

        if (empty($seed)) {
            return [];
        }

        // First key is always the core — never pruned
        $keys = array_keys($seed);
        $core = array_shift($keys);

        if (empty($keys)) {
            return $seed;
        }

        shuffle($keys);
        $count = random_int(0, count($keys));
        $kept = array_slice($keys, 0, $count);
        $kept[] = $core;

        return array_intersect_key($seed, array_flip($kept));
    }

    private function personalitySeed(): array
    {
        $demeanors = [
            'talks too much when nervous', 'goes quiet in groups', 'smiles at the wrong moments',
            'laughs things off even when hurt', 'gets loud when cornered', 'overly polite to strangers',
            'blunt to the point of rude', 'fidgets constantly', 'stares too long before answering',
            'changes subject when things get personal', 'agrees with everyone then does what they want',
            'cracks jokes nobody asked for', 'mumbles when unsure', 'speaks in questions',
        ];

        $comforts = [
            'being alone with a task', 'having a plan', 'being around animals', 'a specific drink or food',
            'routine and repetition', 'being useful to someone', 'high places', 'walking at night',
            'sharpening or cleaning things', 'humming the same tune', 'counting things',
            'being near water', 'keeping hands busy', 'organizing belongings',
        ];

        $irritants = [
            'being interrupted', 'loud chewing', 'people who lie badly', 'unsolicited advice',
            'being pitied', 'waiting with nothing to do', 'people touching their things',
            'being called the wrong name', 'authority pulling rank', 'dishonesty about small things',
            'people who give up easily', 'broken promises', 'whining', 'being watched while working',
        ];

        $underPressure = [
            'shuts down completely', 'becomes hyper-focused and cold', 'lashes out at the nearest person',
            'makes reckless decisions fast', 'freezes and needs a push', 'gets eerily calm',
            'talks faster and louder', 'retreats and hides', 'doubles down on whatever they were doing',
            'looks for someone to follow', 'starts planning obsessively', 'picks a fight',
        ];

        return [
            'demeanor' => $demeanors[array_rand($demeanors)],
            'comfort' => $comforts[array_rand($comforts)],
            'irritant' => $irritants[array_rand($irritants)],
            'under_pressure' => $underPressure[array_rand($underPressure)],
        ];
    }

    private function traitsSeed(): array
    {
        $positive = [
            'honest even when it costs them', 'loyal past the point of reason', 'patient with children and animals',
            'generous with food', 'remembers small details about people', 'keeps promises literally',
            'first to volunteer for grunt work', 'calm in emergencies', 'forgives easily',
            'shares credit', 'admits mistakes quickly', 'defends people who aren\'t present',
        ];

        $negative = [
            'holds grudges for years', 'steals small things out of habit', 'lies about unimportant things',
            'jealous of anyone doing better', 'lazy when nobody is watching', 'takes credit for group work',
            'gossips to feel important', 'avoids confrontation until they explode', 'cheats at games',
            'breaks things when frustrated', 'never apologizes first', 'keeps score of favors',
        ];

        $quirks = [
            'collects teeth', 'names their tools', 'won\'t sleep facing a door', 'eats flowers',
            'talks to the moon', 'refuses to step on cracks', 'always sits facing the exit',
            'keeps a tally of something nobody understands', 'sleeps with one eye open',
            'hums before making a decision', 'taps surfaces three times', 'won\'t eat red food',
        ];

        return [
            'positive' => $positive[array_rand($positive)],
            'negative' => $negative[array_rand($negative)],
            'quirk' => $quirks[array_rand($quirks)],
        ];
    }

    private function traumaSeed(): array
    {
        $events = [
            'watched a building collapse with people inside', 'was left behind during an evacuation',
            'killed someone by accident', 'survived something nobody else did', 'was blamed for a fire',
            'found a body as a child', 'was locked in a cellar for days', 'saw a parent beg',
            'was betrayed by a mentor', 'caused a stampede', 'was bitten by something that talked',
            'woke up somewhere with no memory of how', 'was sold for a debt', 'failed to save a sibling',
        ];

        $copings = [
            'avoids the topic entirely, changes subject', 'talks about it too casually',
            'checks locks and exits obsessively', 'won\'t go near similar places',
            'drinks or smokes more than they should', 'keeps a specific object close at all times',
            'has a ritual they do every morning because of it', 'gets aggressive when reminded',
            'pretends it happened to someone else', 'has made peace with it, mostly',
        ];

        return [
            'event' => $events[array_rand($events)],
            'coping' => $copings[array_rand($copings)],
            'severity' => random_int(1, 10),
        ];
    }

    private function hobbiesSeed(): array
    {
        $hobbies = [
            'whittling small animals', 'fermenting things', 'pressing flowers', 'arm wrestling',
            'birdwatching', 'foraging mushrooms', 'collecting coins from different places',
            'sketching people without them knowing', 'fishing but never eating the fish',
            'stargazing', 'making rope', 'training a pet to do useless tricks',
            'competitive eating', 'cloud reading', 'stone stacking', 'mending strangers\' clothes',
        ];

        $count = random_int(1, 3);
        shuffle($hobbies);

        return [
            'hobbies' => implode(', ', array_slice($hobbies, 0, $count)),
            'dedication' => random_int(1, 10),
        ];
    }

    private function routinesSeed(): array
    {
        $times = ['morning', 'midday', 'evening', 'night'];

        $activities = [
            'walks the same route', 'checks on a specific person or place', 'prays or meditates',
            'cleans weapons or tools', 'writes in a journal', 'stretches and exercises',
            'visits a market stall', 'feeds a stray animal', 'sits in the same spot and watches people',
            'counts inventory', 'polishes boots', 'brews something specific',
        ];

        return [
            'time_of_day' => $times[array_rand($times)],
            'activity' => $activities[array_rand($activities)],
            'rigidity' => random_int(1, 10),
        ];
    }

    private function jobSeed(): array
    {
        $jobs = [
            'tanner', 'chandler', 'rat catcher', 'courier', 'debt collector',
            'gravedigger', 'fence (stolen goods)', 'scribe', 'bouncer', 'farrier',
            'leech collector', 'peat cutter', 'soap maker', 'nightsoil collector',
            'mule driver', 'quarryman', 'rope maker', 'tinker', 'fuller', 'ditcher',
        ];

        $attitudes = [
            'proud of the craft despite what others think', 'does it because nothing else pays',
            'inherited it and can\'t escape', 'genuinely loves it', 'ashamed and hides it from new people',
            'treats it as temporary but it\'s been years', 'obsessively good at it',
        ];

        return [
            'job' => $jobs[array_rand($jobs)],
            'attitude' => $attitudes[array_rand($attitudes)],
            'competence' => random_int(1, 10),
        ];
    }

    private function skillsSeed(): array
    {
        $skills = [
            'lockpicking', 'tracking', 'haggling', 'forgery', 'herbalism',
            'knot tying', 'animal handling', 'fire starting', 'lying convincingly',
            'reading lips', 'holding breath', 'climbing', 'stitching wounds',
            'navigation by stars', 'intimidation', 'pickpocketing', 'cooking',
            'trap making', 'swimming', 'bluffing at cards',
        ];

        $howLearned = [
            'trained by a master', 'self-taught through necessity', 'picked up in prison',
            'learned from a parent', 'stole the knowledge', 'forced to learn as punishment',
            'learned from a dead person\'s notes', 'taught by a rival', 'natural talent',
        ];

        $count = random_int(2, 4);
        shuffle($skills);

        return [
            'skills' => implode(', ', array_slice($skills, 0, $count)),
            'how_learned' => $howLearned[array_rand($howLearned)],
        ];
    }

    private function goalsSeed(): array
    {
        $goals = [
            'pay off a specific debt', 'find a missing person', 'get back something stolen',
            'earn enough to leave', 'prove someone wrong', 'get revenge on one person',
            'build something that lasts', 'be left alone', 'gain a title or position',
            'find out what really happened', 'protect a specific place', 'learn a forbidden skill',
            'outlive an enemy', 'earn forgiveness', 'destroy a specific object',
        ];

        $obstacles = [
            'no money', 'wrong reputation', 'the target is powerful', 'nobody believes them',
            'they lack a critical skill', 'someone they love is in the way', 'time is running out',
            'they don\'t know where to start', 'a past mistake keeps catching up',
        ];

        return [
            'goal' => $goals[array_rand($goals)],
            'obstacle' => $obstacles[array_rand($obstacles)],
            'urgency' => random_int(1, 10),
        ];
    }

    private function secretsSeed(): array
    {
        $secrets = [
            'killed someone and got away with it', 'is not who they claim to be',
            'owes money to dangerous people', 'can\'t read', 'has a child nobody knows about',
            'was exiled from their hometown', 'stole something important from an employer',
            'has a disease they\'re hiding', 'witnessed a crime and said nothing',
            'is addicted to something', 'betrayed a friend for money', 'has a fake identity',
            'is wanted in another region', 'made a deal they regret',
        ];

        $exposure = [
            'nobody knows', 'one person suspects', 'rumors are starting', 'someone has proof',
        ];

        $stakes = [
            'embarrassment', 'exile', 'death', 'losing someone they care about',
            'imprisonment', 'loss of livelihood',
        ];

        return [
            'secret' => $secrets[array_rand($secrets)],
            'exposure' => $exposure[array_rand($exposure)],
            'stakes_if_found' => $stakes[array_rand($stakes)],
        ];
    }

    private function limitsSeed(): array
    {
        $lines = [
            'won\'t hurt children', 'won\'t steal from the poor', 'won\'t break a sworn oath',
            'won\'t abandon a companion in danger', 'won\'t use poison', 'won\'t lie to a friend',
            'won\'t kill an unarmed person', 'won\'t work for the church', 'won\'t beg',
            'won\'t eat meat', 'won\'t enter a specific place', 'won\'t speak a certain name',
        ];

        $tested = ['never been tested', 'tested once and held', 'tested and almost broke', 'broke it once'];

        return [
            'line' => $lines[array_rand($lines)],
            'tested' => $tested[array_rand($tested)],
            'firmness' => random_int(1, 10),
        ];
    }

    private function intentionsSeed(): array
    {
        $targets = [
            'the local authority', 'a former partner', 'a specific merchant', 'their own family',
            'a religious order', 'whoever wronged them', 'the person they work for',
            'a childhood friend', 'a stranger who helped them once', 'themselves',
        ];

        $intents = [
            'protect at all costs', 'exploit for personal gain', 'watch and wait',
            'undermine quietly', 'escape from', 'earn trust of', 'betray when the time comes',
            'control through favors', 'make amends with',
        ];

        $timelines = ['immediate', 'soon', 'long term', 'when the right moment comes'];

        return [
            'toward' => $targets[array_rand($targets)],
            'intent' => $intents[array_rand($intents)],
            'timeline' => $timelines[array_rand($timelines)],
            'transparency' => random_int(1, 10),
        ];
    }

    public function generateOpenerSeed(): array
    {
        $activities = [
            'repairing a piece of equipment',
            'eating a meal alone',
            'haggling over a price',
            'waiting for someone who is late',
            'carrying something heavy across the street',
            'sharpening a blade',
            'reading a posted notice',
            'warming hands by a fire',
            'feeding scraps to a stray animal',
            'sorting through a bag of supplies',
            'patching a hole in clothing',
            'counting coins at a table',
            'watching a street performer',
            'cleaning mud off boots',
            'sketching something in a journal',
        ];

        $interruptions = [
            'someone bumps into them',
            'a loud crash nearby',
            'someone asks for directions',
            'an item rolls to their feet',
            'a child tugs at their sleeve',
            'a cart blocks the path',
            'someone calls out the wrong name',
            'a dog steals something',
            'a merchant offers a free sample',
            'someone drops a heavy crate',
            'rain starts suddenly',
            'a door slams open',
            'a bell rings unexpectedly',
            'smoke drifts from a nearby building',
            'two people start arguing loudly',
        ];

        $npcDemeanors = [
            'bored', 'friendly', 'annoyed', 'distracted', 'rushing',
            'tired', 'cheerful', 'suspicious', 'chatty', 'indifferent',
        ];

        $npcRoles = [
            'vendor', 'guard', 'laborer', 'courier', 'innkeeper',
            'street sweeper', 'stable hand', 'cook', 'fishmonger', 'porter',
            'carpenter', 'lamplighter', 'washerwoman', 'dockhand', 'errand boy',
        ];

        $timeOfDay = [
            'early morning, mist still clinging',
            'midday, sun high and harsh',
            'late afternoon, long shadows',
            'dusk, lanterns being lit',
            'overcast morning, drizzle',
        ];

        return [
            'fallback_activity' => $activities[array_rand($activities)],
            'interruption' => $interruptions[array_rand($interruptions)],
            'npc_demeanor' => $npcDemeanors[array_rand($npcDemeanors)],
            'npc_role' => $npcRoles[array_rand($npcRoles)],
            'time_of_day' => $timeOfDay[array_rand($timeOfDay)],
        ];
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