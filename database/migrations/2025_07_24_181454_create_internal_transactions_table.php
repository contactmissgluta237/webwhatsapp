<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\TransactionType;
use App\Enums\TransactionStatus;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('internal_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained()->onDelete('cascade');
            $table->decimal('amount', 10, 2);
            $table->enum('transaction_type', TransactionType::values());
            $table->enum('status', TransactionStatus::values());
            $table->text('description')->nullable();
            
            // Relations avec d'autres entités
            $table->string('related_type')->nullable(); // 'subscription', 'package', 'transfer', etc.
            $table->unsignedBigInteger('related_id')->nullable();
            $table->foreignId('recipient_user_id')->nullable()->constrained('users'); // Pour transferts
            
            // Traçabilité
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            
            // Index
            $table->index(['wallet_id', 'transaction_type']);
            $table->index(['wallet_id', 'status']);
            $table->index(['related_type', 'related_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('internal_transactions');
    }
};
