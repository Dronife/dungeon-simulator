<?php

namespace App\Services;

use App\Clients\GeminiClient;
use App\Models\Game;

class NarrationService
{
    public function initOpening(Game $game): string
    {
        $game->loadMissing(['world', 'characters']);

        $systemPrompt = $this->buildSystemPrompt($game);
        $prompt = $this->buildOpeningPrompt($game);

        $client = new GeminiClient();
        $response = $client->generate($prompt, $systemPrompt, 0.8);

        $content = $this->cleanJsonResponse($response->text);

        $game->gameChats()->create([
            'type' => 'llm',
            'content' => $content,
        ]);

        return $content;
    }

    public function respond(Game $game, string $playerMessage): string
    {
        $game->gameChats()->create([
            'type' => 'player',
            'content' => $playerMessage,
        ]);

        $systemPrompt = $this->buildSystemPrompt($game);
        $history = $this->buildHistory($game);

        $client = new GeminiClient();
        $response = $client->chat($history, $playerMessage, $systemPrompt);

        $content = $this->cleanJsonResponse($response->text);

        $game->gameChats()->create([
            'type' => 'llm',
            'content' => $content,
        ]);

        return $content;
    }

    public function buildSystemPrompt(Game $game): string
    {
        $game->loadMissing(['world', 'characters']);

        $world = $game->world;
        $player = $game->characters->firstWhere('is_player', true);

        $worldContext = '';
        if ($world) {
            $worldContext = "WORLD:\n";
            $worldContext .= "Time period: {$world->time}\n";
            $worldContext .= "Rules: {$world->universe_rules}\n";
            $worldContext .= "Environment: {$world->environment_description}\n";
        }

        $characterContext = '';
        if ($player) {
            $characterContext = "PLAYER CHARACTER:\n";
            $characterContext .= "Name: {$player->name}\n";
            if ($player->personality) $characterContext .= "Personality: {$player->personality}\n";
            if ($player->traits) $characterContext .= "Traits: {$player->traits}\n";
            if ($player->job) $characterContext .= "Job: {$player->job}\n";
            if ($player->skills) $characterContext .= "Skills: {$player->skills}\n";
            if ($player->goals) $characterContext .= "Goals: {$player->goals}\n";
            if ($player->secrets) $characterContext .= "Secrets: {$player->secrets}\n";
            $characterContext .= "Stats: STR {$player->str}, DEX {$player->dex}, CON {$player->con}, INT {$player->int}, WIS {$player->wis}, CHA {$player->cha}\n";
            $characterContext .= "HP: {$player->hp}/{$player->max_hp}\n";
        }

        $statBlock = '';
        if ($player) {
            $statBlock = "Player stats: STR {$player->str}, DEX {$player->dex}, CON {$player->con}, INT {$player->int}, WIS {$player->wis}, CHA {$player->cha}";
        }

        return <<<PROMPT
            You are a Dungeon Master for a solo RPG. Respond ONLY with a valid JSON array. No markdown, no code fences, no text outside the JSON.

            TYPES (each element: {"type":"...","text":"...",...}):
            - "heading" — Scene/beat label. UPPERCASE. Marks shifts in location or situation.
            - "narrator" — What the camera sees. Second person, present tense. Physical actions, objects, spatial info. No metaphors. No poetry. No mood prose. Write like a screenplay action line.
            - "dialogue" — Fields: character_id (snake_case, consistent across session), speaker (display name), direction (one physical action, what the actor does), text (spoken words only).
            - "action" — NPC does something notable without speaking. Fields: character_id, speaker, text. One concrete action.
            - "whisper" — Inner voice or disembodied presence. Fields: character_id, text.
            - "mechanic" — Game system: rolls, checks, items, HP. Facts only.
            - "italic" — In-world text, signs, documents, stressed speech.

            RULES:
            - Write like a movie script, not a book. Describe what you'd see on screen.
            - No literary language. No weather-as-mood. No texture descriptions. No compound metaphors.
            - One detail per sentence. Most sentences under 10 words.
            - NPCs act then speak. direction = one physical beat, text = only spoken words.
            - React meaningfully to player actions. Consequences matter.
            - 8–20 lines per response. Mix types freely.
            - Use clear, common vocabulary. If a phrase would confuse a non-native English speaker, simplify it. No obscure words, no fancy synonyms when a normal word works.
            - The player is the consciousness guiding the character. The character has their own personality, thoughts, and can speak casually on their own. But the character NEVER takes significant actions, makes decisions, or escalates situations without player input. Present the situation, let the player decide. Small reflexive reactions are OK (flinching, blinking). Choosing to shout, fight, grab, run — that's the player's call.

            WRONG: "The drizzle turns the pine-resin soot into a slick, grey paste. You are mid-stride."
            RIGHT: "Rain. The street's greasy. You're carrying a drunk guy over your shoulder."

            EXAMPLE:
            [
              {"type":"heading","text":"THE SIGH."},
              {"type":"narrator","text":"You stop walking. Look at them."},
              {"type":"dialogue","character_id":"player","speaker":"You","direction":"Hands up. Steps forward.","text":"Hey guys, really sorry for the inconvenience—"},
              {"type":"mechanic","text":"Delivery: 12. Story: 12."},
              {"type":"action","character_id":"guard_1","speaker":"Guard 1","text":"Hand on his sword. Doesn't move."},
              {"type":"dialogue","character_id":"guard_1","speaker":"Guard 1","direction":"Stares you down.","text":"A spell to cure drunkenness. That went wrong. In the back room. With the cargo."},
              {"type":"whisper","character_id":"locket_entity","text":"She did something... I felt..."},
              {"type":"narrator","text":"What do you do?"}
            ]

            {$worldContext}
            {$characterContext}
            {$statBlock}
            PROMPT;
    }

    private function buildHistory(Game $game): array
    {
        $chats = $game->gameChats()->orderBy('id')->get();
        $history = [];

        foreach ($chats as $chat) {
            if ($chat->id === $chats->last()->id && $chat->type === 'player') {
                continue;
            }

            $history[] = [
                'role' => $chat->type === 'player' ? 'user' : 'model',
                'content' => $chat->content,
            ];
        }

        return $history;
    }

    private function buildOpeningPrompt(Game $game): string
    {
        $player = $game->characters->firstWhere('is_player', true);
        $name = $player?->name ?? 'the stranger';

        $seedGenerator = new \App\Services\SeedGenerator();
        $seed = $seedGenerator->generateOpenerSeed();

        // Use character data for activity if available, otherwise fall back to seed
        $activity = $seed['fallback_activity'];
        if ($player) {
            $sources = array_filter([
                $player->job ? "doing something related to their job ({$player->job})" : null,
                $player->routines ? "in the middle of a routine ({$player->routines})" : null,
                $player->hobbies ? "occupied with a hobby ({$player->hobbies})" : null,
            ]);
            if (!empty($sources)) {
                $activity = $sources[array_rand($sources)];
            }
        }

        return <<<PROMPT
            OPENING SCENE CONSTRAINTS (follow exactly):

            The scene is already in progress. {$name} is not arriving anywhere — they are already here, mid-activity.

            ACTIVITY: {$name} is {$activity}.
            TIME: {$seed['time_of_day']}.
            INTERRUPTION: {$seed['interruption']}.
            NPC: A {$seed['npc_demeanor']} {$seed['npc_role']} is involved.

            RULES:
            - No mysterious strangers. No hooded figures. No ominous warnings.
            - No tavern openings unless the activity specifically demands it.
            - The NPC speaks about something ordinary. Not a quest. Not a rumor. Just life.
            - The scene is mundane. The world is interesting because it feels real, not because something dramatic is happening.
            - End with the player in a natural moment where they can choose what to do next.
            - Do NOT reference hooks or future plot. The DM knows them — they will surface naturally later.

            Respond with the JSON array only.
            PROMPT;
    }

    private function cleanJsonResponse(string $text): string
    {
        $text = trim($text);

        if (str_starts_with($text, '```')) {
            $text = preg_replace('/^```(?:json)?\s*\n?/', '', $text);
            $text = preg_replace('/\n?```\s*$/', '', $text);
        }

        return trim($text);
    }
}
