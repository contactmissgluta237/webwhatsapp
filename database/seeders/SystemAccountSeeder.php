<?php

namespace Database\Seeders;

use App\Enums\PaymentMethod;
use App\Models\SystemAccount;
use Illuminate\Database\Seeder;

class SystemAccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (PaymentMethod::cases() as $paymentMethod) {
            SystemAccount::firstOrCreate(
                ['type' => $paymentMethod->value],
                ['balance' => 0, 'is_active' => true]
            );
        }
    }
}
