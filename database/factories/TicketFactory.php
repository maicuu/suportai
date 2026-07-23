<?php

namespace Database\Factories;

use App\Models\Tenant;
use App\Models\Ticket;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ticket>
 */
class TicketFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'requester_name' => fake()->name(),
            'requester_email' => fake()->safeEmail(),
            'subject' => fake()->sentence(),
            'body' => fake()->paragraph(),
            'status' => 'open',
        ];
    }
}
