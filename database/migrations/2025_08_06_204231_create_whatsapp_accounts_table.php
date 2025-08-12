<?php

use App\Enums\WhatsAppStatus;
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
        Schema::create('whatsapp_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('session_name'); // Supprimé unique() - nom libre pour l'utilisateur
            $table->string('session_id')->unique(); // ID unique pour WhatsApp Bridge
            $table->string('phone_number')->nullable();
            $table->enum('status', WhatsAppStatus::cases())
                  ->default(WhatsAppStatus::DISCONNECTED()->value);
            $table->text('qr_code')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->json('session_data')->nullable();
            
            // Configuration de l'agent IA
            $table->string('agent_name')->nullable();
            $table->boolean('agent_enabled')->default(false);
            $table->foreignId('ai_model_id')->nullable()->constrained()->onDelete('set null');
            $table->string('response_time')->default('random');
            $table->text('agent_prompt')->nullable();
            $table->json('trigger_words')->nullable();
            
            $table->text('contextual_information')->nullable();
            
            $table->json('ignore_words')->nullable();
            
            // Métadonnées pour l'IA
            $table->timestamp('last_ai_response_at')->nullable();
            $table->integer('daily_ai_responses')->default(0);
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_accounts');
    }
};