<?php

namespace Database\Seeders;

use App\Models\Tenant;
use Illuminate\Database\Seeder;

class DemoSeeder extends Seeder
{
    /**
     * Cria um cenário de demo: tenant "demo" + um agente pra logar.
     * Rode com: php artisan db:seed --class=DemoSeeder
     * Login: agente@demo.test / password
     */
    public function run(): void
    {
        $tenant = Tenant::firstOrCreate(
            ['slug' => 'demo'],
            ['name' => 'Demo Co'],
        );

        // tenant_id é setado pela relação (nunca por mass-assignment).
        $tenant->users()->firstOrCreate(
            ['email' => 'agente@demo.test'],
            [
                'name' => 'Agente Demo',
                'password' => 'password',
                'email_verified_at' => now(),
            ],
        );
    }
}
