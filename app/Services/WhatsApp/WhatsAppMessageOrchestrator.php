<?php

declare(strict_types=1);

namespace App\Services\WhatsApp;

use App\Contracts\WhatsApp\AIProviderServiceInterface;
use App\Contracts\WhatsApp\ContextPreparationServiceInterface;
use App\Contracts\WhatsApp\MessageBuildServiceInterface;
use App\Contracts\WhatsApp\ResponseFormatterServiceInterface;
use App\Contracts\WhatsApp\WhatsAppMessageOrchestratorInterface;
use App\DTOs\WhatsApp\WhatsAppAccountMetadataDTO;
use App\DTOs\WhatsApp\WhatsAppMessageRequestDTO;
use App\DTOs\WhatsApp\WhatsAppMessageResponseDTO;
use App\Models\WhatsAppAccount;
use App\Services\CreditSystemService;
use Exception;
use Illuminate\Support\Facades\Log;

final class WhatsAppMessageOrchestrator implements WhatsAppMessageOrchestratorInterface
{
    public function __construct(
        private readonly ContextPreparationServiceInterface $contextService,
        private readonly MessageBuildServiceInterface $messageBuildService,
        private readonly AIProviderServiceInterface $aiProviderService,
        private readonly ResponseFormatterServiceInterface $responseFormatterService,
        private readonly CreditSystemService $creditSystemService
    ) {}

    /**
     * Orchestrate the complete processing of an incoming WhatsApp message
     */
    public function processIncomingMessage(
        WhatsAppAccountMetadataDTO $accountMetadata,
        WhatsAppMessageRequestDTO $messageRequest
    ): WhatsAppMessageResponseDTO {
        Log::info('[ORCHESTRATOR] Processing incoming WhatsApp message', [
            'session_id' => $accountMetadata->sessionId,
            'from' => $messageRequest->from,
            'message_id' => $messageRequest->id,
            'agent_enabled' => $accountMetadata->agentEnabled,
        ]);

        try {
            // Step 1: Validate agent is enabled
            if (! $accountMetadata->isAgentActive()) {
                Log::info('[ORCHESTRATOR] Agent disabled, skipping AI processing', [
                    'session_id' => $accountMetadata->sessionId,
                ]);

                return WhatsAppMessageResponseDTO::processedWithoutResponse();
            }

            // Step 2: Check credit system - verify user has enough credits
            $whatsappAccount = WhatsAppAccount::find($accountMetadata->accountId);
            if (! $whatsappAccount) {
                Log::error('[ORCHESTRATOR] WhatsApp account not found', [
                    'account_id' => $accountMetadata->accountId,
                ]);

                return WhatsAppMessageResponseDTO::processedWithoutResponse();
            }

            $accountOwner = $whatsappAccount->user;
            if (! $this->creditSystemService->hasEnoughCredit($accountOwner)) {
                Log::warning('[ORCHESTRATOR] Insufficient credits, blocking AI response', [
                    'session_id' => $accountMetadata->sessionId,
                    'user_id' => $accountOwner->id,
                    'user_balance' => $this->creditSystemService->getUserBalance($accountOwner),
                    'message_cost' => $this->creditSystemService->getMessageCost(),
                ]);

                return WhatsAppMessageResponseDTO::processedWithoutResponse();
            }

            // Step 3: Prepare conversation context
            $conversation = $this->contextService->findOrCreateConversation(
                $accountMetadata,
                $messageRequest
            );

            // Step 4: Store incoming message
            $this->contextService->storeIncomingMessage($conversation, $messageRequest);

            // Step 5: Build conversation context
            $conversationContext = $this->contextService->buildConversationContext(
                $conversation,
                $accountMetadata->contextualInformation
            );

            // Step 6: Build AI request
            $aiRequest = $this->messageBuildService->buildAiRequest(
                $accountMetadata,
                $conversationContext,
                $messageRequest->body
            );

            // Step 7: Generate AI response
            $aiResponse = $this->aiProviderService->generateResponse(
                $accountMetadata,
                $aiRequest
            );

            if (! $aiResponse || ! $aiResponse->hasValidResponse()) {
                Log::warning('[ORCHESTRATOR] No valid AI response generated', [
                    'session_id' => $accountMetadata->sessionId,
                ]);

                return WhatsAppMessageResponseDTO::processedWithoutResponse();
            }

            // Step 8: Deduct credit cost for successful AI response
            $messageContext = "Session: {$accountMetadata->sessionId}";
            if (! $this->creditSystemService->deductMessageCost($accountOwner, $messageContext)) {
                Log::error('[ORCHESTRATOR] Failed to deduct message cost after successful AI response', [
                    'session_id' => $accountMetadata->sessionId,
                    'user_id' => $accountOwner->id,
                ]);
                // Note: We continue with the response even if deduction fails to avoid inconsistent state
            }

            // Step 9: Format and store response
            $finalResponse = $this->responseFormatterService->formatAndStoreResponse(
                $conversation,
                $aiResponse,
                $accountMetadata
            );

            Log::info('[ORCHESTRATOR] Message processing completed successfully', [
                'session_id' => $accountMetadata->sessionId,
                'ai_response_length' => $aiResponse->getResponseLength(),
                'model_used' => $aiResponse->model,
            ]);

            return $finalResponse;

        } catch (Exception $e) {
            Log::error('[ORCHESTRATOR] Error processing message', [
                'session_id' => $accountMetadata->sessionId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return WhatsAppMessageResponseDTO::error("Erreur traitement message: {$e->getMessage()}");
        }
    }

    /**
     * Process a simulated message (from ConversationSimulator)
     */
    public function processSimulatedMessage(
        WhatsAppAccountMetadataDTO $accountMetadata,
        string $userMessage,
        ?array $existingContext = null
    ): WhatsAppMessageResponseDTO {
        Log::info('[ORCHESTRATOR] Processing simulated message', [
            'session_id' => $accountMetadata->sessionId,
            'message_length' => strlen($userMessage),
        ]);

        try {
            // Step 1: Check credit system for simulation
            $whatsappAccount = WhatsAppAccount::find($accountMetadata->accountId);
            if (! $whatsappAccount) {
                Log::error('[ORCHESTRATOR] WhatsApp account not found for simulation', [
                    'account_id' => $accountMetadata->accountId,
                ]);

                return WhatsAppMessageResponseDTO::processedWithoutResponse();
            }

            $accountOwner = $whatsappAccount->user;
            if (! $this->creditSystemService->hasEnoughCredit($accountOwner)) {
                Log::warning('[ORCHESTRATOR] Insufficient credits for simulation, blocking AI response', [
                    'session_id' => $accountMetadata->sessionId,
                    'user_id' => $accountOwner->id,
                    'user_balance' => $this->creditSystemService->getUserBalance($accountOwner),
                    'message_cost' => $this->creditSystemService->getMessageCost(),
                ]);

                return WhatsAppMessageResponseDTO::processedWithoutResponse();
            }

            // Step 2: For simulation, we create a mock conversation context
            $conversationContext = $this->buildSimulatedContext(
                $accountMetadata,
                $existingContext ?? []
            );

            // Step 3: Build AI request
            $aiRequest = $this->messageBuildService->buildAiRequest(
                $accountMetadata,
                $conversationContext,
                $userMessage
            );

            // Step 4: Generate AI response
            $aiResponse = $this->aiProviderService->generateResponse(
                $accountMetadata,
                $aiRequest
            );

            if (! $aiResponse || ! $aiResponse->hasValidResponse()) {
                Log::warning('[ORCHESTRATOR] No valid AI response for simulation', [
                    'session_id' => $accountMetadata->sessionId,
                ]);

                return WhatsAppMessageResponseDTO::processedWithoutResponse();
            }

            // Step 5: Deduct credit cost for successful simulation response
            $messageContext = "Simulation - Session: {$accountMetadata->sessionId}";
            if (! $this->creditSystemService->deductMessageCost($accountOwner, $messageContext)) {
                Log::error('[ORCHESTRATOR] Failed to deduct message cost for simulation after successful AI response', [
                    'session_id' => $accountMetadata->sessionId,
                    'user_id' => $accountOwner->id,
                ]);
                // Continue with the response even if deduction fails
            }

            // Step 6: For simulation, we return success without storing to database
            $response = WhatsAppMessageResponseDTO::success(
                $aiResponse->response,
                $aiResponse
            );

            Log::info('[ORCHESTRATOR] Simulation completed successfully', [
                'session_id' => $accountMetadata->sessionId,
                'ai_response_length' => $aiResponse->getResponseLength(),
            ]);

            return $response;

        } catch (Exception $e) {
            Log::error('[ORCHESTRATOR] Error processing simulation', [
                'session_id' => $accountMetadata->sessionId,
                'error' => $e->getMessage(),
            ]);

            return WhatsAppMessageResponseDTO::error("Erreur simulation: {$e->getMessage()}");
        }
    }

    /**
     * Create account metadata from session information
     */
    public function createAccountMetadata(string $sessionId, string $sessionName): WhatsAppAccountMetadataDTO
    {
        $account = WhatsAppAccount::where('session_id', $sessionId)->first();

        if (! $account) {
            Log::warning('WhatsApp account not found for incoming message', [
                'session_id' => $sessionId,
                'session_name' => $sessionName,
            ]);

            // Retourner un DTO avec agent désactivé pour gérer gracieusement
            return WhatsAppAccountMetadataDTO::createDisabled($sessionId, $sessionName);
        }

        return WhatsAppAccountMetadataDTO::fromAccount($account, $sessionId, $sessionName);
    }

    /**
     * Build simulated conversation context for testing
     */
    private function buildSimulatedContext(
        WhatsAppAccountMetadataDTO $accountMetadata,
        array $existingContext
    ): \App\DTOs\WhatsApp\ConversationContextDTO {
        return new \App\DTOs\WhatsApp\ConversationContextDTO(
            conversationId: 0, // Simulation doesn't need real ID
            chatId: 'simulation@c.us',
            contactPhone: 'simulation',
            isGroup: false,
            recentMessages: $existingContext,
            contextualInformation: $accountMetadata->contextualInformation,
            metadata: ['simulation' => true]
        );
    }

    /**
     * Validate prerequisites for message processing
     */
    private function validatePrerequisites(WhatsAppAccountMetadataDTO $accountMetadata): bool
    {
        return $accountMetadata->isAgentActive() &&
               $this->aiProviderService->canGenerateResponse($accountMetadata);
    }
}
