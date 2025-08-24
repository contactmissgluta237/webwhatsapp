<?php

declare(strict_types=1);

namespace App\Enums;

use Spatie\Enum\Enum;

/**
 * @method static self TEXT()
 * @method static self SHOW_PRODUCTS()
 * @method static self SHOW_CATALOG()
 */
final class AIResponseAction extends Enum
{
    protected static function values(): array
    {
        return [
            'TEXT' => 'text',
            'SHOW_PRODUCTS' => 'show_products',
            'SHOW_CATALOG' => 'show_catalog',
        ];
    }

    protected static function labels(): array
    {
        return [
            'TEXT' => 'Réponse texte simple',
            'SHOW_PRODUCTS' => 'Afficher des produits spécifiques',
            'SHOW_CATALOG' => 'Afficher le catalogue complet',
        ];
    }

    public function shouldSendProducts(): bool
    {
        return in_array($this->value, ['show_products', 'show_catalog']);
    }

    public function getDescription(): string
    {
        return match ($this->value) {
            'text' => 'Envoie uniquement un message texte',
            'show_products' => 'Envoie un message texte suivi de produits spécifiques',
            'show_catalog' => 'Envoie un message texte suivi de tout le catalogue',
            default => 'Action inconnue',
        };
    }
}
