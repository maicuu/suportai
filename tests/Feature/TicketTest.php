<?php

use App\Models\Tenant;
use App\Models\Ticket;
use App\Models\User;

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

it('lista apenas os tickets do tenant do agente logado', function () {
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();
    $agentA = User::factory()->for($tenantA)->create();

    Ticket::factory()->count(2)->for($tenantA)->create();
    Ticket::factory()->count(5)->for($tenantB)->create();

    $this->actingAs($agentA)
        ->getJson('/tickets')
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

it('não deixa o agente ver ticket de outro tenant (404)', function () {
    $agentA = User::factory()->create();
    $ticketB = Ticket::factory()->create(); // pertence a outro tenant

    $this->actingAs($agentA)
        ->getJson("/tickets/{$ticketB->id}")
        ->assertNotFound();
});

it('deixa o agente responder na thread do ticket', function () {
    $agent = User::factory()->create();
    $ticket = Ticket::factory()->for($agent->tenant)->create();

    $this->actingAs($agent)
        ->postJson("/tickets/{$ticket->id}/reply", ['body' => 'Olá, já estou verificando.'])
        ->assertCreated()
        ->assertJsonPath('data.author_type', 'agent')
        ->assertJsonPath('data.author_name', $agent->name);

    expect($ticket->messages()->count())->toBe(1);
});

it('exige autenticação para a área do agente', function () {
    $this->getJson('/tickets')->assertUnauthorized();
});
