<?php

namespace Database\Factories;

use App\Models\Message;
use App\Models\Ticket;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Message>
 */
class MessageFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'ticket_id' => Ticket::factory(),
            // Mantém o tenant da mensagem coerente com o do ticket.
            'tenant_id' => fn (array $attrs) => Ticket::withoutGlobalScopes()->find($attrs['ticket_id'])->tenant_id,
            'user_id' => null,
            'author_type' => 'customer',
            'author_name' => fake()->name(),
            'body' => fake()->paragraph(),
        ];
    }
}
