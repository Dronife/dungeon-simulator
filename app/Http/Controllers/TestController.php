<?php

namespace App\Http\Controllers;

use App\Clients\GeminiClient;

class TestController
{
    public function __construct(
        private readonly GeminiClient $geminiClient
    ) {
    }

    public function test()
    {
        $supperBeats = [];

        $beats = [
            "Sable arrives in the border town of Thresh on a grey morning with a commission to update the regional maps. The town sits at the foot of a pass that has been closed for two years due to a landslide. She checks into the only inn, run by a man named Gorev who charges twice the usual rate and does not apologize for it. She asks if the pass is still closed. He says officially yes. She asks about unofficially. He refills her cup without answering.",
            "Sable sets up her equipment in the inn's back room and begins comparing her old maps to what she can see from the window. The pass on the map shows a clean route. The actual mountain shows a different shape than the map suggests — the ridge line is wrong by at least two degrees. Either the original survey was inaccurate or the mountain has changed. She makes a note and goes for a walk to get a better angle.",
            "On the main road she meets a man named Fetch who says he is a trader waiting for the pass to reopen. He is very friendly and asks a lot of questions about her work. She answers briefly. He asks if she has the original survey maps. She says she has copies. He asks who holds the originals. She says the capital registry. He says that's a long way to go for corrections. She agrees and keeps walking. She notes that he did not ask why corrections might be needed.",
            "That evening a woman knocks on Sable's door and says her name is Ru and she is the town's former surveyor. She says she heard a cartographer arrived and that she needs to show Sable something. She is nervous in the way people are nervous when they have been nervous for a long time. Sable lets her in. Ru puts a rolled paper on the table. It is a survey of the pass done eight months ago — after the supposed landslide — showing the route completely clear.",
        ];

        $response = $this->responseSummary($beats);
        $supperBeats[] = $response;

        $beats = [
            "Ru tells Sable she did the survey herself on contract from a merchant guild and that when she submitted it the guild told her the pass was still closed and paid her for a different, shorter survey she had not done. She kept her original. She says two weeks later someone broke into her house and searched it. They did not find the survey because she had hidden it under the floorboards. She has not told anyone about it until now. Sable asks why she is telling her. Ru says because Sable is from the capital and has a commission — she has a reason to be here that nobody can question.",
            "Sable examines Ru's survey carefully. The work is precise. The pass is clear, the route is stable, and the survey is dated eight months ago with Ru's official mark. If accurate it means the pass has been passable for most of the past year. Sable asks Ru who in the guild commissioned the original survey. Ru says a man named Aldric who handles northern trade routes. Sable writes the name down. She asks if Ru knows why the pass would be kept closed. Ru says she has a theory but that saying it out loud feels dangerous. Sable says she can write it down instead. Ru does not write it down.",
            "Fetch is in the common room when Sable comes down for dinner. He is talking to Gorev in a low voice and both of them stop when she enters. Fetch smiles and invites her to join him. She sits. He asks how her work is going. She says slowly. He asks if the terrain matches her maps. She says mostly. He asks what doesn't match. She says the ridge line on the eastern face. He nods like this is not surprising to him. She asks if he has been up there. He says no. She finishes her dinner and goes back upstairs. The conversation with Gorev had stopped the moment she walked in.",
            "Sable goes over what she knows. The pass is clear and has been for months. Someone paid to have the clearing survey buried. Fetch knows something about the ridge and claims he hasn't been up there. Ru is frightened and has a theory she won't say out loud. Gorev answers questions obliquely. She has a legitimate commission that gives her reason to survey the pass herself. She decides to go up in the morning and sketches a route from memory. Before sleeping she moves her working copies to the bottom of her pack and puts blank paper on top.",
        ];

        $response = $this->responseSummary($beats);
        $supperBeats[] = $response;

        $beats = [
            "Sable leaves at dawn. The path up is overgrown but passable. After an hour she finds the first sign of regular traffic — a section of trail that has been deliberately obscured with cut brush, but the ground underneath is compacted from use. Someone has been using the pass regularly and covering the signs afterward. She marks the location on her working map with a small notation and keeps climbing.",
            "Near the top of the pass she finds a supply cache hidden under a rock overhang. It contains preserved food, rope, and three empty document cases of the kind used for official papers. The cases are stamped with a merchant seal she does not recognize. She sketches the seal carefully. She does not take anything. On the way back down she counts six more sections of obscured trail. Someone is moving through the pass frequently enough to require a system.",
            "Fetch is on the trail when she comes back down. He says he decided to take a walk. He says this pleasantly. They walk down together. He asks if she found anything interesting. She says the ridge line discrepancy is less significant than she thought. He says that's good news. She asks what business he is waiting to do once the pass reopens officially. He says import of cloth from the northern valleys. She asks which guild. He says the northern trade guild. She does not mention that Aldric works for the northern trade guild. He does not mention that he already knew who she was before she arrived in Thresh.",
            "Back at the inn Ru is waiting in the hall outside Sable's room. She says Fetch came to her house this morning while Sable was gone and asked if she knew the cartographer. She told him no. She says Fetch asked specifically if Sable had visited her. She told him no again. She says she is leaving Thresh today and is going to her sister's in Veld and that Sable should know that Aldric and Fetch are the same person — Fetch is a name he uses for unofficial business. Sable asks how Ru knows this. Ru says the merchant seal on the contract she was paid from. She leaves before Sable can ask more.",
        ];

        $response = $this->responseSummary($beats);
        $supperBeats[] = $response;

        $beats = [
            "Sable writes two reports. The first is her official survey update — ridge line discrepancy noted, pass assessment ongoing, estimated completion four days. She sends this to her commission office. The second is a private letter describing the cache, the obscured trails, the buried survey, and the connection between Aldric and Fetch. She addresses it to a colleague in the capital registry and sends it separately from the post office, which is run by a woman who does not work for Gorev. She is not certain who else it might reach.",
            "Fetch finds her at the post office. He says he saw her heading this way and wanted to offer to share the postal rider since they are both sending to the capital. She says she already sent. He says that's a shame and smiles and walks with her back toward the inn. He says he thinks they got off on the wrong foot and that he'd like to be straightforward with her. She says she'd appreciate that. He says the pass reopening is a matter of significant commercial interest to several parties and that a cartographer confirming the route is clear would be very valuable. He says valuable in a way he would be happy to make concrete. She says she'll think about it.",
            "That night Gorev tells Sable her room is needed and she'll have to leave in the morning. He says a family has reserved it for a month. She asks when they reserved it. He looks at the ledger for longer than necessary and says last week. She pays for the night and goes upstairs. Her pack has been moved. Everything is still there but the blank paper she put on top is now underneath the working copies. She repacks everything, sleeps lightly, and is gone before dawn.",
            "Sable makes camp two hours outside Thresh and works by lamplight. She completes a full survey report incorporating Ru's hidden document, her own observations, the cache location, the obscured trails, and the seal she sketched. She dates it and marks it with her official cartographer's stamp. She makes two copies. She seals one for the capital registry and one for a magistrate's office in a different city. In the morning she rides for the capital by a route that does not pass back through Thresh. She does not know if Fetch will follow. She does not know what the pass is being used for. She knows the route is clear and has been for months and that several people worked to keep that information from reaching anyone with authority to act on it.",
        ];

        $response = $this->responseSummary($beats);
        $supperBeats[] = $response;

        $superResponse = $this->responseMemorySummary($supperBeats);

        dd($supperBeats, $superResponse);
    }

    private function responseMemorySummary($beats): string
    {
        $this->geminiClient->setModel(GeminiClient::MODEL_25_FLASH_LITE);

        $rules = <<<RULES
            - Every claim must name who said or did it.
            - Never combine two characters' actions in one sentence.
            - When two characters describe the same event differently, keep ONLY what the character who was most directly involved said. Delete the other version completely.
                Example: If Character A says "the box had gold" and later Character B who opened the box says "the box had silver", write ONLY "Character B found silver in the box."
            - Preserve: all character names, locations, unanswered questions, and key evidence (documents, ledgers, letters).
            - Preserve relationships between characters (who works for whom, who knows whom).
            - Keep sentences under 30 words.
        RULES;

        $jsonRule = 'return as json. Do not write json markdown (```json) No questions, no additional content. There is provided return_format how you need to return your response.';

        // Pass 1: Compress
        $pass1Payload = json_encode([
            'task' => "Compress these summaries into one paragraph of 4-6 sentences.\n\nRules:\n$rules",
            'rule' => $jsonRule,
            'summaries' => $beats,
            'return_format' => [
                'summary' => 'your-data',
            ],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        $pass1Raw = $this->geminiClient->generate(prompt: $pass1Payload, temperature: 1)->text;
        $pass1Summary = $this->parseJsonResponse($pass1Raw)['summary'] ?? '';

        // Pass 2: Find missing important details
        $pass2Payload = json_encode([
            'task' => "Compare the original summaries to the compressed version. List only the important details missing from the compressed version.\n\nImportant means: evidence, character names, suspicious or unusual behavior, unanswered questions, or later corrections to earlier claims.\nDo not list minor procedural details like movement between locations or routine actions.",
            'rule' => $jsonRule,
            'original_summaries' => $beats,
            'compressed_version' => $pass1Summary,
            'return_format' => [
                'missing' => ['detail 1', 'detail 2'],
            ],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        $pass2Raw = $this->geminiClient->generate(prompt: $pass2Payload, temperature: 0.5, thinkingBudget: 1000)->text;
        $pass2Missing = $this->parseJsonResponse($pass2Raw)['missing'] ?? [];

        // Pass 3: Recompress with missing details
        $pass3Payload = json_encode([
            'task' => "Compress these summaries into one paragraph of 6-8 sentences. You must include the listed missing details.\n\nRules:\n$rules\n- Preserve suspicious or unusual behavior by any character.",
            'rule' => $jsonRule,
            'summaries' => $beats,
            'missing_details_to_include' => $pass2Missing,
            'return_format' => [
                'summary' => 'your-data',
            ],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        $pass3Raw = $this->geminiClient->generate(prompt: $pass3Payload, temperature: 0.5, thinkingBudget: 2000)->text;

        return $this->parseJsonResponse($pass3Raw)['summary'] ?? '';
    }

    private function responseSummary($beats): string
    {
        $task =
        <<<PROMPT
            Compress the following into a single summary paragraph of 3-5 sentences.

            Rules:
            - State WHO did or said WHAT. Never merge two characters' actions or claims.
            - If a later event corrects or contradicts an earlier claim, keep the later version and note it replaced the earlier one.
            - Preserve character suspicions, motives, and unanswered questions — not just actions.
            - Do not interpret or explain — only compress what happened.
            - Keep each sentence under 30 words. No compound sentences joined with commas.
            - Preserve names, locations, and specific details (numbers, dates, document names).
        PROMPT;

        $payload = json_encode([
            'task' => $task,
            'rule' => 'return as json. Do not write json markdown (```json) No questions, no additional content. There is provided return_format how you need to return your response. ',
            'beats' => $beats,
            'return_format' => [
                'summary' => 'your-data',
            ],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        $this->geminiClient->setModel(GeminiClient::MODEL_25_FLASH_LITE);

        $text = $this->geminiClient->generate(
            prompt: $payload,
            temperature: 1
        );

        $raw = $text->text;

        $array = $this->parseJsonResponse($raw);

        return $array['summary'] ?? '';
    }

    private function parseJsonResponse(string $raw): array
    {
        $cleaned = trim($raw);

        // Strip opening ```json or ```
        if (str_starts_with($cleaned, '```')) {
            $cleaned = preg_replace('/^```[a-z]*\n?/i', '', $cleaned);
        }

        // Strip closing ```
        if (str_ends_with($cleaned, '```')) {
            $cleaned = substr($cleaned, 0, -3);
        }

        $cleaned = trim($cleaned);
        $data = json_decode($cleaned, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('JSON parse failed: ' . json_last_error_msg());
        }

        return $data;
    }
}
