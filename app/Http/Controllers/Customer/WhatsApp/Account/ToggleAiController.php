<?php

declare(strict_types=1);

namespace App\Http\Controllers\Customer\WhatsApp\Account;

use App\Handlers\WhatsApp\AgentActivationHandler;
use App\Http\Controllers\Controller;
use App\Models\AiModel;
use App\Models\WhatsAppAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class ToggleAiController extends Controller
{
    /**
     * Toggle AI agent status for a WhatsApp account.
     *
     * Route: POST /whatsapp/{account}/toggle-ai
     * Name: whatsapp.toggle-ai
     */
    public function __invoke(Request $request, WhatsAppAccount $account): RedirectResponse
    {
        $enable = $request->boolean('enable');

        if ($enable) {
            return $this->enableAgent($request, $account);
        }

        return $this->disableAgent($account);
    }

    private function enableAgent(Request $request, WhatsAppAccount $account): RedirectResponse
    {
        $handler = app(AgentActivationHandler::class);
        $result = $handler->handle($request->user());

        if (! $result->canActivate) {
            return $this->redirectWithError($result->reason);
        }

        if (! $account->ai_model_id) {
            $defaultModel = AiModel::getDefault();
            if (! $defaultModel) {
                return $this->redirectWithError('No AI model available');
            }
            $account->ai_model_id = $defaultModel->id;
        }

        $account->update(['agent_enabled' => true]);

        return $this->redirectWithSuccess('Agent enabled successfully');
    }

    private function disableAgent(WhatsAppAccount $account): RedirectResponse
    {
        $account->disableAiAgent();

        return $this->redirectWithSuccess('Agent disabled successfully');
    }

    private function redirectWithError(string $message): RedirectResponse
    {
        return redirect()->route('whatsapp.index')->with('error', $message);
    }

    private function redirectWithSuccess(string $message): RedirectResponse
    {
        return redirect()->route('whatsapp.index')->with('success', $message);
    }
}
