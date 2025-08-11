<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Auth\Contracts\AccountActivationServiceInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class ActivateAccountViewController extends Controller
{
    public function __invoke(Request $request): RedirectResponse|View
    {
        $identifier = $request->get('identifier');
        $code = $request->get('code');

        if (! $identifier) {
            return redirect()->route('register')
                ->with('error', 'Identifiant manquant pour l\'activation du compte.');
        }

        if ($code) {
            return $this->activateAccountDirectly($identifier, $code);
        }

        return view('auth.activate-account', [
            'identifier' => $identifier,
        ]);
    }

    private function activateAccountDirectly(string $identifier, string $code): RedirectResponse
    {
        try {
            $activationService = app(AccountActivationServiceInterface::class);
            $user = User::where('email', $identifier)->first();

            if (! $user) {
                return redirect()->route('account.activate', ['identifier' => $identifier])
                    ->with('error', 'Aucun compte n\'est associé à cet identifiant.');
            }

            $isValid = $activationService->verifyActivationCode($user->email, $code);

            if ($isValid) {
                if (! $user->hasVerifiedEmail()) {
                    $user->markEmailAsVerified();

                    return redirect()->route('login')
                        ->with('success', 'Félicitations ! Votre compte a été activé avec succès. Vous pouvez maintenant vous connecter.');
                }

                return redirect()->route('login')
                    ->with('info', 'Votre compte est déjà activé. Vous pouvez vous connecter.');
            }

            return redirect()->route('account.activate', ['identifier' => $identifier])
                ->with('error', 'Le lien d\'activation a expiré ou est invalide. Veuillez saisir le code manuellement.');

        } catch (\Exception $e) {
            return redirect()->route('account.activate', ['identifier' => $identifier])
                ->with('error', 'Une erreur est survenue lors de l\'activation. Veuillez réessayer.');
        }
    }
}
