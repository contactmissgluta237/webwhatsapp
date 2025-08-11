<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('ai_contexts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('whatsapp_account_id')->constrained()->onDelete('cascade');
            $table->text('business_context');
            $table->enum('response_tone', ['friendly', 'professional', 'casual'])
                  ->default('friendly');
            $table->text('greeting_message')->nullable();

            // ⚠️ SOLUTION: Supprime le default() pour les colonnes TEXT
            $table->text('fallback_message')->nullable();

            $table->boolean('auto_reply_enabled')->default(true);
            $table->integer('response_delay_seconds')->default(2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_contexts');
    }
};
