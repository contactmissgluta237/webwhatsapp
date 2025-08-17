<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_account_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('whatsapp_account_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_product_id')->constrained()->onDelete('cascade');
            $table->timestamps();
            
            $table->unique(['whatsapp_account_id', 'user_product_id'], 'wa_account_product_unique');
            $table->index('whatsapp_account_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_account_products');
    }
};