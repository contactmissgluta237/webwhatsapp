<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\WhatsAppAccount;

interface PromptEnhancementInterface
{
    public function enhancePrompt(WhatsAppAccount $account, string $originalPrompt): string;
}
