<?php

namespace App\Http\Controllers;

use App\Events\TicketCreated;
use App\Http\Requests\StoreReplyRequest;
use App\Http\Requests\StoreTicketRequest;
use App\Http\Resources\TicketResource;
use App\Jobs\ClassifyTicket;
use App\Models\Tenant;
use App\Models\Ticket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class TicketController extends Controller
{
    /**
     * Board dos agentes (Inertia). O global scope (BelongsToTenant) já filtra
     * pelo tenant do usuário logado — nada de "buscar tudo e filtrar".
     */
    public function index(): InertiaResponse
    {
        return Inertia::render('tickets/index', [
            'tickets' => TicketResource::collection(Ticket::latest()->get())->resolve(),
            'tenantId' => request()->user()->tenant_id,
        ]);
    }

    /**
     * Detalhe do ticket + thread (Inertia). Route-model binding respeita o
     * global scope: ticket de outro tenant nem é encontrado (404).
     */
    public function show(Ticket $ticket): InertiaResponse
    {
        return Inertia::render('tickets/show', [
            'ticket' => TicketResource::make($ticket->load('messages'))->resolve(),
            'tenantId' => request()->user()->tenant_id,
        ]);
    }

    /**
     * Abertura PÚBLICA de ticket (JSON) — o "ticket chega".
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

        // Tempo real: avisa o board dos agentes que um ticket acabou de chegar.
        TicketCreated::dispatch($ticket);

        // Efeito colateral ASSÍNCRONO, fora do request (espírito outbox/@Async):
        // a IA classifica e rascunha em background — o cliente não espera o LLM.
        ClassifyTicket::dispatch($ticket->id);

        return TicketResource::make($ticket->load('messages'))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Resposta do agente (autenticado) na thread — volta pra tela do ticket.
     */
    public function reply(StoreReplyRequest $request, Ticket $ticket): RedirectResponse
    {
        $ticket->messages()->create([
            'user_id' => $request->user()->id,
            'author_type' => 'agent',
            'author_name' => $request->user()->name,
            'body' => $request->validated()['body'],
        ]);
        // tenant_id é preenchido automaticamente pela trait (usuário logado).

        return back();
    }
}
