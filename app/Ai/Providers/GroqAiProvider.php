<?php

namespace App\Ai\Providers;

use App\Ai\AiAnalysis;
use App\Ai\AiProvider;
use Illuminate\Support\Facades\Http;

/**
 * Adapter real: Groq (API compatível com OpenAI). Pede à LLM um JSON
 * estruturado e o converte no DTO AiAnalysis. Falha de rede/HTTP sobe como
 * exceção (o Job cuida do retry/degradação — a resiliência vive lá).
 */
class GroqAiProvider implements AiProvider
{
    public function __construct(
        private string $apiKey,
        private string $model,
        private string $baseUrl,
    ) {}

    public function analyze(string $subject, string $body): AiAnalysis
    {
        $content = Http::withToken($this->apiKey)
            ->timeout(30)
            ->post($this->baseUrl.'/chat/completions', [
                'model' => $this->model,
                'temperature' => 0.2,
                'response_format' => ['type' => 'json_object'],
                'messages' => [
                    ['role' => 'system', 'content' => $this->systemPrompt()],
                    ['role' => 'user', 'content' => "Assunto: {$subject}\n\nMensagem: {$body}"],
                ],
            ])
            ->throw()
            ->json('choices.0.message.content');

        /** @var array<string, string> $data */
        $data = json_decode((string) $content, true, flags: JSON_THROW_ON_ERROR);

        return new AiAnalysis(
            category: $data['category'] ?? 'geral',
            priority: $data['priority'] ?? 'medium',
            sentiment: $data['sentiment'] ?? 'neutral',
            suggestedReply: $data['suggested_reply'] ?? '',
        );
    }

    private function systemPrompt(): string
    {
        return <<<'PROMPT'
        Você é um assistente de triagem de suporte ao cliente. Analise o ticket
        e responda SOMENTE com um JSON válido, sem texto ao redor, com as chaves:
        - "category": categoria curta em minúsculas (ex.: "billing", "bug", "acesso", "duvida", "geral").
        - "priority": exatamente um de "low", "medium", "high", "urgent".
        - "sentiment": exatamente um de "positive", "neutral", "negative".
        - "suggested_reply": um rascunho de resposta cordial, claro e útil ao cliente, em português.
        PROMPT;
    }
}
