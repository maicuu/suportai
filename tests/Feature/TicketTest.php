<?php

use App\Models\Tenant;
use App\Models\Ticket;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

it('permite abertura pública de ticket escopada ao tenant do slug', function () {
    $tenant = Tenant::factory()->create();

    $response = $this->postJson("/t/{$tenant->slug}/tickets", [
        'requester_name' => 'Cliente Feliz',
        'requester_email' => 'cliente@ex.com',
        'subject' => 'Não consigo acessar',
        'body' => 'Recebo erro 500 ao logar.',
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.subject', 'Não consigo acessar')
        ->assertJsonPath('data.status', 'open')
        ->assertJsonPath('data.messages.0.author_type', 'customer');

    $ticket = Ticket::withoutGlobalScopes()->first();
    expect($ticket->tenant_id)->toBe($tenant->id)
        ->and($ticket->messages()->withoutGlobalScopes()->count())->toBe(1);
});

it('valida os campos obrigatórios na abertura do ticket', function () {
    $tenant = Tenant::factory()->create();

    $this->postJson("/t/{$tenant->slug}/tickets", [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['requester_name', 'requester_email', 'subject', 'body']);
});

it('lista no board apenas os tickets do tenant do agente logado', function () {
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();
    $agentA = User::factory()->for($tenantA)->create();

    Ticket::factory()->count(2)->for($tenantA)->create();
    Ticket::factory()->count(5)->for($tenantB)->create();

    $this->actingAs($agentA)
        ->get('/tickets')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('tickets/index')
            ->has('tickets', 2)
        );
});

it('mostra o ticket com a thread para o agente do tenant', function () {
    $agent = User::factory()->create();
    $ticket = Ticket::factory()->for($agent->tenant)->create();

    $this->actingAs($agent)
        ->get("/tickets/{$ticket->id}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('tickets/show')
            ->where('ticket.id', $ticket->id)
        );
});

it('não deixa o agente ver ticket de outro tenant (404)', function () {
    $agentA = User::factory()->create();
    $ticketB = Ticket::factory()->create(); // pertence a outro tenant

    $this->actingAs($agentA)
        ->get("/tickets/{$ticketB->id}")
        ->assertNotFound();
});

it('deixa o agente responder na thread (redirect back)', function () {
    $agent = User::factory()->create();
    $ticket = Ticket::factory()->for($agent->tenant)->create();

    $this->actingAs($agent)
        ->from("/tickets/{$ticket->id}")
        ->post("/tickets/{$ticket->id}/reply", ['body' => 'Olá, já estou verificando.'])
        ->assertRedirect("/tickets/{$ticket->id}");

    expect($ticket->messages()->count())->toBe(1)
        ->and($ticket->messages()->first()->author_type)->toBe('agent');
});

it('exige autenticação para o board', function () {
    $this->get('/tickets')->assertRedirect('/login');
});
