<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Ticket
 */
class TicketResource extends JsonResource
{
    /**
     * Molda a saída do ticket. NÃO expõe tenant_id (detalhe de isolamento).
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'requester_name' => $this->requester_name,
            'requester_email' => $this->requester_email,
            'subject' => $this->subject,
            'body' => $this->body,
            'status' => $this->status,
            'ai' => [
                'category' => $this->category,
                'priority' => $this->priority,
                'sentiment' => $this->sentiment,
                'suggested_reply' => $this->ai_suggested_reply,
                'processed_at' => $this->ai_processed_at,
            ],
            'messages' => MessageResource::collection($this->whenLoaded('messages')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
