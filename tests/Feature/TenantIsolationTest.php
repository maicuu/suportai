<?php

use App\Models\Tenant;
use App\Models\Ticket;
use App\Models\User;

it('escopa as queries pelo tenant do usuário logado (tenant A não vê B)', function () {
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();

    $userA = User::factory()->for($tenantA)->create();

    Ticket::factory()->count(2)->for($tenantA)->create();
    Ticket::factory()->count(3)->for($tenantB)->create();

    $this->actingAs($userA);

    // Sem nenhum filtro manual: o global scope devolve só os do tenant A.
    expect(Ticket::count())->toBe(2)
        ->and(Ticket::all()->pluck('tenant_id')->unique()->all())->toBe([$tenantA->id]);
});

it('preenche tenant_id automaticamente ao criar autenticado', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $ticket = Ticket::create([
        'requester_name' => 'Cliente X',
        'requester_email' => 'x@cliente.com',
        'subject' => 'Não consigo logar',
        'body' => 'Recebo erro 500 ao entrar.',
    ]);

    expect($ticket->tenant_id)->toBe($user->tenant_id);
});

it('não vaza ticket de outro tenant nem ao buscar por id', function () {
    $userA = User::factory()->create();
    $ticketB = Ticket::factory()->create(); // pertence a outro tenant

    $this->actingAs($userA);

    expect(Ticket::find($ticketB->id))->toBeNull();
});
