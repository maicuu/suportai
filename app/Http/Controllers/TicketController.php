<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReplyRequest;
use App\Http\Requests\StoreTicketRequest;
use App\Http\Resources\MessageResource;
use App\Http\Resources\TicketResource;
use App\Models\Tenant;
use App\Models\Ticket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class TicketController extends Controller
{
    /**
     * Lista os tickets do tenant do agente logado.
     * O global scope (BelongsToTenant) já filtra por tenant — nada de
     * "buscar tudo e filtrar na mão".
     */
    public function index(): AnonymousResourceCollection
    {
        $tickets = Ticket::latest()->get();

        return TicketResource::collection($tickets);
    }

    /**
     * Detalhe do ticket + thread de mensagens.
     * O route-model binding respeita o global scope: ticket de outro tenant
     * simplesmente não é encontrado (404).
     */
    public function show(Ticket $ticket): TicketResource
    {
        return TicketResource::make($ticket->load('messages'));
    }

    /**
     * Abertura PÚBLICA de ticket (o "ticket chega").
     * O tenant vem do slug na URL, nunca de input arbitrário do corpo.
     */
    public function store(StoreTicketRequest $request, Tenant $tenant): JsonResponse
    {
        $ticket = DB::transaction(function () use ($request, $tenant) {
            $ticket = $tenant->tickets()->create([
                ...$request->validated(),
                'status' => 'open',
            ]);

            // Primeira mensagem da thread = o corpo enviado pelo cliente.
            // tenant_id é setado explicitamente (contexto público, sem login).
            $message = $ticket->messages()->make([
                'author_type' => 'customer',
                'author_name' => $ticket->requester_name,
                'body' => $ticket->body,
            ]);
            $message->tenant_id = $tenant->id;
            $message->save();

            return $ticket;
        });

        // Passo 4: dispatch(new ClassifyTicket($ticket)) — IA em fila.

        return TicketResource::make($ticket->load('messages'))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Resposta do agente (autenticado) na thread do ticket.
     */
    public function reply(StoreReplyRequest $request, Ticket $ticket): JsonResponse
    {
        $message = $ticket->messages()->create([
            'user_id' => $request->user()->id,
            'author_type' => 'agent',
            'author_name' => $request->user()->name,
            'body' => $request->validated()['body'],
        ]);
        // tenant_id é preenchido automaticamente pela trait (usuário logado).

        return MessageResource::make($message)
            ->response()
            ->setStatusCode(201);
    }
}
