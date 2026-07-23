<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends Model
{
    /** @use HasFactory<\Database\Factories\TenantFactory> */
    use HasFactory;

    /**
     * Atributos preenchíveis em massa (mass assignment).
     * Equivalente a proteger contra "over-posting": só estes campos
     * podem ser setados via Tenant::create([...]).
     */
    protected $fillable = [
        'name',
        'slug',
    ];

    /**
     * Usuários (agentes) deste tenant.
     * hasMany ≈ @OneToMany do JPA (lado inverso da FK tenant_id).
     *
     * @return HasMany<User, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Tickets deste tenant.
     *
     * @return HasMany<Ticket, $this>
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }
}
