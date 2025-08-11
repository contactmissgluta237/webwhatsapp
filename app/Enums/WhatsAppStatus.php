<?php

declare(strict_types=1);

namespace App\Enums;

use Spatie\Enum\Enum;

/**
 * @method static self DISCONNECTED()
 * @method static self CONNECTING()
 * @method static self CONNECTED()
 * @method static self ERROR()
 * @method static self INITIALIZING()
 * @method static self WAITING_QR()
 * @method static self FAILED()
 */
final class WhatsAppStatus extends Enum
{
    protected static function values(): array
    {
        return [
            'DISCONNECTED' => 'disconnected',
            'CONNECTING' => 'connecting',
            'CONNECTED' => 'connected',
            'ERROR' => 'error',
            'INITIALIZING' => 'initializing',
            'WAITING_QR' => 'waiting_qr',
            'FAILED' => 'failed',
        ];
    }

    protected static function labels(): array
    {
        return [
            'DISCONNECTED' => 'Déconnecté',
            'CONNECTING' => 'Connexion en cours',
            'CONNECTED' => 'Connecté',
            'ERROR' => 'Erreur',
            'INITIALIZING' => 'Initialisation',
            'WAITING_QR' => 'En attente du QR Code',
            'FAILED' => 'Échec',
        ];
    }

    public function getBadgeClass(): string
    {
        return match ($this->value) {
            'disconnected' => 'bg-gray-100 text-gray-800',
            'connecting' => 'bg-yellow-100 text-yellow-800',
            'connected' => 'bg-green-100 text-green-800',
            'error' => 'bg-red-100 text-red-800',
            'initializing' => 'bg-blue-100 text-blue-800',
            'waiting_qr' => 'bg-purple-100 text-purple-800',
            'failed' => 'bg-red-100 text-red-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    public function getIcon(): string
    {
        return match ($this->value) {
            'disconnected' => 'x-circle',
            'connecting' => 'clock',
            'connected' => 'check-circle',
            'error' => 'exclamation-circle',
            'initializing' => 'arrow-path',
            'waiting_qr' => 'qr-code',
            'failed' => 'x-mark',
            default => 'question-mark-circle',
        };
    }
}
