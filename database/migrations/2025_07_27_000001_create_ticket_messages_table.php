<?php
// database/migrations/xxxx_create_ticket_messages_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('message');
            $table->string('sender_type', 20); // 'customer' or 'admin'
            $table->json('attachments')->nullable();
            $table->boolean('is_internal')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['ticket_id', 'created_at']);
            $table->index('user_id');
            $table->index('sender_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_messages');
    }
};
