<?php

use App\Enums\MessageType;
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
        Schema::create('whatsapp_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('whatsapp_conversation_id')->constrained('whatsapp_conversations')->onDelete('cascade');
            $table->string('whatsapp_message_id')->nullable();
            $table->enum('direction', ['inbound', 'outbound']);
            $table->text('content');
            $table->enum('message_type', MessageType::cases())
                  ->default('text');
            $table->boolean('is_ai_generated')->default(false);
            $table->string('ai_model_used')->nullable();
            $table->float('ai_confidence', 3, 2)->nullable();
            $table->json('metadata')->nullable(); // Pour stocker des infos supplémentaires comme taille fichier, etc.
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            // Index pour optimiser les recherches
            $table->index(['whatsapp_conversation_id', 'created_at']);
            $table->index('direction');
            $table->index('message_type');
            $table->index('whatsapp_message_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_messages');
    }
};