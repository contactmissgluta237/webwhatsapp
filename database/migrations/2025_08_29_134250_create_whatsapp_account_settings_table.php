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
        Schema::create('whatsapp_account_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('whatsapp_account_id')->constrained('whatsapp_accounts')->onDelete('cascade');
            
            // Notification settings
            $table->boolean('enable_email_notifications')->default(false);
            $table->string('notification_email')->nullable();
            $table->boolean('enable_whatsapp_notifications')->default(false);
            $table->string('notification_whatsapp_number')->nullable();
            
            $table->timestamps();

            $table->unique('whatsapp_account_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_account_settings');
    }
};