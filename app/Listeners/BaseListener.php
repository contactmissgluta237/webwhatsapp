<?php

declare(strict_types=1);

namespace App\Listeners;

use Illuminate\Support\Facades\Log;

/**
 * Classe de base pour tous les listeners avec protection anti-multilistening
 *
 * Cette classe résout le problème de duplication des événements où le même
 * listener peut être appelé plusieurs fois pour le même événement.
 */
abstract class BaseListener
{
    private static array $processedEvents = [];

    /**
     * Méthode template qui gère la protection anti-multilistening
     */
    public function handle($event): void
    {
        // Créer un identifiant unique pour l'événement
        $eventId = $this->generateEventId($event);

        if (in_array($eventId, self::$processedEvents, true)) {
            Log::info('['.static::class.'] Duplicate event detected, skipping', [
                'event_id' => $eventId,
                'event_class' => get_class($event),
            ]);

            return;
        }

        self::$processedEvents[] = $eventId;

        // Appeler la méthode handleEvent définie par les classes enfants
        $this->handleEvent($event);
    }

    /**
     * Génère un ID unique pour l'événement basé sur ses propriétés
     *
     * Les classes enfants peuvent override cette méthode pour personnaliser
     * la génération d'ID selon le type d'événement qu'elles traitent
     */
    protected function generateEventId($event): string
    {
        // ID générique basé sur la classe de l'événement et timestamp
        return md5(
            get_class($event).
            get_class($this).
            serialize($this->getEventIdentifiers($event))
        );
    }

    /**
     * Extrait les identifiants uniques de l'événement
     *
     * Les classes enfants doivent override cette méthode pour retourner
     * les propriétés qui rendent l'événement unique
     */
    abstract protected function getEventIdentifiers($event): array;

    /**
     * Méthode principale que les classes enfants doivent implémenter
     *
     * C'est ici que va la logique métier du listener
     */
    abstract protected function handleEvent($event): void;

    /**
     * Nettoie les événements traités (utile pour les tests)
     */
    public static function clearProcessedEvents(): void
    {
        self::$processedEvents = [];
    }

    /**
     * Retourne le nombre d'événements traités (utile pour debug)
     */
    public static function getProcessedEventsCount(): int
    {
        return count(self::$processedEvents);
    }
}
