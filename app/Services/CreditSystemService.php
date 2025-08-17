<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\InternalTransaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class CreditSystemService
{
    public function __construct()
    {
    }

    /**
     * Vérifier si l'utilisateur a suffisamment de crédit pour un message IA
     */
    public function hasEnoughCredit(User $user): bool
    {
        $wallet = $user->wallet;
        
        if (!$wallet) {
            Log::warning('[CREDIT_SYSTEM] Utilisateur sans wallet', [
                'user_id' => $user->id,
                'user_name' => $user->full_name,
            ]);
            return false;
        }

        $messageCost = $this->getMessageCost();
        $hasSufficientFunds = $wallet->balance >= $messageCost;

        Log::info('[CREDIT_SYSTEM] Vérification crédit', [
            'user_id' => $user->id,
            'user_name' => $user->full_name,
            'wallet_balance' => $wallet->balance,
            'message_cost' => $messageCost,
            'has_sufficient_funds' => $hasSufficientFunds,
        ]);

        return $hasSufficientFunds;
    }

    /**
     * Déduire le coût d'un message du wallet de l'utilisateur
     */
    public function deductMessageCost(User $user, string $messageContext = ''): bool
    {
        return DB::transaction(function () use ($user, $messageContext) {
            $wallet = $user->wallet;
            
            if (!$wallet) {
                Log::error('[CREDIT_SYSTEM] Impossible de déduire - wallet inexistant', [
                    'user_id' => $user->id,
                ]);
                return false;
            }

            $messageCost = $this->getMessageCost();

            if ($wallet->balance < $messageCost) {
                Log::warning('[CREDIT_SYSTEM] Solde insuffisant pour déduire', [
                    'user_id' => $user->id,
                    'wallet_balance' => $wallet->balance,
                    'message_cost' => $messageCost,
                ]);
                return false;
            }

            // Décrémenter le solde du wallet
            $wallet->decrement('balance', $messageCost);

            // Créer une transaction interne pour tracer l'opération
            $transaction = InternalTransaction::create([
                'wallet_id' => $wallet->id,
                'amount' => $messageCost,
                'transaction_type' => TransactionType::DEBIT(),
                'status' => TransactionStatus::COMPLETED(),
                'description' => 'Déduction coût message IA' . ($messageContext ? " - {$messageContext}" : ''),
                'related_type' => 'ai_message',
                'created_by' => $user->id,
                'completed_at' => now(),
            ]);

            Log::info('[CREDIT_SYSTEM] Déduction effectuée avec succès', [
                'user_id' => $user->id,
                'transaction_id' => $transaction->id,
                'amount_deducted' => $messageCost,
                'new_balance' => $wallet->fresh()->balance,
            ]);

            return true;
        });
    }

    /**
     * Obtenir le coût configuré par message
     */
    public function getMessageCost(): float
    {
        return (float) config('system_settings.ai_messaging.cost_per_message', 50);
    }

    /**
     * Mettre à jour le coût par message (pour les administrateurs)
     */
    public function updateMessageCost(float $newCost): bool
    {
        if ($newCost < 0) {
            Log::error('[CREDIT_SYSTEM] Tentative de définir un coût négatif', [
                'attempted_cost' => $newCost,
            ]);
            return false;
        }

        // Note: Cette méthode nécessiterait une implémentation pour persister
        // la configuration en base de données plutôt que dans le fichier config
        // Pour le moment, on log l'action
        Log::info('[CREDIT_SYSTEM] Demande de mise à jour coût message', [
            'old_cost' => $this->getMessageCost(),
            'new_cost' => $newCost,
        ]);

        return true;
    }

    /**
     * Obtenir le solde actuel d'un utilisateur
     */
    public function getUserBalance(User $user): float
    {
        return $user->wallet?->balance ?? 0.0;
    }

    /**
     * Vérifier si un utilisateur peut effectuer un certain nombre de messages
     */
    public function canAffordMessages(User $user, int $messageCount): bool
    {
        $totalCost = $this->getMessageCost() * $messageCount;
        $userBalance = $this->getUserBalance($user);

        return $userBalance >= $totalCost;
    }
}