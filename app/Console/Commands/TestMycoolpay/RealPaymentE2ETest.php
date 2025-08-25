<?php

namespace App\Console\Commands\TestMycoolpay;

use App\Enums\ExternalTransactionType;
use App\Enums\PaymentMethod;
use App\Enums\TransactionMode;
use App\Enums\TransactionStatus;
use App\Models\ExternalTransaction;
use App\Models\Geography\Country;
use App\Models\User;
use App\Services\Payment\DTOs\PaymentIdentifierRequestDTO;
use App\Services\Payment\Gateways\MyCoolPayGateway;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RealPaymentE2ETest extends Command
{
    protected $signature = 'payment-mycoolpay:test-e2e {--amount=100 : Amount to recharge in XAF} {--phone=655332183 : Phone number for payment}';

    protected $description = 'Real E2E Payment Test with customer1@example.com and MyCoolPay';

    public function handle(): int
    {
        if ($this->isProduction()) {
            $this->error('❌ Cannot run test commands in production environment!');
            Log::warning('Attempted to run MyCoolPay test command in production', [
                'command' => $this->getName(),
                'environment' => app()->environment(),
            ]);

            return Command::FAILURE;
        }

        $this->displayHeader();

        $amount = (int) $this->option('amount');
        $phoneNumber = $this->option('phone');

        // Step 1: Setup test user
        $this->info('🔧 STEP 1: Setting up test environment...');
        $user = $this->getOrCreateTestUser();

        if (! $user) {
            $this->error('❌ Failed to setup test user');

            return self::FAILURE;
        }

        $this->displayUserInfo($user, $phoneNumber);

        // Step 2: Check MyCoolPay configuration
        $this->info("\n🔧 STEP 2: Verifying MyCoolPay configuration...");
        if (! $this->checkMyCoolPayConfig()) {
            return self::FAILURE;
        }

        // Step 3: Launch real payment
        $this->info("\n🚀 STEP 3: Launching REAL payment with MyCoolPay...");
        $this->warn('⚠️  This will make a REAL API call to MyCoolPay!');
        $this->warn("⚠️  Amount: {$amount} XAF will be charged to +237{$phoneNumber}");

        if (! $this->confirm('Continue with real payment?')) {
            $this->info('Test cancelled by user');

            return self::SUCCESS;
        }

        $result = $this->processRealPayment($user, $amount, $phoneNumber);

        if (! $result) {
            return self::FAILURE;
        }

        // Step 4: Wait for mobile confirmation
        $this->info("\n📱 STEP 4: Waiting for your mobile money validation...");
        $this->displayMobileInstructions($result);

        // Step 5: Instructions for webhook testing
        $this->info("\n🔗 STEP 5: Ready for webhook testing!");
        $this->displayWebhookInstructions($result);

        return self::SUCCESS;
    }

    private function displayHeader(): void
    {
        $this->info('============================================');
        $this->info('🧪 REAL E2E PAYMENT TEST - MyCoolPay');
        $this->info('============================================');
        $this->info('Customer: customer1@example.com');
        $this->info('Gateway: MyCoolPay (Cameroon)');
        $this->info("============================================\n");
    }

    private function getOrCreateTestUser(): ?User
    {
        Log::info('🔍 Searching for test user: customer1@example.com');

        $cameroon = Country::where('code', 'CM')->first();
        if (! $cameroon) {
            $this->error('❌ Cameroon country not found in database');

            return null;
        }

        $user = User::where('email', 'customer1@example.com')->first();

        if (! $user) {
            $this->info('👤 Creating test user customer1@example.com...');
            $user = User::factory()->create([
                'email' => 'customer1@example.com',
                'phone_number' => '+237655332183',
                'first_name' => 'Test',
                'last_name' => 'Customer E2E',
                'country_id' => $cameroon->id,
                'locale' => 'fr',
                'currency' => 'XAF',
            ]);

            Log::info('✅ Test user created successfully', [
                'user_id' => $user->id,
                'email' => $user->email,
                'phone' => $user->phone_number,
            ]);
        } else {
            Log::info('✅ Test user found', [
                'user_id' => $user->id,
                'email' => $user->email,
                'phone' => $user->phone_number,
            ]);
        }

        return $user;
    }

    private function displayUserInfo(User $user, string $phoneNumber): void
    {
        $this->table(['Field', 'Value'], [
            ['ID', $user->id],
            ['Email', $user->email],
            ['Phone', $phoneNumber],
            ['Name', $user->first_name.' '.$user->last_name],
            ['Country', $user->country->name.' ('.$user->country->code.')'],
            ['Currency', $user->currency ?? 'XAF'],
            ['Locale', $user->locale ?? 'fr'],
        ]);
    }

    private function checkMyCoolPayConfig(): bool
    {
        $apiUrl = config('services.mycoolpay.api_url');
        $publicKey = config('services.mycoolpay.public_key');
        $privateKey = config('services.mycoolpay.private_key');

        $this->table(['Config', 'Value', 'Status'], [
            ['API URL', $apiUrl ?: 'NOT SET', $apiUrl ? '✅' : '❌'],
            ['Public Key', $publicKey ? substr($publicKey, 0, 10).'...' : 'NOT SET', $publicKey ? '✅' : '❌'],
            ['Private Key', $privateKey ? 'SET ('.strlen($privateKey).' chars)' : 'NOT SET', $privateKey ? '✅' : '❌'],
        ]);

        if (! $apiUrl || ! $publicKey || ! $privateKey) {
            $this->error('❌ MyCoolPay configuration incomplete!');
            $this->info('Please set in .env:');
            $this->info('- MYCOOLPAY_PUBLIC_KEY');
            $this->info('- MYCOOLPAY_PRIVATE_KEY');

            return false;
        }

        $this->info('✅ MyCoolPay configuration is complete');

        return true;
    }

    private function processRealPayment(User $user, int $amount, string $phoneNumber): ?object
    {
        try {
            Log::info('🚀 LAUNCHING REAL E2E PAYMENT', [
                'user_email' => $user->email,
                'user_phone' => $user->phone_number,
                'payment_phone' => $phoneNumber,
                'amount' => $amount,
                'currency' => $user->currency ?? 'XAF',
                'timestamp' => now()->toISOString(),
            ]);

            $this->info('💳 Processing payment with MyCoolPay Gateway...');

            // Créer d'abord une ExternalTransaction pour le test
            $transaction = ExternalTransaction::create([
                'wallet_id' => $user->wallet->id,
                'amount' => $amount,
                'transaction_type' => ExternalTransactionType::RECHARGE(),
                'mode' => TransactionMode::AUTOMATIC(),
                'status' => TransactionStatus::PENDING(),
                'external_transaction_id' => 'test-e2e-'.now()->timestamp,
                'description' => 'E2E Test Payment',
                'payment_method' => PaymentMethod::ORANGE_MONEY(),
                'created_by' => $user->id,
            ]);

            // Appel au gateway avec la transaction et le request
            $gateway = app(MyCoolPayGateway::class);
            $paymentRequest = new PaymentIdentifierRequestDTO(
                phoneNumber: $phoneNumber,
            );

            $result = $gateway->initiatePayment($transaction, $paymentRequest);

            Log::info('💰 MYCOOLPAY RESPONSE RECEIVED', [
                'status' => $result->status,
                'transaction_ref' => $result->transaction_ref,
                'external_transaction_id' => $transaction->external_transaction_id,
                'action' => $result->action,
                'ussd' => $result->ussd,
            ]);
            $transaction->update([
                'gateway_transaction_id' => $result->transaction_ref,
            ]);

            if ($result->isSuccess()) {
                $this->info('✅ Payment initiated successfully!');

                $this->table(['Field', 'Value'], [
                    ['Status', $result->status],
                    ['Transaction Ref', $result->transaction_ref],
                    ['External Transaction ID', $transaction->external_transaction_id],
                    ['Action', $result->action],
                    ['USSD Code', $result->ussd ?? 'N/A'],
                    ['Message', $result->getUserMessageToDisplay()],
                ]);

                return $result;
            } else {
                $this->error('❌ Payment initiation failed!');
                $this->error('Status: '.$result->status);

                Log::error('💥 PAYMENT INITIATION FAILED', [
                    'status' => $result->status,
                    'user_id' => $user->id,
                ]);

                return null;
            }

        } catch (\Exception $e) {
            $this->error('💥 Exception during payment processing:');
            $this->error($e->getMessage());

            Log::error('💥 PAYMENT PROCESSING EXCEPTION', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $user->id,
                'amount' => $amount,
            ]);

            return null;
        }
    }

    private function displayMobileInstructions(object $result): void
    {
        $this->info('📱 Instructions for mobile validation:');
        $this->info('─────────────────────────────────────');

        if (! empty($result->ussd)) {
            $this->info('1. 📞 Compose: '.$result->ussd);
            $this->info('2. ✅ Follow the prompts to confirm payment');
        } else {
            $this->info('1. 📱 Check your phone for payment notification');
            $this->info('2. ✅ Confirm the payment in your mobile money app');
        }

        $this->info('3. 💬 '.$result->getUserMessageToDisplay());
        $this->info('─────────────────────────────────────');
        $this->warn('⏳ Validate the transaction on your mobile NOW!');
        $this->warn('⏳ Transaction Ref: '.$result->transaction_ref);
    }

    private function displayWebhookInstructions(object $result): void
    {
        // Utiliser l'app_transaction_ref stocké dans le result
        $appTransactionRef = $result->app_transaction_ref ?? 'unknown';

        $this->info('🔗 Webhook testing commands:');
        $this->info('─────────────────────────────────────');

        $this->info('For SUCCESS webhook:');
        $this->line("<fg=green>php artisan test-mycoolpay-recharge {$appTransactionRef} --status=SUCCESS</>");

        $this->info("\nFor FAILED webhook:");
        $this->line("<fg=red>php artisan test-mycoolpay-recharge {$appTransactionRef} --status=FAILED</>");

        $this->info('─────────────────────────────────────');
        $this->info('💡 Run these commands AFTER confirming on your mobile');

        $this->info("\n🔍 Watch logs in real-time:");
        $this->line("<fg=blue>tail -f storage/logs/laravel.log | grep -E 'E2E|MyCoolPay'</>");
    }

    private function isProduction(): bool
    {
        return app()->environment('production');
    }
}
