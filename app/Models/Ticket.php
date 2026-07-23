<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    /**
     * Thread de mensagens do ticket (cliente + agentes), em ordem cronológica.
     *
     * @return HasMany<Message, $this>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class)->orderBy('created_at');
    }

    /**
     * Payload enxuto do ticket para o board em tempo real (broadcast).
     *
     * @return array<string, mixed>
     */
    public function toBroadcastArray(): array
    {
        return [
            'id' => $this->id,
            'subject' => $this->subject,
            'requester_name' => $this->requester_name,
            'status' => $this->status,
            'category' => $this->category,
            'priority' => $this->priority,
            'sentiment' => $this->sentiment,
            'ai_suggested_reply' => $this->ai_suggested_reply,
            'created_at' => $this->created_at,
        ];
    }
}
