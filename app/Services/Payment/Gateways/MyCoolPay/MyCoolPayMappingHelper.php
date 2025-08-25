<?php

declare(strict_types=1);

namespace App\Services\Payment\Gateways\MyCoolPay;

use App\Enums\ExternalTransactionType;
use App\Enums\PaymentMethod;
use App\Models\ExternalTransaction;
use App\Services\Payment\Gateways\MyCoolPay\Enums\MyCoolPayCurrency;
use App\Services\Payment\Gateways\MyCoolPay\Enums\MyCoolPayOperator;
use App\Services\Payment\Gateways\MyCoolPay\Enums\MyCoolPayTransactionType;
use Illuminate\Support\Facades\Log;

final class MyCoolPayMappingHelper
{
    /**
     * Génère la signature MD5 pour une transaction MyCoolPay
     */
    public static function generateSignature(ExternalTransaction $transaction): string
    {
        $privateKey = config('services.mycoolpay.private_key');

        $transactionRef = $transaction->gateway_transaction_id;
        $transactionType = self::mapTransactionType($transaction->transaction_type)->value;
        $transactionAmount = (string) ((int) $transaction->amount);
        $transactionCurrency = self::mapCurrency($transaction)->value;
        $transactionOperator = self::mapOperator($transaction)->value;
        Log::debug('Mapping MyCoolPay signature data', [
            'transaction_ref' => $transactionRef,
            'transaction_type' => $transactionType,
            'transaction_amount' => $transactionAmount,
            'transaction_currency' => $transactionCurrency,
            'transaction_operator' => $transactionOperator,
        ]);

        $signatureString = $transactionRef
            .$transactionType
            .$transactionAmount
            .$transactionCurrency
            .$transactionOperator
            .$privateKey;

        return md5($signatureString);
    }

    /**
     * Convertit un MyCoolPayOperator (string) en PaymentMethod
     * Utilisé lors de la réception du webhook
     */
    public static function operatorToPaymentMethod(string $operator): PaymentMethod
    {
        return match (strtoupper($operator)) {
            'CM_MOMO' => PaymentMethod::MOBILE_MONEY(),
            'CM_OM' => PaymentMethod::ORANGE_MONEY(),
            'CARD', 'MCP' => PaymentMethod::BANK_CARD(),
            default => PaymentMethod::BANK_CARD(),
        };
    }

    private static function mapTransactionType(ExternalTransactionType $transactionType): MyCoolPayTransactionType
    {
        return match ($transactionType) {
            ExternalTransactionType::RECHARGE() => MyCoolPayTransactionType::PAYIN(),
            ExternalTransactionType::WITHDRAWAL() => MyCoolPayTransactionType::PAYOUT(),
            default => MyCoolPayTransactionType::PAYIN(),
        };
    }

    private static function mapCurrency(ExternalTransaction $transaction): MyCoolPayCurrency
    {
        $paymentMethod = $transaction->payment_method;

        if ($paymentMethod === null) {
            return MyCoolPayCurrency::EUR();
        }

        return match ($paymentMethod) {
            PaymentMethod::MOBILE_MONEY(), PaymentMethod::ORANGE_MONEY() => MyCoolPayCurrency::XAF(),
            PaymentMethod::BANK_CARD() => MyCoolPayCurrency::EUR(),
            default => MyCoolPayCurrency::EUR(),
        };
    }

    private static function mapOperator(ExternalTransaction $transaction): MyCoolPayOperator
    {
        $paymentMethod = $transaction->payment_method;

        if ($paymentMethod === null) {
            return MyCoolPayOperator::CARD();
        }

        return match ($paymentMethod) {
            PaymentMethod::MOBILE_MONEY() => MyCoolPayOperator::CM_MOMO(),
            PaymentMethod::ORANGE_MONEY() => MyCoolPayOperator::CM_OM(),
            PaymentMethod::BANK_CARD() => MyCoolPayOperator::CARD(),
            default => MyCoolPayOperator::CARD(),
        };
    }
}
