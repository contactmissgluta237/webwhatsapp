<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\AiModel;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

final class CleanAiModels extends Command
{
    protected $signature = 'ai:clean-and-reseed';

    protected $description = 'Clean existing AI models and reseed only DeepSeek';

    public function handle(): int
    {
        $this->info('Starting AI models cleanup and reseed...');

        // Show current AI models
        $currentModels = AiModel::all();
        $this->info("Current AI models: {$currentModels->count()}");
        foreach ($currentModels as $model) {
            $this->line("- {$model->name} ({$model->provider}) - ".($model->is_default ? 'DEFAULT' : 'regular'));
        }

        if ($currentModels->count() > 0) {
            if (! $this->option('no-interaction') && ! $this->confirm('Delete all existing AI models?')) {
                $this->info('Operation cancelled.');

                return Command::SUCCESS;
            }

            // First, remove AI model references from WhatsApp accounts
            $this->info('Removing AI model references from WhatsApp accounts...');
            DB::table('whatsapp_accounts')->update(['ai_model_id' => null]);

            // Delete all AI models (using delete instead of truncate due to foreign key constraints)
            $this->info('Deleting existing AI models...');
            $deletedCount = AiModel::count();
            AiModel::query()->delete();
            $this->info("✅ Deleted {$deletedCount} AI models");
        }

        // Reseed AI models (now only DeepSeek)
        $this->info('Reseeding AI models...');
        $this->call('db:seed', ['--class' => 'AiModelsSeeder', '--force' => true]);

        // Show new models
        $newModels = AiModel::all();
        $this->info("New AI models: {$newModels->count()}");
        foreach ($newModels as $model) {
            $this->line("- {$model->name} ({$model->provider}) - ".($model->is_default ? 'DEFAULT' : 'regular'));
        }

        $defaultModel = AiModel::getDefault();
        if ($defaultModel) {
            $this->info("✅ Default model set to: {$defaultModel->name} (ID: {$defaultModel->id})");
        } else {
            $this->error('❌ No default model found!');
        }

        return Command::SUCCESS;
    }
}
