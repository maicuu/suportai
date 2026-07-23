<?php

namespace App\Ai;

/**
 * PORT (padrão hexagonal): contrato de IA para triagem de tickets.
 *
 * O domínio (Job, controllers) depende SÓ desta interface — nunca de um
 * provedor concreto. Os adapters (FakeAiProvider, GroqAiProvider, ...) são
 * plugados via service container (ver AppServiceProvider), então trocar de
 * IA é mudar 1 linha de config, sem tocar no domínio.
 */
interface AiProvider
{
    public function analyze(string $subject, string $body): AiAnalysis;
}
