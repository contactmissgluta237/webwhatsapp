<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('packages', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // trial, starter, business, pro
            $table->string('display_name'); // "Essai Gratuit", "Démarrage", etc.
            $table->string('description')->nullable();
            $table->decimal('price', 10, 2); // Prix en devise de base
            $table->string('currency', 3)->default('XAF');
            
            // Limitations
            $table->integer('messages_limit')->default(0);
            $table->integer('context_limit')->default(1000); // Caractères
            $table->integer('accounts_limit')->default(1);
            $table->integer('products_limit')->default(0);
            
            // Spécificités temporelles
            $table->integer('duration_days')->nullable(); // Pour trial (7 jours)
            $table->boolean('is_recurring')->default(true); // Mensuel ou one-shot
            $table->boolean('one_time_only')->default(false); // Trial = une seule fois
            
            // Fonctionnalités avancées
            $table->json('features')->nullable(); // ["weekly_reports", "priority_support"]
            
            // État
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('packages');
    }
};