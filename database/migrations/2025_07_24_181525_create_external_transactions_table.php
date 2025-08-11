<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\ExternalTransactionType;
use App\Enums\TransactionMode;
use App\Enums\TransactionStatus;
use App\Enums\PaymentMethod;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('external_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained()->onDelete('cascade');
            $table->decimal('amount', 10, 2);
            $table->enum('transaction_type', ExternalTransactionType::values());
            $table->enum('mode', TransactionMode::values());
            $table->enum('status', TransactionStatus::values());
            $table->string('external_transaction_id');
            $table->text('description')->nullable();
            
            // Pour les transactions automatiques (gateways)
            $table->enum('payment_method', PaymentMethod::values())->nullable();
            $table->string('gateway_transaction_id')->nullable();
            $table->text('gateway_response')->nullable();
            
            // Pour les transactions manuelles (admin)
            $table->string('sender_name')->nullable();
            $table->string('sender_account')->nullable();
            
            $table->string('receiver_name')->nullable();
            $table->string('receiver_account')->nullable();
            
            // Traçabilité et approbation
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users'); // Pour l'approbation des retraits
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            
            // Contrainte d'unicité
            $table->unique(['external_transaction_id', 'payment_method'], 'unique_external_transaction');
            
            // Index
            $table->index(['wallet_id', 'transaction_type']);
            $table->index('payment_method');
            $table->index('status');
            $table->index('approved_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('external_transactions');
    }
};
