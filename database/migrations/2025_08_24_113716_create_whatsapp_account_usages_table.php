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
            
            // Relations - user_subscription_id NULLABLE pour débit wallet direct
            $table->foreignId('user_subscription_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('whatsapp_account_id')->constrained()->onDelete('cascade');
            
            $table->timestamps();
            
            // Un usage par subscription/account OU account-only (sans subscription)
            $table->unique(['user_subscription_id', 'whatsapp_account_id'], 'unique_usage');
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
