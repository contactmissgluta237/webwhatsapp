<?php

declare(strict_types=1);

namespace App\Traits\WhatsApp;

trait CalculatesConnectionIntervals
{
    private function calculateNextInterval(int $attempt): int
    {
        $intervals = [2, 2, 2, 3, 5];
        $index = min($attempt - 1, count($intervals) - 1);

        return $intervals[$index] ?? 3;
    }
}
