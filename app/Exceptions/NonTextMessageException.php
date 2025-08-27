<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

final class NonTextMessageException extends Exception
{
    public function __construct(
        public readonly string $messageType,
        string $message = 'Only text messages are supported for now',
        int $code = 422
    ) {
        parent::__construct($message, $code);
    }

    public function getMessageType(): string
    {
        return $this->messageType;
    }

    public function getDefaultResponse(): string
    {
        return "Désolé, je ne peux comprendre que les messages texte pour le moment. Veuillez m'envoyer votre message sous forme de texte et je serai ravi de vous aider ! 😊";
    }
}
