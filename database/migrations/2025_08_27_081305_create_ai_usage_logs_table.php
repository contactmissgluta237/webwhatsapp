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
        Schema::create('ai_usage_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('whatsapp_account_id')->constrained()->onDelete('cascade');
            $table->foreignId('whatsapp_conversation_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('whatsapp_message_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('ai_model');
            $table->string('provider');
            
            // Token details
            $table->integer('prompt_tokens');
            $table->integer('completion_tokens');
            $table->integer('total_tokens');
            $table->integer('cached_tokens')->default(0);
            
            // Cost details
            $table->decimal('prompt_cost_usd', 10, 6);
            $table->decimal('completion_cost_usd', 10, 6); 
            $table->decimal('cached_cost_usd', 10, 6)->default(0);
            $table->decimal('total_cost_usd', 10, 6);
            $table->decimal('total_cost_xaf', 10, 2);
            
            // Request details
            $table->integer('request_length');
            $table->integer('response_length');
            $table->integer('api_attempts')->default(1);
            $table->integer('response_time_ms')->nullable();
            
            $table->timestamps();
            
            // Indexes for reporting queries
            $table->index(['user_id', 'created_at']);
            $table->index(['whatsapp_account_id', 'created_at']);
            $table->index(['whatsapp_conversation_id', 'created_at']);
            $table->index(['created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_usage_logs');
    }
};
