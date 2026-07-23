<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            // Isolamento multi-tenant.
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();

            // Quem abriu o ticket (formulário público / e-mail).
            $table->string('requester_name');
            $table->string('requester_email');

            $table->string('subject');
            $table->text('body');
            $table->string('status')->default('open'); // open | pending | closed

            // Campos preenchidos pela IA (Passo 4) — nullable até o job rodar.
            $table->string('category')->nullable();
            $table->string('priority')->nullable();   // low | medium | high | urgent
            $table->string('sentiment')->nullable();  // positive | neutral | negative
            $table->text('ai_suggested_reply')->nullable();
            $table->timestamp('ai_processed_at')->nullable();

            $table->timestamps();

            // Índice pra listagem do board escopada por tenant.
            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
