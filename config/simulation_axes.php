<?php

/*
|--------------------------------------------------------------------------
| Simulation Axes
|--------------------------------------------------------------------------
|
| Every entity (npc, object, place, action) is defined by orthogonal axes.
| Axes are either enumerated (string lists below) or numeric (1-10 ranges
| declared inline in the seeder/migration).
|
| Combinatorial logic: ~200 object subtypes × 8 materials × 5 qualities
| × 5 wear states = 40,000 distinct items from ~218 strings.
|
| Strings here are CANONICAL — code references them, never re-types them.
|
*/

return [

    /*
    |--------------------------------------------------------------------------
    | NPC axes
    |--------------------------------------------------------------------------
    */
    'npc' => [
        'race' => [
            'human', 'elf', 'dwarf', 'halfling', 'gnome', 'half_orc', 'tiefling',
            'dragonborn', 'goblin', 'kobold', 'orc', 'lizardfolk',
        ],

        'gender' => ['male', 'female', 'other'],

        'build' => ['frail', 'lean', 'average', 'stocky', 'burly', 'hulking'],

        'profession' => [
            'farmer', 'blacksmith', 'tanner', 'baker', 'butcher', 'miller',
            'carpenter', 'mason', 'cooper', 'fletcher', 'bowyer', 'weaver',
            'tailor', 'cobbler', 'innkeeper', 'barkeep', 'cook', 'brewer',
            'priest', 'acolyte', 'healer', 'midwife', 'herbalist', 'alchemist',
            'scribe', 'cartographer', 'merchant', 'peddler', 'fence',
            'guard', 'soldier', 'mercenary', 'bandit', 'thief', 'cutpurse',
            'gravedigger', 'rat_catcher', 'nightsoil_collector', 'beggar',
            'minstrel', 'jester', 'noble', 'steward', 'hunter', 'trapper',
            'fisher', 'sailor', 'porter', 'stablehand', 'shepherd', 'goatherd',
            'mayor', 'constable', 'tax_collector', 'scholar', 'apprentice',
        ],

        'social_class' => [
            'destitute', 'poor', 'commoner', 'comfortable', 'wealthy', 'noble',
        ],

        // Five OCEAN traits stored as integers 1-10:
        //   openness, conscientiousness, extraversion, agreeableness, neuroticism

        // Six core needs stored as integers 0-100 (lower = more urgent):
        //   hunger, thirst, rest, hygiene, safety, social_need, purpose

        'mood' => [
            'content', 'happy', 'excited', 'bored', 'tired', 'anxious',
            'angry', 'sad', 'afraid', 'curious', 'disgusted', 'lonely',
        ],

        'current_action' => [
            'idle', 'sleeping', 'eating', 'drinking', 'working', 'walking',
            'talking', 'fighting', 'fleeing', 'praying', 'crafting', 'trading',
            'resting', 'patrolling', 'hunting', 'foraging', 'haggling', 'gossiping',
            'stealing', 'sneaking', 'helping', 'giving', 'mending', 'begging',
            'starving', 'exhausted', 'dead',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Object axes
    |--------------------------------------------------------------------------
    */
    'object' => [
        // High-level type. Picks the rules an object obeys at simulation time.
        'type' => [
            'weapon', 'armor', 'tool', 'container', 'furniture', 'food',
            'drink', 'clothing', 'jewelry', 'book', 'consumable', 'material',
            'currency', 'instrument', 'key', 'misc',
        ],

        // ~200 subtypes — orthogonal: subtype says WHAT, material says of what.
        'subtype' => [
            // weapons
            'sword', 'longsword', 'shortsword', 'dagger', 'rapier', 'axe',
            'handaxe', 'greataxe', 'mace', 'club', 'hammer', 'warhammer',
            'spear', 'pike', 'halberd', 'bow', 'shortbow', 'longbow', 'crossbow',
            'sling', 'staff', 'quarterstaff', 'whip', 'flail', 'morningstar',
            'arrow', 'bolt', 'javelin', 'sickle', 'scythe',

            // armor
            'helmet', 'cap', 'hood', 'breastplate', 'chainmail', 'leather_armor',
            'gambeson', 'shield', 'buckler', 'gauntlet', 'greaves', 'boots',
            'pauldron', 'bracers',

            // tools
            'pickaxe', 'shovel', 'rake', 'hoe', 'plow', 'saw', 'chisel',
            'awl', 'needle', 'spindle', 'loom_shuttle', 'fishing_rod', 'net',
            'lockpicks', 'rope', 'chain', 'lantern', 'torch', 'tinderbox',
            'whetstone', 'mortar', 'pestle', 'tongs', 'bellows', 'anvil',

            // containers
            'sack', 'pouch', 'pack', 'chest', 'crate', 'barrel', 'jar', 'bottle',
            'flask', 'waterskin', 'basket', 'bucket', 'urn',

            // furniture
            'chair', 'stool', 'bench', 'table', 'bed', 'cot', 'desk', 'shelf',
            'cabinet', 'wardrobe', 'rug', 'tapestry', 'mirror', 'candle',
            'candlestick', 'fireplace', 'cauldron',

            // food
            'bread', 'loaf', 'cheese', 'meat', 'sausage', 'fish', 'apple',
            'pear', 'turnip', 'onion', 'carrot', 'cabbage', 'porridge', 'stew',
            'pie', 'honey', 'butter', 'eggs', 'salt',

            // drink
            'water', 'ale', 'beer', 'wine', 'mead', 'milk', 'cider', 'spirit',
            'tea', 'broth',

            // clothing
            'shirt', 'tunic', 'robe', 'dress', 'cloak', 'breeches', 'trousers',
            'skirt', 'belt', 'hat', 'gloves', 'scarf',

            // jewelry
            'ring', 'amulet', 'necklace', 'bracelet', 'earring', 'circlet',
            'brooch',

            // book / written
            'book', 'tome', 'scroll', 'letter', 'map', 'ledger', 'journal',

            // consumable
            'potion', 'salve', 'poultice', 'bandage', 'poison', 'antidote',
            'incense', 'powder',

            // material (raw)
            'ore', 'ingot', 'log', 'plank', 'plank_cut', 'hide', 'pelt',
            'leather_strip', 'wool', 'linen', 'cloth', 'thread', 'clay', 'stone',
            'coal', 'charcoal', 'wax', 'tallow', 'oil', 'glass', 'sand',

            // currency
            'copper_coin', 'silver_coin', 'gold_coin', 'gem',

            // instrument
            'lute', 'flute', 'drum', 'horn', 'fiddle',

            // key / misc
            'key', 'dice', 'cards', 'pipe', 'token', 'idol', 'rune_stone',
        ],

        'material' => [
            'iron', 'steel', 'bronze', 'copper', 'silver', 'gold', 'wood',
            'oak', 'pine', 'leather', 'cloth', 'linen', 'wool', 'stone',
            'bone', 'glass', 'clay', 'flesh', 'paper',
        ],

        'quality' => ['shoddy', 'crude', 'common', 'fine', 'masterwork'],

        'wear' => ['pristine', 'used', 'worn', 'battered', 'broken'],

        'rarity' => ['mundane', 'uncommon', 'rare', 'legendary'],

        // size, weight, integrity, value are numeric — declared in migration
    ],

    /*
    |--------------------------------------------------------------------------
    | Place axes
    |--------------------------------------------------------------------------
    */
    'place' => [
        'type' => [
            'building', 'street', 'square', 'wilderness', 'dungeon', 'water',
        ],

        'subtype' => [
            // buildings
            'tavern', 'inn', 'forge', 'smithy', 'mill', 'bakery', 'butchery',
            'tannery', 'temple', 'shrine', 'chapel', 'home', 'hovel', 'manor',
            'barracks', 'guardhouse', 'jail', 'shop', 'market_stall',
            'apothecary', 'library', 'stable', 'warehouse', 'brewery',
            'bathhouse', 'mortuary', 'school', 'hall',
            // open
            'town_square', 'crossroads', 'main_road', 'alley', 'docks',
            'bridge', 'gate', 'wall', 'graveyard', 'garden', 'orchard', 'farm',
            // wild
            'forest', 'glade', 'hill', 'mountain', 'cave', 'swamp', 'meadow',
            'cliff',
            // dungeon
            'crypt', 'ruins', 'tomb', 'mine', 'sewer',
            // water
            'river', 'lake', 'well', 'stream', 'pond',
        ],

        'scale' => ['tiny', 'small', 'medium', 'large', 'huge'],

        'condition' => ['ruined', 'derelict', 'shabby', 'kept', 'pristine'],

        'climate' => ['frigid', 'cold', 'temperate', 'warm', 'hot'],

        'terrain' => [
            'paved', 'dirt', 'mud', 'grass', 'sand', 'stone', 'wood', 'water',
        ],

        'weather' => [
            'clear', 'cloudy', 'overcast', 'drizzle', 'rain', 'storm',
            'snow', 'fog', 'wind',
        ],

        'time_of_day' => [
            'dawn', 'morning', 'noon', 'afternoon', 'dusk', 'evening',
            'night', 'midnight',
        ],

        // danger_level, population, prosperity numeric
    ],

    /*
    |--------------------------------------------------------------------------
    | Action axes
    |--------------------------------------------------------------------------
    */
    'action' => [
        // Coarse intent category — drives utility weighting per need.
        'type' => [
            'satisfy_need', 'work', 'social', 'travel', 'crafting', 'combat',
            'rest', 'idle',
        ],

        // ~50 verbs an NPC can perform during a tick.
        'verb' => [
            'eat', 'drink', 'sleep', 'wash', 'rest', 'walk_to', 'enter',
            'leave', 'pick_up', 'drop', 'give', 'take', 'buy', 'sell',
            'haggle', 'craft', 'repair', 'sharpen', 'cook', 'brew',
            'forge', 'sew', 'plow', 'harvest', 'milk', 'feed', 'pray',
            'meditate', 'gossip', 'greet', 'flirt', 'argue', 'threaten',
            'lie', 'apologize', 'tell_story', 'sing', 'play_music', 'dance',
            'attack', 'defend', 'flee', 'hide', 'sneak', 'steal',
            'pick_lock', 'open', 'close', 'patrol', 'search', 'forage',
            'hunt', 'fish', 'chop_wood', 'mine', 'guard', 'observe',
        ],

        'outcome' => [
            'pending', 'success', 'partial', 'failure', 'critical_success',
            'critical_failure', 'interrupted',
        ],

        'status' => ['planned', 'in_progress', 'done', 'aborted'],
    ],
];
