<?php

declare(strict_types=1);

namespace App\Services\WhatsApp;

use App\DTOs\AI\AiRequestDTO;
use App\Models\UserProduct;
use App\Models\WhatsAppAccount;
use App\Services\AI\Helpers\AgentPromptHelper;
use App\Services\WhatsApp\Contracts\MessageBuildServiceInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class MessageBuildService implements MessageBuildServiceInterface
{
    private const MAX_PRODUCTS_PER_REQUEST = 10;
    private const CURRENCY_SUFFIX = ' XAF';

    /**
     * Build a complete AI request with system prompt, user message and context
     */
    public function buildAiRequest(
        WhatsAppAccount $account,
        string $conversationHistory,
        string $userMessage
    ): AiRequestDTO {
        Log::debug('[MESSAGE_BUILD] Building AI request', [
            'account_id' => $account->id,
            'message_length' => strlen($userMessage),
            'history_length' => strlen($conversationHistory),
        ]);

        $systemPrompt = $this->buildSystemPrompt($account, $conversationHistory);

        return new AiRequestDTO(
            systemPrompt: $systemPrompt,
            userMessage: $userMessage,
            account: $account,
        );
    }

    /**
     * Build system prompt with contextual information
     */
    private function buildSystemPrompt(WhatsAppAccount $account, string $conversationHistory): string
    {
        $components = [
            $this->getBasePrompt($account),
            $this->getAntiHallucinationRules(),
            $this->getConversationHistory($conversationHistory),
            $this->getProductsContext($account),
            $this->getJsonResponseInstructions(),
        ];

        return implode('', array_filter($components));
    }

    private function getBasePrompt(WhatsAppAccount $account): string
    {
        return $account->agent_prompt ?? 'Tu es un assistant commercial professionnel.';
    }

    private function getConversationGuidelines(): string
    {
        return "\n\nDirectives de conversation :"
            ."\n- Réponds en français de manière naturelle et conversationnelle"
            ."\n- Reste concis et pertinent"
            ."\n- Utilise un ton professionnel mais chaleureux";
    }

    private function getAntiHallucinationRules(): string
    {
        return AgentPromptHelper::getAntiHallucinationRules();
    }

    private function getConversationHistory(string $conversationHistory): string
    {
        return empty($conversationHistory)
            ? ''
            : "\n\nContexte de la conversation précédente :\n".$conversationHistory;
    }

    private function getProductsContext(WhatsAppAccount $account): string
    {
        $products = $this->getActiveProducts($account);

        if ($products->isEmpty()) {
            return '';
        }

        return $this->buildProductsContextString($products, $account->id);
    }

    /**
     * @return Collection<UserProduct>
     */
    private function getActiveProducts(WhatsAppAccount $account): Collection
    {
        /** @var Collection<UserProduct> */
        return $account->userProducts()
            ->where('is_active', true)
            ->with('media')
            ->take(self::MAX_PRODUCTS_PER_REQUEST)
            ->get();
    }

    private function buildProductsContextString(Collection $products, int $accountId): string
    {
        $productsContext = "\n\n📦 PRODUITS DISPONIBLES À PROPOSER AU CLIENT :\n";

        foreach ($products as $product) {
            $productsContext .= $this->formatProductLine($product);
        }

        $productsContext .= $this->getProductInstructions();

        Log::debug('[MESSAGE_BUILD] Products context added', [
            'account_id' => $accountId,
            'products_count' => $products->count(),
            'context_length' => strlen($productsContext),
        ]);

        return $productsContext;
    }

    private function formatProductLine(UserProduct $product): string
    {
        $price = is_numeric($product->price) ? (float) $product->price : 0.0;

        return sprintf(
            "• ID: %d | %s | %s | %s\n",
            $product->id,
            $product->title,
            number_format($price, 0, ',', ' ').self::CURRENCY_SUFFIX,
            Str::limit($product->description, 80)
        );
    }

    private function getProductInstructions(): string
    {
        return AgentPromptHelper::getProductInstructions();
    }

    private function getJsonResponseInstructions(): string
    {
        return AgentPromptHelper::getJsonResponseInstructions();
    }
}
