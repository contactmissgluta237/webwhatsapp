<?php

declare(strict_types=1);

namespace App\Listeners;

use Illuminate\Support\Facades\Log;

/**
 * Base class for all listeners with anti-multilistening protection
 *
 * This class solves the problem of event duplication where the same
 * listener can be called multiple times for the same event.
 */
abstract class BaseListener
{
    private static array $processedEvents = [];

    /**
     * Template method that handles anti-multilistening protection
     */
    public function handle($event): void
    {
        // Create a unique identifier for the event
        $eventId = $this->generateEventId($event);

        if (in_array($eventId, self::$processedEvents, true)) {
            Log::info('['.static::class.'] Duplicate event detected, skipping', [
                'event_id' => $eventId,
                'event_class' => get_class($event),
            ]);

            return;
        }

        self::$processedEvents[] = $eventId;

        // Call the handleEvent method defined by child classes
        $this->handleEvent($event);
    }

    /**
     * Generates a unique ID for the event based on its properties
     *
     * Child classes can override this method to customize
     * the ID generation according to the type of event they handle
     */
    protected function generateEventId($event): string
    {
        // Generic ID based on event class and timestamp
        return md5(
            get_class($event).
            get_class($this).
            serialize($this->getEventIdentifiers($event))
        );
    }

    /**
     * Extracts unique identifiers from the event
     *
     * Child classes must override this method to return
     * the properties that make the event unique
     */
    abstract protected function getEventIdentifiers($event): array;

    /**
     * Main method that child classes must implement
     *
     * This is where the listener's business logic goes
     */
    abstract protected function handleEvent($event): void;

    /**
     * Cleans processed events (useful for testing)
     */
    public static function clearProcessedEvents(): void
    {
        self::$processedEvents = [];
    }

    /**
     * Returns the number of processed events (useful for debugging)
     */
    public static function getProcessedEventsCount(): int
    {
        return count(self::$processedEvents);
    }
}
