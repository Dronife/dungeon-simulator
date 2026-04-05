<?php

namespace App\Services;

use App\Enum\MemoryType;
use App\Models\Character;
use App\Models\CharacterRelationship;

class CharacterPromptMapper
{
    public function buildIntentWithContext(Character $character, array $intent)
    {
        $check = $intent['check'] === 'none' ? '' : "[Check]: {$intent['check']} -- ";

        return
            <<<PROMPT
                ====================================
                Character [$character->name] intent:
                {$check} {$intent['answer']}
                --------------
                Character [$character->name] bio:
                {$this->parseBio($character)}
                --------------
                Speech patterns:
                {$this->parseSpeech($character)}
                ====================================
            PROMPT;
    }

    public function constructSheet(Character $character, Character ...$nearbyCharacters): string
    {
        return
            <<<PROMPT
                You are this character.
                ===============================
                Bio:
                {$this->parseBio($character)}
                ===============================
                Identity:
                {$this->parseIdentity($character)}
                ===============================
                Speech patterns:
                {$this->parseSpeech($character)}
                ===============================
                Backstory:
                {$this->parseMemories($character, MemoryType::EPOCH)}
                ===============================
                Things that happened:
                {$this->parseMemories($character, MemoryType::SUMMARY)}
                ===============================
                Things recently happened:
                {$this->parseMemories($character, MemoryType::RECAP)}
                ===============================
                Current moments:
                {$this->parseMemories($character, MemoryType::TICK)}
                ===============================
                RelationshipMap:
                {$this->parseRelationships($character, ...$nearbyCharacters)}
                ===============================

                  What do you want to do next? Describe your intention in first person.
                  If you want to say something, you MUST include the exact words in double quotes.
                  Keep it to one action. Do not narrate what happens — only state what you attempt.
                  DO NOT INVENT THINGS - places, items. Whole info is here.
                  Keep it brief.
            PROMPT;
    }

    private function parseRelationships(Character $character, Character ...$nearbyCharacters): string
    {
        $string = '';

        foreach ($nearbyCharacters as $nearbyCharacter) {
            $characterRelationship = $character->relationshipWith($nearbyCharacter);
            $relationship = '';
            foreach($characterRelationship->getFillable() as $fillable) {
                $relationship .= "{$fillable}={$characterRelationship->$fillable}; ";
            }

            $string .= "[{$nearbyCharacter->name}]: $relationship\n";
        }

        return $string;
    }

    private function parseBio(Character $character): string
    {
        $string = '';
        $fields = ['name', 'age', 'gender', 'race'];

        foreach ($fields as $field) {
            if ($character->$field) {
                $string .= "[{$field}]: {$character->$field}; ";
            }
        }

        return $string;
    }

    private function parseSpeech(Character $character): string
    {
        $string = '';
        $fields = ['talking_mannerism', 'talking_style'];

        foreach ($fields as $field) {
            if ($character->$field) {
                $string .= "[{$field}]: {$character->$field}; ";
            }
        }

        return $string;
    }

    private function parseIdentity(Character $character): string
    {
        $string = '';
        $fields = ['info', 'personality', 'traits', 'trauma', 'hobbies', 'routines', 'job', 'skills', 'goals', 'secrets', 'limits', 'intentions'];

        foreach ($fields as $field) {
            if ($character->$field) {
                $string .= "[{$field}]: {$character->$field}; ";
            }
        }

        return $string;
    }

   private function parseMemories(Character $character, MemoryType $memoryType): string
   {
       return implode("\n",$character->memories()->where('type', $memoryType)->get()->pluck('memory')->toArray());
   }
}
