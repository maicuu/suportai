<?php

namespace App\Jobs;

use App\Ai\AiProvider;
use App\Events\TicketClassified;
use App\Models\Ticket;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Classifica um ticket com IA, FORA do request (fila).
 *
 * Resiliência: tenta até 3x com backoff. Se a IA falhar de vez, o ticket
 * continua salvo (só sem sugestão) — degradação graciosa, como manda o brief.
 *
 * Recebe apenas o ID (não o model) pra não serializar estado velho.
 */
class ClassifyTicket implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [10, 30, 60];

    public function __construct(public int $ticketId) {}

    public function handle(AiProvider $ai): void
    {
        // Sem usuário logado na fila: buscamos sem o global scope
        // (o tenant já está fixado na própria linha do ticket).
        $ticket = Ticket::withoutGlobalScopes()->find($this->ticketId);

        if ($ticket === null) {
            return; // ticket removido nesse meio-tempo
        }

        $analysis = $ai->analyze($ticket->subject, $ticket->body);

        // forceFill: os campos de IA não são fillable (só o servidor os define).
        $ticket->forceFill([
            'category' => $analysis->category,
            'priority' => $analysis->priority,
            'sentiment' => $analysis->sentiment,
            'ai_suggested_reply' => $analysis->suggestedReply,
            'ai_processed_at' => now(),
        ])->save();

        // Tempo real: avisa o board que a IA terminou (o card "acende").
        TicketClassified::dispatch($ticket);
    }
}
