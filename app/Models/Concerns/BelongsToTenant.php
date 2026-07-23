<?php

namespace App\Models\Concerns;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

/**
 * Isolamento multi-tenant AUTOMÁTICO para um model.
 *
 * Duas garantias, para nunca depender de o dev "lembrar" de escopar:
 *
 *  1) GLOBAL SCOPE — toda consulta ganha "WHERE tenant_id = <tenant do usuário
 *     logado>". Assim, Ticket::all() já vem filtrado; é impossível "buscar tudo
 *     e filtrar na mão". Análogo ao @Filter/@Where do Hibernate, mas dirigido
 *     pelo usuário autenticado.
 *
 *  2) AUTO-FILL no create — ao criar um registro autenticado, tenant_id é
 *     preenchido a partir do usuário logado, NUNCA de input do request.
 *
 * Sem usuário autenticado (fila/console/seed), o scope não é aplicado — nesses
 * contextos o tenant é definido explicitamente (ex.: pela factory ou pelo Job).
 */
trait BelongsToTenant
{
    protected static function bootBelongsToTenant(): void
    {
        static::creating(function (Model $model): void {
            if (empty($model->tenant_id) && Auth::check()) {
                $model->tenant_id = Auth::user()->tenant_id;
            }
        });

        static::addGlobalScope('tenant', function (Builder $builder): void {
            if (Auth::check()) {
                $builder->where(
                    $builder->getModel()->getTable().'.tenant_id',
                    Auth::user()->tenant_id,
                );
            }
        });
    }

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
