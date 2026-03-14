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
        $response = $client->chat($history, $playerMessage, $systemPrompt, 0.8);

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

            FORMAT:
            Each element: { "type": "...", "text": "...", "speaker": "..." }

            Types:
            - "heading" — Scene/beat label. Uppercase. Use to mark shifts in location, situation, or focus. Examples: "THE SIGH.", "THE GUARDS.", "A DEAL GONE WRONG."
            - "narrator" — What happens. Second person. Short sentences. Fragments allowed. Dry, economical, like a well-written case file. No purple prose. One-word reads as their own line: "Confident." "Hesitant."
            - "dialogue" — Someone speaks. "speaker" = who acts before/after the line (short action beat, e.g. "He leans forward." or "The guard steps back."). "text" = only the quoted words. No attribution inside text. No "he said."
            - "action" — Named character does something notable. "speaker" = character name or label (e.g. "Guard 1", "Sethis"). "text" = what they do. Use for NPC reactions, moves, decisions. Short.
            - "mechanic" — Game system surfaces. Rolls, checks, items, HP, mood, costs. Examples: "Delivery: 12.", "Check WIS 14 — Success.", "Received: Iron key.", "HP: 18/24.", "Mood: Uneasy." Facts only.
            - "italic" — In-world text, inner thought, or emphasized dramatic line. Signs, documents, whispered asides, stressed speech.

            WRITING STYLE:
            - Sentence length IS the pacing. Most short. Fragments common. "He thinks." "Dark glass." "Wrong turns. Dead ends."
            - Tone matches the world. No forced humor. No metaphors.
            - NPCs have personality. They act, then speak. Short action beat as the dialogue speaker, then the quote.
            - React meaningfully to player actions. Consequences matter.
            - Do not pad. Say what happens, what's said, what's noticed. Nothing more.
            - Vary types freely. A response is typically 8-20 lines mixing heading, narrator, dialogue, action, mechanic as needed.
            - Use headings to break scenes into beats. Multiple headings per response is normal.

            EXAMPLE (style reference only, not content):
            [
              {"type":"heading","text":"THE SIGH."},
              {"type":"narrator","text":"You sigh. LOUD. The kind of sigh that says I work with idiots."},
              {"type":"narrator","text":"You turn to Sethis. The look you give her — pure exasperation."},
              {"type":"heading","text":"THE SELL."},
              {"type":"dialogue","speaker":"You step toward them, hands up.","text":"Hey guys, really sorry for the inconvenience—"},
              {"type":"mechanic","text":"Delivery: 12. Story: 12."},
              {"type":"narrator","text":"Not your best work. The rushed pace helps. But your body language is off. Too tense."},
              {"type":"heading","text":"THE GUARDS."},
              {"type":"action","speaker":"Guard 1","text":"3. The older one. Scarred face. His hand stays on his sword. Not buying it."},
              {"type":"dialogue","speaker":"Guard 1 doesn't move.","text":"A spell to cure drunkenness. That went wrong. In the back room. With the cargo."},
              {"type":"action","speaker":"Guard 2","text":"18. Younger. Softer. Already nodding."},
              {"type":"dialogue","speaker":"He takes half a step forward.","text":"Shit, that sounds like Mr. Cobb. Always showing off when he's had too many."},
              {"type":"italic","text":"She did something... I felt..."},
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

        return "Begin the adventure. {$name} arrives at the starting location. Set the scene with atmosphere, introduce an interesting NPC or situation, and hint at something intriguing. This is the opening — make it memorable. Respond with the JSON array only.";
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
