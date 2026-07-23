<?php

use App\Ai\AiProvider;
use App\Ai\Providers\FakeAiProvider;
use App\Jobs\ClassifyTicket;
use App\Models\Tenant;
use App\Models\Ticket;
use Illuminate\Support\Facades\Queue;

it('usa o FakeAiProvider por padrão (binding do container)', function () {
    expect(app(AiProvider::class))->toBeInstanceOf(FakeAiProvider::class);
});

it('enfileira a classificação ao abrir um ticket (não bloqueia o request)', function () {
    Queue::fake();

    $tenant = Tenant::factory()->create();

    $this->postJson("/t/{$tenant->slug}/tickets", [
        'requester_name' => 'Ana',
        'requester_email' => 'ana@ex.com',
        'subject' => 'Não consigo pagar a fatura',
        'body' => 'O pagamento do cartão dá erro.',
    ])->assertCreated()
        ->assertJsonPath('data.ai.category', null); // ainda não processado

    Queue::assertPushed(ClassifyTicket::class);
});

it('o job preenche categoria, prioridade, sentimento e rascunho', function () {
    $ticket = Ticket::factory()->create([
        'subject' => 'Erro 500 ao logar',
        'body' => 'Não consigo entrar, dá erro toda vez.',
    ]);

    (new ClassifyTicket($ticket->id))->handle(app(AiProvider::class));

    $ticket->refresh();

    expect($ticket->ai_processed_at)->not->toBeNull();
    expect($ticket->category)->not->toBeNull();
    expect($ticket->ai_suggested_reply)->not->toBeNull();
    expect(['low', 'medium', 'high', 'urgent'])->toContain($ticket->priority);
    expect(['positive', 'neutral', 'negative'])->toContain($ticket->sentiment);
});

it('não quebra se o ticket sumiu antes de processar', function () {
    (new ClassifyTicket(999999))->handle(app(AiProvider::class));

    expect(true)->toBeTrue(); // chegou aqui sem lançar exceção
});
