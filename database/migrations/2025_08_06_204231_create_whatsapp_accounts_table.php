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