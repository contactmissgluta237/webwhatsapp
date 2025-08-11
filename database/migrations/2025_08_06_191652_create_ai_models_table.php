<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ai_models', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Ex: "ChatGPT 4o-mini", "Claude 3.5 Sonnet"
            $table->string('provider'); // Ex: "openai", "anthropic", "deepseek", "ollama"
            $table->string('model_identifier'); // Ex: "gpt-4o-mini", "claude-3-5-sonnet-20241022"
            $table->text('description')->nullable();
            $table->string('endpoint_url')->nullable(); // URL de l'API
            $table->boolean('requires_api_key')->default(true);
            $table->string('api_key')->nullable(); // Clé API (chiffrée)
            $table->json('model_config')->nullable(); // Configuration spécifique (temperature, max_tokens, etc.)
            $table->boolean('is_active')->default(false);
            $table->boolean('is_default')->default(false);
            $table->decimal('cost_per_1k_tokens', 10, 6)->nullable(); // Coût pour 1000 tokens
            $table->integer('max_context_length')->nullable(); // Longueur maximale du contexte
            $table->timestamps();
            
            // Index pour les requêtes fréquentes
            $table->index(['is_active', 'is_default']);
            $table->index('provider');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_models');
    }
};
