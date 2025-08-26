<?php

declare(strict_types=1);

namespace App\Http\Controllers\Customer\Packages;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Http\Controllers\Controller;
use App\Models\InternalTransaction;
use App\Models\Package;
use App\Models\UserSubscription;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SubscribeController extends Controller
{
    public function __invoke(Request $request, Package $package): RedirectResponse
    {
        $user = $request->user();

        // Check if user already has an active subscription
        if ($user->hasActiveSubscription()) {
            return redirect()
                ->route('customer.packages.index')
                ->with('error', 'Vous avez déjà un abonnement actif. Veuillez attendre son expiration ou le résilier avant de souscrire à un nouveau package.');
        }

        // Check for trial package
        if ($package->isTrial()) {
            $hasUsedTrial = $user->subscriptions()
                ->whereHas('package', fn ($q) => $q->where('name', 'trial'))
                ->exists();

            if ($hasUsedTrial) {
                return redirect()
                    ->route('customer.packages.index')
                    ->with('error', 'Vous avez déjà utilisé votre essai gratuit.');
            }
        }

        // Check if package is active
        if (! $package->is_active) {
            return redirect()
                ->route('customer.packages.index')
                ->with('error', 'Ce package n\'est plus disponible.');
        }

        // For trial package, no wallet verification needed
        if ($package->isTrial()) {
            return $this->createSubscription($user, $package);
        }

        // Check wallet balance
        $wallet = $user->wallet;
        if (! $wallet || $wallet->balance < $package->price) {
            $missingAmount = $package->price - ($wallet ? $wallet->balance : 0);

            return redirect()
                ->route('customer.packages.index')
                ->with('error', "Solde insuffisant. Il vous manque {$missingAmount} XAF.")
                ->with('recharge_needed', true)
                ->with('missing_amount', $missingAmount);
        }

        return $this->createSubscription($user, $package);
    }

    private function createSubscription($user, Package $package): RedirectResponse
    {
        try {
            DB::transaction(function () use ($user, $package) {
                // Debit wallet only if not trial
                if (! $package->isTrial()) {
                    // Create debit transaction
                    InternalTransaction::create([
                        'wallet_id' => $user->wallet->id,
                        'amount' => $package->price,
                        'transaction_type' => TransactionType::DEBIT(),
                        'status' => TransactionStatus::COMPLETED(),
                        'description' => "Souscription au package {$package->display_name}",
                        'related_type' => Package::class,
                        'related_id' => $package->id,
                        'created_by' => $user->id,
                        'completed_at' => now(),
                    ]);

                    // Debit wallet balance
                    $user->wallet->decrement('balance', $package->price);
                }

                // Create subscription
                UserSubscription::create([
                    'user_id' => $user->id,
                    'package_id' => $package->id,
                    'starts_at' => now(),
                    'ends_at' => now()->addDays($package->duration_days ?? 30),
                    'status' => 'active',
                    'messages_limit' => $package->messages_limit,
                    'context_limit' => $package->context_limit,
                    'accounts_limit' => $package->accounts_limit,
                    'products_limit' => $package->products_limit,
                    'amount_paid' => $package->price,
                    'payment_method' => 'wallet',
                    'activated_at' => now(),
                ]);
            });

            $message = $package->isTrial()
                ? 'Votre essai gratuit de 7 jours a été activé avec succès !'
                : "Votre abonnement au package {$package->display_name} a été activé avec succès !";

            return redirect()
                ->route('customer.packages.index')
                ->with('success', $message);

        } catch (\Exception $e) {
            return redirect()
                ->route('customer.packages.index')
                ->with('error', 'Une erreur est survenue lors de la souscription. Veuillez réessayer.');
        }
    }
}
