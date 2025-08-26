<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('package_id')->constrained()->onDelete('cascade');
            
            // Période d'abonnement
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            
            // État de l'abonnement
            $table->enum('status', ['active', 'expired', 'cancelled', 'suspended'])->default('active');
            
            // Limites du package (copiées depuis le package lors de l'abonnement)
            $table->integer('messages_limit')->default(0);
            $table->integer('context_limit')->default(0);
            $table->integer('accounts_limit')->default(1);
            $table->integer('products_limit')->default(0);
            
            // Métadonnées de paiement
            $table->decimal('amount_paid', 10, 2)->nullable();
            $table->string('payment_method')->nullable();
            $table->string('transaction_id')->nullable();
            
            // Historique
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancellation_reason')->nullable();
            
            $table->timestamps();
            
            // Index pour les requêtes fréquentes
            $table->index(['user_id', 'status']);
            $table->index(['status', 'ends_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_subscriptions');
    }
};