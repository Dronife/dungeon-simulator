<?php

namespace App\Services;

use App\Clients\GeminiClient;

class WorldGenerator
{
    private GeminiClient $client;
    private float $globalTemperature;

    public function __construct()
    {
        $this->client = new GeminiClient();
    }

    public function generate(): array
    {
        ini_set('max_execution_time', 300);
        $this->globalTemperature = abs((random_int(-100,100))/100);

        $prompt = $this->buildPrompt();

        $response = $this->client->generate(
            prompt: $prompt,
            systemPrompt: 'You are a fantasy world and character generator. Return ONLY valid JSON, no markdown, no explanation.',
            temperature: $this->globalTemperature,
        );

        $json = json_decode($response->text, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Failed to parse LLM response as JSON: ' . $response->text);
        }

        foreach($this->generateStats() as $key => $stat) {
            $json['character'][$key] = $stat;
        }

        $json['character']['temperature'] = $this->globalTemperature;

        return $json;
    }

    public function getTemperature(): int
    {
        return $this->globalTemperature;
    }

    private function buildPrompt(): string
    {
        $prompt =  <<<PROMPT
            Generate a new fantasy RPG game world and player character. Return JSON with exactly this structure:
            Be straight to the point, simple, cohisive, dont yap around the bush.

            I will provide temperatures, just use them.

             If the temperature is lower the context for the character is lower as well. For instance they could not have hobbies, routines or jobs.
             Overall not all context in character can be filled in.
             Temperature can be from 0 to 1. The closer to the 0 the more simplier character it is OR
             less attribute character has. YOU ARE ALLOWERD TO LEAVE SOME ATTRIBUTES EMPTY.
             Then we have chaotic and positive temperatures which can be from -1 to 1.
             Chaotic temperature is how chaotic things are. -1 Chaos is not chaotic - it is very structured, not random attributes - for instance as person in the morning he or she wakes up always n 7 am. Never lies and are really trustworthy. Basically structured people with order in their lives
             On the other hand chaos 1 is opposite - as an example person in the morning can go hunt dragons and in the evening they will knit their scarf.
             Then there is positive temperature. Person with -1 positiveness is really negative, bitter, do not trust people, do not like things, are sceptical etc etc.
             On the other hand person with 1 positiveness is positive, outgoing, more likely to trust people, more kee to like things.

            if you gonna use strange world building with unknown phrases, please add them to the json and explain them. For instance if you say character's trauma is "saw how her mother was consumed by snaily snail ghost snail" - then you add the "snaily snail ghost snail" into world_lore.
            lore_temperature is between 0 and 1. It indicates how much lore to add. Preference is to not add stuff around. But if lore temperature allows it, you can add.


            There are 11(personality, traits, trauma, hobbies, routines, job, skills, goals, secrets, limits, intentions) personality attributes. They are all equally important, so it does not matter which ones do you fill or not.
            Depending on the temperature you need to fill that much:
            temperature between 0 and 0.2 - minimum 1 and max 3 attributes.
            temperature between 0.21 and 0.45 - minimum 3 and max 6 attributes.
            temperature between 0.46 and 0.99 - minimum 7 and max 11 attributes.

            the "predefined_world_lore_values_if_any" is helper for llm because llm can not think always and it helps to write lore IF THERE IS ANY.
            It is not clear how many lore items LLM will write so there will be 10 items in order saying if
            description has much information, if known_how is possible, if there is reason, occurrence.(All values between 0 and 100).
            The lower the value for attribute the less text and the more direct text becomes.

            world_explanation_predefined will have predefined values
            "magic":
                - 0-36 from none to minimal, there are instances where luck+dedication+will+genes makes you insane user. <= 1 percent of people able to use in extreme levels. Magic is really grounded. logical magical systems.
                - 37-78 from minimal to moderate magic. It has system but is more loose, also grounded. Magic is more often in the world.
                - 79-100 from moderate to always magic. It is grounded, but nothing special.
            "gods":
                - 0-36 - None to max 2. None can be anything else, super humans or something like that. Gods are more like fairy tales. But if higher score they exist, but it is really rare that gods appears
                - 37-78  can be one or few. Gods are present. Time to time they appear. People know that they exist, but is rare occasion they appear.
                - 79-100, They kinda like rare celebrities, they appear time to time. They like to interfere with human decisions
            "physics":
                - 0-50 - simple physics
                - 51-100 - there can be from zero two few physic quirks that the real world does not have, but possible in sci-fi,
            "specific_rules":
                - 0-50 - nothing specific
                - 51-100 - from nothing to one or few specific world/nation rules
            "current_location":
                - 0-36 - small village
                - 37-78 - small town
                - 79-100 - capital

            structure:
            {
                "character": {
                    "name": "full name",
                    "info": "About who they are, age, background. It can be simple background, it can be complex background. It can be short introduction. You can here apply temperature for more creativity. The bigger temperature the more absurd it can be",
                    "personality": "string - core personality traits and how they behave",
                    "traits": "string - notable characteristics, quirks, habits if have any",
                    "trauma": "string - past wound or fear that affects them if have any",
                    "hobbies": "string - what they do for fun or relaxation",
                    "routines": "string - daily habits and patterns",
                    "job": "string - profession or role",
                    "skills": "string - what they're good at",
                    "goals": "string - what they want to achieve",
                    "secrets": "string - something they hide from others",
                    "limits": "string - lines they won't cross, weaknesses",
                    "intentions": "string - current short-term plans",
                    "chaotic_temperature": %.2f,
                    "positive_temperature": %.2f,
                    "lore_temperature": %.2f,
                    "temperature": %.2f,
                    "how_many_attributes-to-fill": %d
                    "attributes_to_fill": %s
                },
                "predefined_world": {
                    %s
                }
                "world": {
                    "time": "era, season, time of day",
                    "universe_rules": "how magic works, gods, special physics or rules",
                    "environment_description": "the starting location, atmosphere, key features"
                },
                "world_explanation": {
                    "world_era" : "what is this era?",
                    "magic": "if any",
                    "gods": "if any",
                    "physics": "if any",
                    "specific_rules": "if any",
                    "current_location": "place"
                },
                "predefined_world_lore_values_if_any": [
                    %s
                ],
                "world_lore": [
                    {
                        "name": "name of thing you write",
                        "type": "event|place|creature|organization|artifact|phenomenon",
                        "description": "what it is, 1-2 sentences",
                        "know_how": "where to find or how to make or how to acquire if possible"
                        "reason" : "what causes or why it exists",
                        "occurrence" : "occasionally, sometimes, frequently, often, usually, regularly, consistently, constantly, invariably, and forever",
                        "image_prompt" : "straight to te point, abstract, epic, concept art of a thing you write"
                    }
                ]
            }

            Please return predefined_world_lore_values_if_any elements as much as world_lore there are elements no more no less.

            Be creative but coherent. Make the character feel real with flaws and depth. The world should have interesting hooks for adventure.
            PROMPT;

        $attributeCount =  $this->getAttributeCount();
        $prompt = sprintf(
            $prompt,
            random_int(-100,100)/100,
            random_int(-100,100)/100,
            abs(random_int(-100,100)/100)/2,
            $this->globalTemperature,
            $attributeCount,
            $this->getAttributeNames($attributeCount),
            $this->worldExplanationPredefined(),
            $this->getPredefinedLoreStats(),
        );

        return $prompt;
    }

    /**
     * @return array<string, int>
     */
    private function generateStats(): array
    {
        $con = random_int(5,19);
        $hp = $con * 2 + 10;
        return [
            'str' => random_int(5,19),
            'dex' => random_int(5,19),
            'con' => $con,
            'int' => random_int(5,19),
            'wis' => random_int(5,19),
            'cha' => random_int(5,19),
            'hp' =>  $hp,
            'max_hp' => $hp,
            'trauma_severity' => random_int(1, 6),
            'goal_severity' => random_int(1, 6),
            'intention_severity' => random_int(1, 10),
            'personality_severity' => random_int(1, 10),
        ];
    }

    private function getAttributeCount(): int
    {
        if($this->globalTemperature >= 0 && $this->globalTemperature <= 0.2){
            return random_int(1,3);
        }

        if($this->globalTemperature >= 0.21 && $this->globalTemperature <= .45){
            return random_int(3,6);
        }

        return random_int(7,11);
    }

    public function getAttributeNames(int $amount): string
    {
        $attributes = ['personality', 'traits', 'trauma', 'hobbies', 'routines', 'job', 'skills', 'goals', 'secrets', 'limits', 'intentions'];

        shuffle($attributes);

        return implode(', ', array_slice($attributes, 0, $amount));
    }


    public function getPredefinedLoreStats(): string {
        $lore = [];
        for($i = 0; $i < 10; $i++){
            $lore[] = [
                'description' => random_int(1, 100),
                'known_how' => random_int(1, 100),
                'reason' => random_int(1, 100),
                'occurrence' => random_int(1, 100),
                'grounding' => random_int(1, 100),
                'chaos' => random_int(1, 100),
            ] ;
        }

        return json_encode($lore);
    }

    private function worldExplanationPredefined(): string
    {
        $world =  [
            'magic' => random_int(1,100),
            'gods' => random_int(1,100),
            'physics' => random_int(1,100),
            'specific_rules' => random_int(1,100),
            'current_location' => random_int(1,100),
        ];

        return json_encode($world);
    }
}
