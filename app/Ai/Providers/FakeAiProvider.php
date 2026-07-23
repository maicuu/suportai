<?php

namespace App\Ai\Providers;

use App\Ai\AiAnalysis;
use App\Ai\AiProvider;
use Illuminate\Support\Str;

/**
 * Adapter de IA "de mentira": heurística local, sem chamada externa.
 * Serve pra dev/demo offline e pros testes (rápido e determinístico).
 * Implementa o MESMO port que o Groq — o domínio não vê diferença.
 */
class FakeAiProvider implements AiProvider
{
    public function analyze(string $subject, string $body): AiAnalysis
    {
        $text = Str::lower($subject.' '.$body);

        $priority = match (true) {
            Str::contains($text, ['urgente', 'parou', 'caiu', 'travando', 'erro 500', 'nao consigo', 'não consigo']) => 'high',
            Str::contains($text, ['duvida', 'dúvida', 'como faço', 'quando']) => 'low',
            default => 'medium',
        };

        $sentiment = match (true) {
            Str::contains($text, ['pessimo', 'péssimo', 'horrivel', 'horrível', 'raiva', 'cancelar', 'reembolso']) => 'negative',
            Str::contains($text, ['obrigado', 'otimo', 'ótimo', 'excelente', 'parabens', 'parabéns']) => 'positive',
            default => 'neutral',
        };

        $category = match (true) {
            Str::contains($text, ['cobranca', 'cobrança', 'fatura', 'pagamento', 'reembolso', 'cartao', 'cartão']) => 'billing',
            Str::contains($text, ['bug', 'erro', 'travando', 'quebrou', 'nao funciona', 'não funciona']) => 'bug',
            Str::contains($text, ['senha', 'login', 'acesso', 'entrar', 'logar']) => 'acesso',
            default => 'geral',
        };

        $reply = "Olá! Obrigado por entrar em contato sobre \"{$subject}\". "
            .'Já registramos seu chamado e nossa equipe vai te ajudar o quanto antes. '
            .'Para agilizar, você poderia confirmar mais alguns detalhes do que aconteceu?';

        return new AiAnalysis($category, $priority, $sentiment, $reply);
    }
}
