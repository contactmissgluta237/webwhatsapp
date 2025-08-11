<?php

declare(strict_types=1);

namespace App\Services\WhatsApp\AI;

use App\Enums\ResponseTime;
use App\Models\AiModel;
use Carbon\Carbon;

final class AiResponseSimulator
{
    public function simulate(AiModel $model, string $prompt, string $userMessage, ResponseTime $responseTime): string
    {
        // Simulate realistic AI response based on model and prompt
        $response = $this->generateSimulatedResponse($model, $prompt, $userMessage);

        // Add simulation metadata
        $metadata = $this->generateMetadata($model, $responseTime);

        return $response . "\n\n" . $metadata;
    }

    private function generateSimulatedResponse(AiModel $model, string $prompt, string $userMessage): string
    {
        // Simple response simulation based on common patterns
        $responses = [
            'greeting' => $this->getGreetingResponse($prompt),
            'question' => $this->getQuestionResponse($prompt, $userMessage),
            'help' => $this->getHelpResponse($prompt),
            'farewell' => $this->getFarewellResponse($prompt),
            'default' => $this->getDefaultResponse($prompt, $userMessage)
        ];

        $messageType = $this->detectMessageType($userMessage);

        return $responses[$messageType] ?? $responses['default'];
    }

    private function detectMessageType(string $message): string
    {
        $message = strtolower($message);

        if (preg_match('/\b(salut|bonjour|hello|bonsoir)\b/', $message)) {
            return 'greeting';
        }

        if (preg_match('/\b(aide|help|support|problème)\b/', $message)) {
            return 'help';
        }

        if (preg_match('/\b(au revoir|merci|bye|à bientôt)\b/', $message)) {
            return 'farewell';
        }

        if (preg_match('/\?/', $message)) {
            return 'question';
        }

        return 'default';
    }

    private function getGreetingResponse(string $prompt): string
    {
        $responses = [
            "Bonjour ! Comment puis-je vous aider aujourd'hui ?",
            "Salut ! Que puis-je faire pour vous ?",
            "Bonjour ! Je suis là pour vous assister.",
        ];

        return $responses[array_rand($responses)];
    }

    private function getQuestionResponse(string $prompt, string $message): string
    {
        return "C'est une excellente question ! Basé sur votre message « {$message} », voici ma réponse simulée selon le contexte : {$prompt}";
    }

    private function getHelpResponse(string $prompt): string
    {
        return "Je suis là pour vous aider ! Voici comment je peux vous assister selon ma configuration : " . substr($prompt, 0, 100) . "...";
    }

    private function getFarewellResponse(string $prompt): string
    {
        $responses = [
            "Merci de m'avoir contacté ! N'hésitez pas à revenir si vous avez d'autres questions.",
            "Au revoir ! J'espère avoir pu vous aider.",
            "À bientôt ! Je reste disponible si besoin.",
        ];

        return $responses[array_rand($responses)];
    }

    private function getDefaultResponse(string $prompt, string $message): string
    {
        return "J'ai bien reçu votre message « {$message} ». Selon ma configuration, voici ma réponse simulée basée sur : " . substr($prompt, 0, 80) . "...";
    }

    private function generateMetadata(AiModel $model, ResponseTime $responseTime): string
    {
        $delay = $responseTime->getRandomDelay();
        $estimatedCost = $model->getEstimatedCostFor(150);

        return "📊 **Simulation Info:**\n" .
               "• Modèle: {$model->name}\n" .
               "• Délai: {$delay}s ({$responseTime->label})\n" .
               "• Coût estimé: " . number_format($estimatedCost, 6) . " USD\n" .
               "• Simulé à: " . Carbon::now()->format('H:i:s');
    }
}
