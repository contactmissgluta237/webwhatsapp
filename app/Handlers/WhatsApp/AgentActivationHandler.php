<?php

declare(strict_types=1);

namespace App\Handlers\WhatsApp;

use App\DTOs\WhatsApp\AgentActivationResultDTO;
use App\Models\User;

final class AgentActivationHandler
{
    public function handle(User $user): AgentActivationResultDTO
    {
        $activeAgents = $this->getActiveAgentsCount($user);
        $subscription = $this->getActiveSubscription($user);
        $walletBalance = $this->getWalletBalance($user);

        if ($subscription) {
            return $this->handleSubscriptionCase($subscription, $activeAgents, $walletBalance);
        }

        return $this->handleNoSubscriptionCase($activeAgents, $walletBalance);
    }

    private function getActiveAgentsCount(User $user): int
    {
        return $user->whatsappAccounts()->where('agent_enabled', true)->count();
    }

    private function getActiveSubscription(User $user): ?object
    {
        return $user->activeSubscription()->with('package')->first();
    }

    private function getWalletBalance(User $user): float
    {
        return (float) ($user->wallet?->balance ?? 0.0);
    }

    private function handleSubscriptionCase(object $subscription, int $activeAgents, float $walletBalance): AgentActivationResultDTO
    {
        $limit = $subscription->accounts_limit;

        if ($activeAgents >= $limit) {
            return AgentActivationResultDTO::deny(
                __('Your package limit reached: :current/:max active agents', [
                    'current' => $activeAgents,
                    'max' => $limit,
                ]),
                $activeAgents,
                $limit,
                true,
                $walletBalance
            );
        }

        return AgentActivationResultDTO::allow($activeAgents, $limit, true, $walletBalance);
    }

    private function handleNoSubscriptionCase(int $activeAgents, float $walletBalance): AgentActivationResultDTO
    {
        $minimumCost = config('whatsapp.billing.costs.ai_message', 15);
        $currency = config('app.currency', 'XAF');

        if ($walletBalance < $minimumCost) {
            return AgentActivationResultDTO::deny(
                __('Insufficient balance. Minimum required: :amount :currency to activate an agent', [
                    'amount' => $minimumCost,
                    'currency' => $currency,
                ]),
                $activeAgents,
                0,
                false,
                $walletBalance
            );
        }

        if ($activeAgents >= 1) {
            return AgentActivationResultDTO::deny(
                __('Without active subscription, you can only activate one agent at a time'),
                $activeAgents,
                1,
                false,
                $walletBalance
            );
        }

        return AgentActivationResultDTO::allow($activeAgents, 1, false, $walletBalance);
    }
}
