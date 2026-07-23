<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ticket_id')->constrained()->cascadeOnDelete();
            // Autor agente (usuário logado). null quando a mensagem é do cliente.
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('author_type'); // customer | agent
            $table->string('author_name');
            $table->text('body');
            $table->timestamps();

            $table->index(['tenant_id', 'ticket_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
