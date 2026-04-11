<?php

namespace Database\Seeders;

use App\Models\Simulation\SimNpc;
use App\Models\Simulation\SimObject;
use App\Models\Simulation\SimPlace;
use App\Models\Simulation\SimState;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SimulationSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('sim_actions')->delete();
        DB::table('sim_objects')->delete();
        DB::table('sim_npcs')->delete();
        DB::table('sim_places')->delete();
        DB::table('sim_state')->delete();

        SimState::create(['tick' => 0, 'time_of_day' => 'morning', 'weather' => 'clear']);

        $places = $this->createPlaces();
        $npcs = $this->createNpcs($places);
        $this->createObjects($places, $npcs);
    }

    /**
     * @return array<string, SimPlace>
     */
    private function createPlaces(): array
    {
        $defs = [
            // public
            'square'          => ['name' => 'Market Square',             'type' => 'square',   'subtype' => 'town_square', 'x' => 28, 'y' => 26, 'w' => 6, 'h' => 4, 'scale' => 'large',  'terrain' => 'paved',  'prosperity' => 7],
            'well'            => ['name' => 'Old Well',                  'type' => 'water',    'subtype' => 'well',        'x' => 30, 'y' => 32, 'w' => 1, 'h' => 1, 'scale' => 'tiny',   'terrain' => 'stone',  'prosperity' => 5],
            'west_well'       => ['name' => 'Sunhold Well',              'type' => 'water',    'subtype' => 'well',        'x' => 15, 'y' => 23, 'w' => 1, 'h' => 1, 'scale' => 'tiny',   'terrain' => 'stone',  'prosperity' => 3],
            'east_well'       => ['name' => 'Thornvale Well',            'type' => 'water',    'subtype' => 'well',        'x' => 44, 'y' => 23, 'w' => 1, 'h' => 1, 'scale' => 'tiny',   'terrain' => 'stone',  'prosperity' => 3],
            'south_stream'    => ['name' => 'Southford Stream',          'type' => 'water',    'subtype' => 'stream',      'x' => 14, 'y' => 46, 'w' => 2, 'h' => 1, 'scale' => 'tiny',   'terrain' => 'water',  'prosperity' => 2],
            'hunters_spring'  => ['name' => "Huntsman's Spring",         'type' => 'water',    'subtype' => 'pond',        'x' => 50, 'y' => 42, 'w' => 1, 'h' => 1, 'scale' => 'tiny',   'terrain' => 'water',  'prosperity' => 2],

            // household workplaces
            'farm_sunhold'    => ['name' => 'Sunhold Farm',              'type' => 'building', 'subtype' => 'farm',        'x' => 10, 'y' => 20, 'w' => 5, 'h' => 5, 'scale' => 'medium', 'terrain' => 'grass',  'prosperity' => 5],
            'farm_thornvale'  => ['name' => 'Thornvale Farm',            'type' => 'building', 'subtype' => 'farm',        'x' => 45, 'y' => 20, 'w' => 5, 'h' => 5, 'scale' => 'medium', 'terrain' => 'grass',  'prosperity' => 5],
            'hunters_lodge'   => ['name' => "Huntsman's Lodge",          'type' => 'building', 'subtype' => 'home',        'x' => 48, 'y' => 40, 'w' => 2, 'h' => 2, 'scale' => 'small',  'terrain' => 'dirt',   'prosperity' => 4],
            'fishers_hut'     => ['name' => "Fisher's Hut",              'type' => 'building', 'subtype' => 'hovel',       'x' => 12, 'y' => 45, 'w' => 2, 'h' => 2, 'scale' => 'tiny',   'terrain' => 'wood',   'prosperity' => 3],

            // craftsman workplaces
            'forge'           => ['name' => "Brann's Forge",             'type' => 'building', 'subtype' => 'forge',       'x' => 34, 'y' => 27, 'w' => 3, 'h' => 3, 'scale' => 'small',  'terrain' => 'stone',  'prosperity' => 6],
            'weavery'         => ['name' => 'Loom House',                'type' => 'building', 'subtype' => 'shop',        'x' => 24, 'y' => 28, 'w' => 2, 'h' => 2, 'scale' => 'small',  'terrain' => 'wood',   'prosperity' => 5],
            'bakery'          => ['name' => 'Crustmonger Bakery',        'type' => 'building', 'subtype' => 'bakery',      'x' => 38, 'y' => 30, 'w' => 2, 'h' => 2, 'scale' => 'small',  'terrain' => 'stone',  'prosperity' => 5],
            'butchery'        => ['name' => 'Red Butchery',              'type' => 'building', 'subtype' => 'butchery',    'x' => 22, 'y' => 30, 'w' => 2, 'h' => 2, 'scale' => 'small',  'terrain' => 'wood',   'prosperity' => 5],
            'cobblery'        => ['name' => 'Stitchery Cobbler',         'type' => 'building', 'subtype' => 'shop',        'x' => 38, 'y' => 22, 'w' => 2, 'h' => 2, 'scale' => 'small',  'terrain' => 'wood',   'prosperity' => 5],
            'pottery'         => ['name' => 'Clayshaper Kiln',           'type' => 'building', 'subtype' => 'shop',        'x' => 24, 'y' => 22, 'w' => 2, 'h' => 2, 'scale' => 'small',  'terrain' => 'stone',  'prosperity' => 4],

            // service workplaces
            'tavern'          => ['name' => 'The Crooked Hare',          'type' => 'building', 'subtype' => 'tavern',      'x' => 28, 'y' => 33, 'w' => 4, 'h' => 3, 'scale' => 'medium', 'terrain' => 'wood',   'prosperity' => 6],
            'apothecary'      => ['name' => "Alfrom's Apothecary",       'type' => 'building', 'subtype' => 'apothecary',  'x' => 33, 'y' => 33, 'w' => 2, 'h' => 2, 'scale' => 'small',  'terrain' => 'wood',   'prosperity' => 4],
            'shrine'          => ['name' => 'Shrine of the Pale Watcher','type' => 'building', 'subtype' => 'shrine',      'x' => 20, 'y' => 33, 'w' => 3, 'h' => 3, 'scale' => 'small',  'terrain' => 'stone',  'prosperity' => 3],

            // rent extractor workplace
            'barracks'        => ['name' => 'Town Barracks',             'type' => 'building', 'subtype' => 'barracks',    'x' => 40, 'y' => 36, 'w' => 4, 'h' => 3, 'scale' => 'medium', 'terrain' => 'stone',  'prosperity' => 6],
        ];

        $places = [];
        foreach ($defs as $slug => $d) {
            $places[$slug] = SimPlace::create([
                'name'       => $d['name'],
                'type'       => $d['type'],
                'subtype'    => $d['subtype'],
                'scale'      => $d['scale'],
                'condition'  => 'kept',
                'terrain'    => $d['terrain'] ?? 'dirt',
                'x'          => $d['x'],
                'y'          => $d['y'],
                'width'      => $d['w'],
                'height'     => $d['h'],
                'prosperity' => $d['prosperity'] ?? 5,
            ]);
        }

        return $places;
    }

    /**
     * @param array<string, SimPlace> $places
     * @return array<string, SimNpc>
     */
    private function createNpcs(array $places): array
    {
        // [key, name, race, gender, age, build, profession, workplaceSlug]
        $roster = [
            // --- household producers (6) ---
            ['harn',    'Harn Turnipdigger',    'human',    'male',   41, 'stocky',  'farmer',        'farm_sunhold'],
            ['grob',    'Grob Mudfoot',         'half_orc', 'male',   35, 'burly',   'farmer',        'farm_sunhold'],
            ['lida',    'Lida Greenrow',        'human',    'female', 38, 'average', 'farmer',        'farm_thornvale'],
            ['runa',    'Runa Oxback',          'human',    'female', 33, 'stocky',  'shepherd',      'farm_thornvale'],
            ['pell',    'Pell Mossfoot',        'halfling', 'male',   44, 'lean',    'hunter',        'hunters_lodge'],
            ['tessa',   'Tessa Brightleaf',     'human',    'female', 29, 'lean',    'fisher',        'fishers_hut'],

            // --- market craftsmen (6) ---
            ['brann',   'Brann Iron-Hand',      'dwarf',    'male',   47, 'burly',   'blacksmith',    'forge'],
            ['mira',    'Mira Loomwarden',      'human',    'female', 34, 'average', 'weaver',        'weavery'],
            ['orlo',    'Orlo Crustmonger',     'halfling', 'male',   37, 'stocky',  'baker',         'bakery'],
            ['vosk',    'Vosk Redhand',         'human',    'male',   45, 'burly',   'butcher',       'butchery'],
            ['anissa',  'Anissa Stitchery',     'elf',      'female', 62, 'lean',    'cobbler',       'cobblery'],
            ['ros',     'Ros Clayshaper',       'human',    'male',   39, 'average', 'potter',        'pottery'],

            // --- service providers (3) ---
            ['velka',   'Velka Brassbelly',     'human',    'female', 42, 'stocky',  'innkeeper',     'tavern'],
            ['alfrom',  'Alfrom Salve-Seller',  'human',    'male',   38, 'lean',    'alchemist',     'apothecary'],
            ['dessra',  'Dessra Coldhand',      'half_orc', 'female', 29, 'stocky',  'gravedigger',   'shrine'],

            // --- rent extractors (3) ---
            ['ibelin',  'Hierarch Ibelin',      'human',    'male',   58, 'lean',    'priest',        'shrine'],
            ['cren',    'Cren Ashleigh',        'human',    'male',   22, 'lean',    'guard',         'barracks'],
            ['halvar',  'Reeve Halvar',         'human',    'male',   51, 'average', 'tax_collector', 'square'],

            // --- dependents (2) ---
            ['oma',     'Oma Threadbare',       'human',    'female', 63, 'frail',   'beggar',        'square'],
            ['hodd',    'Hodd Ratstop',         'halfling', 'male',   51, 'frail',   'rat_catcher',   'square'],
        ];

        $npcs = [];
        foreach ($roster as [$key, $name, $race, $gender, $age, $build, $profession, $placeSlug]) {
            $npcs[$key] = SimNpc::create(
                $this->npcData($places, $name, $race, $gender, $age, $build, $profession, $placeSlug)
            );
        }

        return $npcs;
    }

    /**
     * @param array<string, SimPlace> $places
     * @return array<string, mixed>
     */
    private function npcData(
        array $places,
        string $name,
        string $race,
        string $gender,
        int $age,
        string $build,
        string $profession,
        string $placeSlug,
    ): array {
        $archetype = config('simulation_roles.archetype_for_profession.' . $profession);
        if (!$archetype) {
            throw new \RuntimeException("No archetype mapping for profession: {$profession}");
        }
        $wealth = config('simulation_roles.starting_wealth.' . $archetype);

        $place = $places[$placeSlug];
        $hasWorkplace = $archetype !== 'dependent';

        $x = $place->x + random_int(0, max(0, $place->width - 1));
        $y = $place->y + random_int(0, max(0, $place->height - 1));

        $con = random_int(10, 15);
        $hp = $con * 2 + 10;

        return [
            'name'              => $name,
            'race'              => $race,
            'gender'            => $gender,
            'age'               => $age,
            'build'             => $build,
            'profession'        => $profession,
            'archetype'         => $archetype,
            'social_class'      => $this->classForArchetype($archetype),
            'wealth'            => $wealth,

            'openness'          => random_int(3, 8),
            'conscientiousness' => random_int(4, 9),
            'extraversion'      => random_int(3, 8),
            'agreeableness'     => random_int(3, 8),
            'neuroticism'       => random_int(2, 7),

            'str'               => random_int(9, 15),
            'dex'               => random_int(9, 15),
            'con'               => $con,
            'int'               => random_int(9, 15),
            'hp'                => $hp,
            'max_hp'            => $hp,

            'hunger'            => random_int(70, 95),
            'thirst'            => random_int(70, 95),
            'rest'              => random_int(70, 95),
            'hygiene'           => random_int(60, 90),
            'safety'            => random_int(70, 95),
            'social_need'       => random_int(60, 90),
            'purpose'           => random_int(55, 85),

            'mood'              => 'content',
            'current_action'    => 'idle',
            'current_action_target' => null,

            'x'                 => $x,
            'y'                 => $y,
            'place_id'          => $place->id,

            'workplace_id'      => $hasWorkplace ? $place->id : null,
            'last_work_tick'    => 0,
        ];
    }

    private function classForArchetype(string $archetype): string
    {
        return match ($archetype) {
            'household_producer' => 'commoner',
            'market_craftsman'   => 'comfortable',
            'service_provider'   => 'commoner',
            'rent_extractor'     => 'wealthy',
            'dependent'          => 'destitute',
            default              => 'commoner',
        };
    }

    /**
     * @param array<string, SimPlace> $places
     * @param array<string, SimNpc> $npcs
     */
    private function createObjects(array $places, array $npcs): void
    {
        // Beds — rest affordance, persistent furniture
        $bedPlaces = [
            'tavern', 'shrine', 'barracks',
            'farm_sunhold', 'farm_thornvale', 'hunters_lodge', 'fishers_hut',
            'forge', 'weavery', 'bakery', 'butchery', 'cobblery', 'pottery', 'apothecary',
        ];
        foreach ($bedPlaces as $slug) {
            $this->bed($places[$slug]);
        }

        // Owned signature tools (flavor — not required for ticker)
        $tools = [
            ['brann',   'iron hammer',     'tool',   'hammer',        'iron'],
            ['mira',    'loom shuttle',    'tool',   'loom_shuttle',  'wood'],
            ['dessra',  'grave shovel',    'tool',   'shovel',        'wood'],
            ['pell',    'hunter bow',      'weapon', 'shortbow',      'wood'],
            ['tessa',   'fishing rod',     'tool',   'fishing_rod',   'wood'],
            ['cren',    'guard sword',     'weapon', 'shortsword',    'steel'],
            ['vosk',    'butcher cleaver', 'tool',   'hammer',        'iron'],
            ['ros',     'potter wheel',    'tool',   'spindle',       'wood'],
            ['anissa',  'cobbler awl',     'tool',   'awl',           'iron'],
            ['orlo',    'bread paddle',    'tool',   'spindle',       'wood'],
        ];
        foreach ($tools as [$key, $name, $type, $subtype, $material]) {
            SimObject::create([
                'name'         => $name,
                'type'         => $type,
                'subtype'      => $subtype,
                'material'     => $material,
                'quality'      => 'common',
                'wear'         => 'used',
                'weight'       => 2,
                'value'        => 4,
                'owner_npc_id' => $npcs[$key]->id,
            ]);
        }
    }

    private function bed(SimPlace $place): void
    {
        SimObject::create([
            'name'        => 'straw bed',
            'type'        => 'furniture',
            'subtype'     => 'bed',
            'material'    => 'wood',
            'quality'     => 'common',
            'wear'        => 'used',
            'weight'      => 20,
            'size'        => 3,
            'value'       => 4,
            'place_id'    => $place->id,
            'x'           => $place->x + intdiv($place->width, 2),
            'y'           => $place->y + intdiv($place->height, 2),
            'affordances' => ['rest' => 45],
        ]);
    }
}
