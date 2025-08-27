<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\AiModel;
use App\Models\WhatsAppAccount;
use Illuminate\Console\Command;

final class FixWhatsAppAccountsAiModel extends Command
{
    protected $signature = 'whatsapp:fix-ai-models';

    protected $description = 'Fix existing WhatsApp accounts to use default AI model';

    public function handle(): int
    {
        $this->info('Starting to fix WhatsApp accounts AI model assignment...');

        $defaultModel = AiModel::getDefault();
        if (! $defaultModel) {
            $this->error('No default AI model found! Please run seeders first.');

            return Command::FAILURE;
        }

        $this->info("Using default model: {$defaultModel->name} (ID: {$defaultModel->id})");

        // Get accounts that need fixing
        $accountsToFix = WhatsAppAccount::where(function ($query) {
            $query->whereNull('ai_model_id')
                ->orWhere('agent_enabled', true);
        })->get();

        $this->info("Found {$accountsToFix->count()} accounts to fix");

        $fixed = 0;
        foreach ($accountsToFix as $account) {
            $before = [
                'agent_enabled' => $account->agent_enabled,
                'ai_model_id' => $account->ai_model_id,
                'hasAiAgent' => $account->hasAiAgent(),
            ];

            $account->update([
                'ai_model_id' => $defaultModel->id,
                'agent_enabled' => true,
            ]);

            $account->refresh();
            $after = [
                'agent_enabled' => $account->agent_enabled,
                'ai_model_id' => $account->ai_model_id,
                'hasAiAgent' => $account->hasAiAgent(),
            ];

            $this->line("Account {$account->id} ({$account->session_name}): ".
                      "enabled={$before['agent_enabled']}->{$after['agent_enabled']}, ".
                      "model_id={$before['ai_model_id']}->{$after['ai_model_id']}, ".
                      "hasAiAgent={$before['hasAiAgent']}->{$after['hasAiAgent']}");

            $fixed++;
        }

        $this->info("✅ Fixed {$fixed} WhatsApp accounts");

        return Command::SUCCESS;
    }
}
