<?php

use App\Enums\ExternalTransactionType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up()
    {
        Schema::create('system_account_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('system_account_id');
            $table->foreign('system_account_id')->references('id')->on('system_accounts')->onDelete('cascade');
            $table->enum('type', ExternalTransactionType::values());
            $table->decimal('amount', 15, 2);
            $table->string('sender_name')->nullable();
            $table->string('sender_account')->nullable();
            $table->string('receiver_name')->nullable();
            $table->string('receiver_account')->nullable();
            $table->text('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();

            $table->index(['system_account_id', 'created_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('payment_provider_transactions');
    }
};
