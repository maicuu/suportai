<?php

namespace App\Ai;

/**
 * Resultado da triagem por IA (DTO imutável).
 * Fronteira limpa: o provedor devolve ISTO, não um payload cru da API.
 */
final class AiAnalysis
{
    public function __construct(
        public readonly string $category,
        public readonly string $priority,   // low | medium | high | urgent
        public readonly string $sentiment,  // positive | neutral | negative
        public readonly string $suggestedReply,
    ) {}
}
