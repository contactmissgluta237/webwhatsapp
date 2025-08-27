<?php

declare(strict_types=1);

namespace App\Http\Controllers\Customer\WhatsApp\Account;

use App\Http\Controllers\Controller;
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
        // Make sure the account belongs to the authenticated user
        if ($account->user_id !== $request->user()->id) {
            return redirect()->route('whatsapp.index')
                ->with('error', 'Unauthorized access to this WhatsApp account.');
        }

        $enable = $request->boolean('enable');

        try {
            if ($enable) {
                // Enable AI agent with default model if none selected
                if (! $account->ai_model_id) {
                    $defaultModel = \App\Models\AiModel::getDefault();
                    if (! $defaultModel) {
                        return redirect()->route('whatsapp.index')
                            ->with('error', 'No AI model available. Please configure the AI agent first.');
                    }
                    $account->ai_model_id = $defaultModel->id;
                }
                $account->agent_enabled = true;
                $account->save();

                $message = 'AI agent enabled successfully!';
            } else {
                $account->disableAiAgent();
                $message = 'AI agent disabled successfully!';
            }

            return redirect()->route('whatsapp.index')
                ->with('success', $message);

        } catch (\Exception $e) {
            return redirect()->route('whatsapp.index')
                ->with('error', 'Error during modification: '.$e->getMessage());
        }
    }
}
