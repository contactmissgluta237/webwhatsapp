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
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->onDelete('cascade');
            $table->string('whatsapp_message_id')->nullable();
            $table->enum('direction', ['inbound', 'outbound']);
            $table->text('content');
            $table->enum('message_type', ['text', 'image', 'document', 'audio'])
                  ->default('text');
            $table->boolean('is_ai_generated')->default(false);
            $table->string('ai_model_used')->nullable();
            $table->float('ai_confidence', 3, 2)->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};