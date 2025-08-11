<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\PaymentMethod;

return new class () extends Migration {
    public function up()
    {
        Schema::create('system_accounts', function (Blueprint $table) {
            $table->id();
            $table->enum('type', PaymentMethod::values());
            $table->decimal('balance', 15, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique('type');
        });
    }

    public function down()
    {
        Schema::dropIfExists('system_accounts');
    }
};
