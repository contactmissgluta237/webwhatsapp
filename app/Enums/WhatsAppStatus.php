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
            'RECONNECTING' => 'reconnecting',
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
            'RECONNECTING' => 'Reconnexion',
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
            'reconnecting' => 'badge-warning',
            'disconnected' => 'badge-secondary',
            'connecting' => 'badge-warning',
            'connected' => 'badge-success',
            'error' => 'badge-danger',
            'initializing' => 'badge-info',
            'waiting_qr' => 'badge-primary',
            'failed' => 'badge-danger',
            default => 'badge-secondary',
        };
    }

    public function getIcon(): string
    {
        return match ($this->value) {
            'reconnecting' => 'arrow-path',
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
