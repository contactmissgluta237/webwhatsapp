<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\PushSubscription;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class PushNotificationDiagnosticController extends Controller
{
    public function __invoke(Request $request): JsonResponse|View
    {
        $diagnostics = $this->runDiagnostics();

        if ($request->expectsJson()) {
            return response()->json($diagnostics);
        }

        return view('push-diagnostic', compact('diagnostics'));
    }

    private function runDiagnostics(): array
    {
        $diagnostics = [
            'config' => $this->checkConfig(),
            'environment' => $this->checkEnvironment(),
            'database' => $this->checkDatabase(),
            'permissions' => $this->checkPermissions(),
        ];

        $diagnostics['overall_status'] = $this->calculateOverallStatus($diagnostics);

        return $diagnostics;
    }

    private function checkConfig(): array
    {
        return [
            'vapid_public_key' => [
                'status' => config('webpush.vapid.public_key') ? 'success' : 'error',
                'value' => config('webpush.vapid.public_key') ? 'Configurée' : 'Manquante',
                'message' => config('webpush.vapid.public_key') ? 'Clé VAPID publique trouvée' : 'Variable VAPID_PUBLIC_KEY manquante dans .env',
            ],
            'vapid_private_key' => [
                'status' => config('webpush.vapid.private_key') ? 'success' : 'error',
                'value' => config('webpush.vapid.private_key') ? 'Configurée' : 'Manquante',
                'message' => config('webpush.vapid.private_key') ? 'Clé VAPID privée trouvée' : 'Variable VAPID_PRIVATE_KEY manquante dans .env',
            ],
            'app_url' => [
                'status' => config('app.url') ? 'success' : 'warning',
                'value' => config('app.url'),
                'message' => config('app.url') ? 'URL configurée' : 'URL d\'application non configurée',
            ],
        ];
    }

    private function checkEnvironment(): array
    {
        $isHttps = request()->secure() || request()->header('x-forwarded-proto') === 'https';

        return [
            'https' => [
                'status' => $isHttps ? 'success' : 'warning',
                'value' => $isHttps ? 'Activé' : 'Désactivé',
                'message' => $isHttps ? 'HTTPS activé (requis pour mobile)' : 'HTTPS désactivé (peut causer des problèmes sur mobile)',
            ],
            'user_agent' => [
                'status' => 'info',
                'value' => request()->userAgent() ?: 'Inconnu',
                'message' => 'Navigateur détecté',
            ],
        ];
    }

    private function checkDatabase(): array
    {
        /** @var User|null $user */
        $user = auth()->user();

        if (! $user) {
            return [
                'subscriptions_total' => [
                    'status' => 'warning',
                    'value' => 0,
                    'message' => 'Aucun utilisateur connecté',
                ],
                'subscriptions_active' => [
                    'status' => 'warning',
                    'value' => 0,
                    'message' => 'Aucun utilisateur connecté',
                ],
            ];
        }

        // Use direct model query instead of relationship with active() scope
        /** @var Collection<int, PushSubscription> $allSubscriptions */
        $allSubscriptions = PushSubscription::where('subscribable_type', User::class)
            ->where('subscribable_id', $user->id)
            ->get();

        /** @var Collection<int, PushSubscription> $activeSubscriptions */
        $activeSubscriptions = PushSubscription::where('subscribable_type', User::class)
            ->where('subscribable_id', $user->id)
            ->active()
            ->get();

        $subscriptionsCount = $allSubscriptions->count();
        $activeSubscriptionsCount = $activeSubscriptions->count();

        return [
            'subscriptions_total' => [
                'status' => $subscriptionsCount > 0 ? 'success' : 'warning',
                'value' => $subscriptionsCount,
                'message' => $subscriptionsCount > 0 ? 'Subscriptions trouvées' : 'Aucune subscription trouvée',
            ],
            'subscriptions_active' => [
                'status' => $activeSubscriptionsCount > 0 ? 'success' : 'warning',
                'value' => $activeSubscriptionsCount,
                'message' => $activeSubscriptionsCount > 0 ? 'Subscriptions actives trouvées' : 'Aucune subscription active',
            ],
        ];
    }

    private function checkPermissions(): array
    {
        return [
            'notification_permission' => [
                'status' => 'info',
                'value' => 'À vérifier côté client',
                'message' => 'Permission vérifiée via JavaScript',
            ],
            'service_worker' => [
                'status' => 'info',
                'value' => 'À vérifier côté client',
                'message' => 'Service Worker vérifié via JavaScript',
            ],
        ];
    }

    private function calculateOverallStatus(array $diagnostics): string
    {
        $hasErrors = false;
        $hasWarnings = false;

        foreach ($diagnostics as $category) {
            if (is_array($category)) {
                foreach ($category as $check) {
                    if (isset($check['status'])) {
                        if ($check['status'] === 'error') {
                            $hasErrors = true;
                        } elseif ($check['status'] === 'warning') {
                            $hasWarnings = true;
                        }
                    }
                }
            }
        }

        if ($hasErrors) {
            return 'error';
        }
        if ($hasWarnings) {
            return 'warning';
        }

        return 'success';
    }
}
