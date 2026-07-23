<?php

namespace App\Events;

use App\Models\Ticket;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Um ticket foi aberto. Transmitido no canal privado do tenant para o
 * board dos agentes atualizar ao vivo.
 *
 * ShouldBroadcast (não ...Now): o envio vai para a fila — se o Reverb
 * estiver fora do ar, a criação do ticket não quebra (resiliência).
 */
class TicketCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Ticket $ticket) {}

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel('tenant.'.$this->ticket->tenant_id)];
    }

    public function broadcastAs(): string
    {
        return 'ticket.created';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return ['ticket' => $this->ticket->toBroadcastArray()];
    }
}
