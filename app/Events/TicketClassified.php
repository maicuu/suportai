<?php

namespace App\Events;

use App\Models\Ticket;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * A IA terminou de classificar o ticket. Transmitido no canal privado do
 * tenant para o card "acender" com categoria/prioridade/sentimento/rascunho.
 */
class TicketClassified implements ShouldBroadcast
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
        return 'ticket.classified';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return ['ticket' => $this->ticket->toBroadcastArray()];
    }
}
