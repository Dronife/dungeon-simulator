<?php

namespace App\Services;

use App\Clients\GeminiClient;

class WorldGenerator
{
    private GeminiClient $client;

    public function __construct()
    {
        $this->client = new GeminiClient();
    }

    public function generate(): array
    {
        ini_set('max_execution_time', 300);

        $prompt = $this->buildPrompt();

        $response = $this->client->generate(
            prompt: $prompt,
            systemPrompt: 'You are a fantasy world and character generator. Return ONLY valid JSON, no markdown, no explanation.',
            temperature: abs(random_int(-100,100)/100),
        );

        $json = json_decode($response->text, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Failed to parse LLM response as JSON: ' . $response->text);
        }

        foreach($this->generateStats() as $key => $stat) {
            $json['character'][$key] = $stat;
        }


        return $json;
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
             On the other hand person with 1 positiveness is positive, outgoing, more likely to trust people, more kee to like things
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
                    "chaotic-temperature": %d,
                    "positive-temperature": %d,
                },
                "world": {
                    "time": "string - era, season, time of day",
                    "universe_rules": "string - how magic works, gods, special physics or rules",
                    "environment_description": "string - the starting location, atmosphere, key features"
                }
            }

            Stats should reflect the character's background. An archivist might have high INT/WIS but low STR. A blacksmith would have high STR/CON. Average human is 10 in all stats.

            Be creative but coherent. Make the character feel real with flaws and depth. The world should have interesting hooks for adventure.
            PROMPT;

        $prompt = sprintf($prompt, random_int(-100,100)/100, random_int(-100,100)/100);

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
}
