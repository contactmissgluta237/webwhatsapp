<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usage_subscription_trackers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_subscription_id')->constrained()->onDelete('cascade');
            
            // Période de tracking (basée sur la date d'abonnement)
            $table->date('cycle_start_date'); // Ex: 2025-01-15 si souscription le 15
            $table->date('cycle_end_date');   // Ex: 2025-02-15 (un mois après)
            
            // Compteurs de consommation
            $table->integer('messages_used')->default(0);
            $table->integer('messages_remaining');
            
            // Détail des coûts (pour analytics)
            $table->integer('base_messages_count')->default(0); // Messages simples
            $table->integer('media_messages_count')->default(0); // Médias envoyés
            
            // Utilisation des ressources
            $table->integer('accounts_linked')->default(0);
            $table->integer('products_linked')->default(0);
            
            // Suivi des dépassements payants
            $table->integer('overage_messages_used')->default(0); // Messages de dépassement
            $table->decimal('overage_cost_paid_xaf', 10, 2)->default(0); // Coût dépassement payé
            
            // Métadonnées
            $table->timestamp('last_message_at')->nullable();
            $table->timestamp('last_overage_payment_at')->nullable();
            $table->timestamp('last_reset_at')->nullable();
            $table->decimal('estimated_cost_xaf', 10, 2)->default(0); // Coût estimé
            
            $table->timestamps();
            
            // Contrainte d'unicité : un tracker par abonnement par cycle
            $table->unique(['user_subscription_id', 'cycle_start_date'], 'usage_trackers_unique');
            
            // Index pour les requêtes fréquentes
            $table->index(['cycle_start_date', 'cycle_end_date'], 'idx_cycle_dates');
            $table->index(['user_subscription_id', 'messages_remaining'], 'idx_subscription_remaining');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usage_subscription_trackers');
    }
};