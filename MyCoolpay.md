🔄 Intégration dans ExternalTransactionService
Customer Recharge :
<?php
// Dans ton ExternalTransactionService existant

use App\Services\Payment\PaymentService;
use App\Services\Payment\DTOs\PaymentInitiateDTO;
use App\Services\Payment\Exceptions\PaymentException;

public function createCustomerRecharge(CreateCustomerRechargeDTO $dto): ExternalTransaction
{
    return DB::transaction(function () use ($dto) {
        $customer = Customer::findOrFail($dto->customer_id);
        $wallet = $customer->wallet;

        $transactionData = [
            'reference' => $this->generateReference('RCH'),
            'transaction_id' => $dto->payment_method->prefix() . '_' . uniqid(),
            'type' => TransactionType::RECHARGE(),
            'mode' => $dto->mode,
            'status' => TransactionStatus::PENDING(),
            'amount' => $dto->amount,
            'payment_method' => $dto->payment_method,
            'description' => $dto->description ?? "Recharge wallet for {$customer->full_name}",
            'sender_name' => $customer->full_name,
            'sender_account' => $wallet->id,
            'receiver_name' => 'System Recharge',
            'receiver_account' => 'SYSTEM',
            'customer_id' => $customer->id,
        ];

        $transaction = ExternalTransaction::create($transactionData);

        // ✨ NOUVEAU : Si mode automatique, lancer le paiement
        if ($dto->mode->equals(TransactionMode::AUTOMATIC())) {
            try {
                $paymentResult = $this->initiateCustomerRecharge($transaction, $dto, $customer);
                
                // Update transaction with payment gateway info
                $transaction->update([
                    'external_transaction_id' => $paymentResult->transaction_id,
                    'api_response' => json_encode($paymentResult->gateway_data),
                    'status' => $this->mapPaymentStatusToTransactionStatus($paymentResult->status),
                ]);

                // Si le paiement est déjà complété (synchrone), créditer le wallet
                if ($paymentResult->status->equals(PaymentStatus::COMPLETED())) {
                    $wallet->increment('balance', $dto->amount);
                    $transaction->update(['status' => TransactionStatus::COMPLETED()]);
                }

            } catch (PaymentException $e) {
                $transaction->update([
                    'status' => TransactionStatus::REJECTED(),
                    'api_response' => json_encode(['error' => $e->getMessage()]),
                ]);
            }
        }

        CustomerRechargeCreatedEvent::dispatch($transaction);
        return $transaction;
    });
}

private function initiateCustomerRecharge(
    ExternalTransaction $transaction, 
    CreateCustomerRechargeDTO $dto, 
    Customer $customer
): PaymentResponseDTO {
    $paymentDTO = new PaymentInitiateDTO([
        'amount' => $dto->amount,
        'payment_method' => $dto->payment_method,
        'reference' => $transaction->reference,
        'customer_phone' => $dto->customer_phone ?? $customer->phone,
        'customer_name' => $customer->full_name,
        'customer_email' => $dto->customer_email ?? $customer->email,
        'description' => $dto->description,
    ]);

    return app(PaymentService::class)->initiateRecharge($paymentDTO);
}

Customer Withdrawal :
<?php
// Dans ton ExternalTransactionService existant

public function createCustomerWithdrawal(CreateCustomerWithdrawalDTO $dto): ExternalTransaction
{
    return DB::transaction(function () use ($dto) {
        $customer = Customer::findOrFail($dto->customer_id);
        $wallet = $customer->wallet;

        // Vérifier le solde
        if ($wallet->balance < $dto->amount) {
            throw new \InvalidArgumentException('Insufficient balance');
        }

        $transactionData = [
            'reference' => $this->generateReference('WDR'),
            'transaction_id' => $dto->payment_method->prefix() . '_' . uniqid(),
            'type' => TransactionType::WITHDRAWAL(),
            'mode' => $dto->mode,
            'status' => TransactionStatus::PENDING(),
            'amount' => $dto->amount,
            'payment_method' => $dto->payment_method,
            'description' => $dto->description ?? "Withdrawal for {$customer->full_name}",
            'sender_name' => $customer->full_name,
            'sender_account' => $wallet->id,
            'receiver_name' => $customer->full_name,
            'receiver_account' => $dto->recipient_phone ?? $customer->phone,
            'customer_id' => $customer->id,
        ];

        $transaction = ExternalTransaction::create($transactionData);

        // ✨ NOUVEAU : Si mode automatique, lancer le retrait
        if ($dto->mode->equals(TransactionMode::AUTOMATIC())) {
            try {
                $paymentResult = $this->initiateCustomerWithdrawal($transaction, $dto, $customer);
                
                // Update transaction with payment gateway info
                $transaction->update([
                    'external_transaction_id' => $paymentResult->transaction_id,
                    'api_response' => json_encode($paymentResult->gateway_data),
                    'status' => $this->mapPaymentStatusToTransactionStatus($paymentResult->status),
                ]);

                // Si le retrait est réussi, débiter le wallet
                if ($paymentResult->success && $paymentResult->status->equals(PaymentStatus::COMPLETED())) {
                    $wallet->decrement('balance', $dto->amount);
                    $transaction->update(['status' => TransactionStatus::COMPLETED()]);
                } elseif ($paymentResult->status->equals(PaymentStatus::FAILED())) {
                    $transaction->update(['status' => TransactionStatus::REJECTED()]);
                }

            } catch (PaymentException $e) {
                $transaction->update([
                    'status' => TransactionStatus::REJECTED(),
                    'api_response' => json_encode(['error' => $e->getMessage()]),
                ]);
            }
        } else {
            // Mode manuel - débiter directement
            $wallet->decrement('balance', $dto->amount);
        }

        CustomerWithdrawalCreatedEvent::dispatch($transaction);
        return $transaction;
    });
}

private function initiateCustomerWithdrawal(
    ExternalTransaction $transaction, 
    CreateCustomerWithdrawalDTO $dto, 
    Customer $customer
): PaymentResponseDTO {
    $paymentDTO = new PaymentInitiateDTO([
        'amount' => $dto->amount,
        'payment_method' => $dto->payment_method,
        'reference' => $transaction->reference,
        'customer_phone' => $dto->recipient_phone ?? $customer->phone,
        'customer_name' => $customer->full_name,
        'customer_email' => $customer->email,
        'description' => $dto->description,
    ]);

    return app(PaymentService::class)->initiateWithdrawal($paymentDTO);
}

⚙️ Configuration
<?php
// .env
MYCOOLPAY_PUBLIC_KEY=your_public_key
MYCOOLPAY_PRIVATE_KEY=your_private_key

<?php
// config/services.php
'mycoolpay' => [
    'public_key' => env('MYCOOLPAY_PUBLIC_KEY'),
    'private_key' => env('MYCOOLPAY_PRIVATE_KEY'),
],

🎯 Flux Simple :
Recharge : Crée transaction → Appelle MyCoolPay → Retourne URL de paiement ou status
Retrait : Vérifie solde → Crée transaction → Appelle MyCoolPay → Débite si succès
Synchrone : Réponse immédiate, pas de webhook nécessaire
C'est beaucoup plus simple ! 🚀