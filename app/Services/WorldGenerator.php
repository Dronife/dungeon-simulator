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
        $prompt = $this->buildPrompt();

        $response = $this->client->generate(
            prompt: $prompt,
            systemPrompt: 'You are a fantasy world and character generator. Return ONLY valid JSON, no markdown, no explanation.',
            temperature: 0.9,
        );

        $json = json_decode($response->text, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Failed to parse LLM response as JSON: ' . $response->text);
        }

        return $json;
    }

    private function buildPrompt(): string
    {
        return <<<PROMPT
Generate a new fantasy RPG game world and player character. Return JSON with exactly this structure:

{
    "character": {
        "name": "string - unique fantasy name",
        "info": "string - 2-3 sentences about who they are, age, background",
        "personality": "string - core personality traits and how they behave",
        "traits": "string - notable characteristics, quirks, habits",
        "trauma": "string - past wound or fear that affects them",
        "hobbies": "string - what they do for fun or relaxation",
        "routines": "string - daily habits and patterns",
        "job": "string - profession or role",
        "skills": "string - what they're good at",
        "goals": "string - what they want to achieve",
        "secrets": "string - something they hide from others",
        "limits": "string - lines they won't cross, weaknesses",
        "intentions": "string - current short-term plans",
        "temperature": 0.7,
        "str": "integer 6-18 - Strength: physical power, melee combat",
        "dex": "integer 6-18 - Dexterity: agility, reflexes, ranged attacks",
        "con": "integer 6-18 - Constitution: health, endurance, stamina",
        "int": "integer 6-18 - Intelligence: knowledge, reasoning, magic",
        "wis": "integer 6-18 - Wisdom: perception, insight, willpower",
        "cha": "integer 6-18 - Charisma: persuasion, leadership, presence",
        "hp": "integer - hit points, based on constitution (con * 2 + 10 is a good baseline)",
        "max_hp": "integer - same as hp initially"
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
    }
}
