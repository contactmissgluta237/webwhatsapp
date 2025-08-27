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
        Schema::create('message_usage_logs', function (Blueprint $table) {
            $table->id();
            
            // Foreign keys
            $table->foreignId('whatsapp_message_id')->constrained()->onDelete('cascade');
            $table->foreignId('whatsapp_account_usage_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('whatsapp_conversation_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Coût détaillé
            $table->decimal('ai_message_cost', 8, 2)->default(0);
            $table->integer('product_messages_count')->default(0);
            $table->decimal('product_messages_cost', 8, 2)->default(0);
            $table->integer('media_count')->default(0);
            $table->decimal('media_cost', 8, 2)->default(0);
            $table->decimal('total_cost', 8, 2);
            
            // Type de facturation
            $table->enum('billing_type', ['subscription_quota', 'wallet_direct']);
            
            $table->timestamps();
            
            // Indexes pour agrégation rapide
            $table->index(['whatsapp_conversation_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index(['whatsapp_account_usage_id', 'billing_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('message_usage_logs');
    }
};
