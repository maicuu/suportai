<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tenant extends Model
{
    /**
     * Atributos preenchíveis em massa (mass assignment).
     * Equivalente a proteger contra "over-posting": só estes campos
     * podem ser setados via Tenant::create([...]).
     */
    protected $fillable = [
        'name',
        'slug',
    ];
}
