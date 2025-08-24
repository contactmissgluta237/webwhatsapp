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
        Schema::create('whatsapp_account_usages', function (Blueprint $table) {
            $table->id();
            
            // Relations
            $table->foreignId('user_subscription_id')->constrained()->onDelete('cascade');
            $table->foreignId('whatsapp_account_id')->constrained()->onDelete('cascade');
            
            // Usage tracking per account
            $table->integer('messages_used')->default(0);
            $table->integer('base_messages_count')->default(0);
            $table->integer('media_messages_count')->default(0);
            
            // Overage tracking per account
            $table->integer('overage_messages_used')->default(0);
            $table->decimal('overage_cost_paid_xaf', 10, 2)->default(0);
            
            // Metadata
            $table->timestamp('last_message_at')->nullable();
            $table->timestamp('last_overage_payment_at')->nullable();
            $table->decimal('estimated_cost_xaf', 10, 2)->default(0);
            
            $table->timestamps();
            
            // Unique constraint: one usage tracker per account per subscription
            $table->unique(['user_subscription_id', 'whatsapp_account_id'], 'unique_subscription_account');
            
            // Indexes for frequent queries
            $table->index(['whatsapp_account_id', 'messages_used']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_account_usages');
    }
};
