<?php

use App\Ai\AiProvider;
use App\Events\TicketClassified;
use App\Events\TicketCreated;
use App\Jobs\ClassifyTicket;
use App\Models\Tenant;
use App\Models\Ticket;
use Illuminate\Support\Facades\Event;

it('dispara TicketCreated ao abrir um ticket', function () {
    Event::fake([TicketCreated::class]);

    $tenant = Tenant::factory()->create();

    $this->postJson("/t/{$tenant->slug}/tickets", [
        'requester_name' => 'Ana',
        'requester_email' => 'ana@ex.com',
        'subject' => 'Teste de broadcast',
        'body' => 'Corpo do ticket.',
    ])->assertCreated();

    Event::assertDispatched(TicketCreated::class, fn ($e) => $e->ticket->tenant_id === $tenant->id);
});

it('TicketCreated transmite no canal privado do tenant', function () {
    $ticket = Ticket::factory()->create();

    $channels = (new TicketCreated($ticket))->broadcastOn();

    expect($channels[0]->name)->toBe('private-tenant.'.$ticket->tenant_id);
});

it('o job dispara TicketClassified ao terminar a IA', function () {
    Event::fake([TicketClassified::class]);

    $ticket = Ticket::factory()->create();

    (new ClassifyTicket($ticket->id))->handle(app(AiProvider::class));

    Event::assertDispatched(TicketClassified::class, fn ($e) => $e->ticket->id === $ticket->id);
});
