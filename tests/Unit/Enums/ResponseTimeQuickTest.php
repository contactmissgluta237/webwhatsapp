<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\ResponseTime;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

final class ResponseTimeQuickTest extends TestCase
{
    #[Test]
    public function response_time_uses_fast_config_in_tests(): void
    {
        $responseTime = ResponseTime::RANDOM();
        $delay = $responseTime->getDelay();
        
        // En mode test: doit être entre 1 et 2 secondes
        $this->assertGreaterThanOrEqual(1, $delay);
        $this->assertLessThanOrEqual(2, $delay);
        
        echo "\n✅ Délai test: {$delay}s (optimisé !)";
    }
}
