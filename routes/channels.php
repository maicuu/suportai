<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Canal PRIVADO por tenant: um agente só pode escutar os tickets do seu
// próprio tenant. A autorização vem do usuário logado (nunca de input) —
// mesma regra de ouro do isolamento multi-tenant, agora no WebSocket.
Broadcast::channel('tenant.{tenantId}', function (User $user, string $tenantId) {
    return (int) $user->tenant_id === (int) $tenantId;
});
