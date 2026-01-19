<?php

namespace App\Http\Controllers;

use App\Clients\GeminiClient;
use Illuminate\Http\Request;

class PlaygroundController extends Controller
{
    public function index()
    {
        return view('playground.index');
    }

    public function generate(Request $request)
    {
        $request->validate([
            'prompt' => 'required|string|max:2000',
            'system_prompt' => 'nullable|string|max:2000',
            'temperature' => 'nullable|numeric|min:0|max:2',
        ]);

        $client = new GeminiClient();

        $response = $client->generate(
            prompt: $request->input('prompt'),
            systemPrompt: $request->input('system_prompt'),
            temperature: (float) $request->input('temperature', 0.7),
        );

        return response()->json([
            'text' => $response->text,
            'prompt_tokens' => $response->promptTokens,
            'completion_tokens' => $response->completionTokens,
            'finish_reason' => $response->finishReason,
        ]);
    }
}
