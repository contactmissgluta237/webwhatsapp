// database/migrations/xxxx_create_countries_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // "Cameroon"
            $table->string('code', 2); // "CM"
            $table->string('phone_code', 5); // "+237"
            $table->string('flag_emoji', 10); // "🇨🇲"
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(999);
            $table->timestamps();
            
            $table->unique('code');
            $table->index(['is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('countries');
    }
};
