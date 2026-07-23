<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    /** @use HasFactory<\Database\Factories\TicketFactory> */
    use BelongsToTenant, HasFactory;

    /**
     * Só campos vindos de quem abre o ticket são preenchíveis em massa.
     * tenant_id (isolamento) e os campos de IA são setados no servidor,
     * nunca por input do request.
     */
    protected $fillable = [
        'requester_name',
        'requester_email',
        'subject',
        'body',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'ai_processed_at' => 'datetime',
        ];
    }
}
