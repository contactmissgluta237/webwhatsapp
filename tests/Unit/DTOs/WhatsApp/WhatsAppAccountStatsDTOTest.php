<?php

declare(strict_types=1);

namespace Tests\Unit\DTOs\WhatsApp;

use App\DTOs\WhatsApp\WhatsAppAccountStatsDTO;
use Tests\TestCase;

final class WhatsAppAccountStatsDTOTest extends TestCase
{
    /**
     * Test DTO creation from stdClass
     */
    public function test_creates_dto_from_std_class(): void
    {
        $stdClass = new \stdClass;
        $stdClass->total_requests = 100;
        $stdClass->unique_conversations = 25;
        $stdClass->total_tokens = 5000;
        $stdClass->total_cost_usd = 1.25;
        $stdClass->total_cost_xaf = 750.0;
        $stdClass->avg_cost_per_request = 0.0125;
        $stdClass->avg_response_time = 1250.5;

        $dto = WhatsAppAccountStatsDTO::fromStdClass($stdClass);

        $this->assertSame(100, $dto->total_requests);
        $this->assertSame(25, $dto->unique_conversations);
        $this->assertSame(5000, $dto->total_tokens);
        $this->assertSame(1.25, $dto->total_cost_usd);
        $this->assertSame(750.0, $dto->total_cost_xaf);
        $this->assertSame(0.0125, $dto->avg_cost_per_request);
        $this->assertSame(1250.5, $dto->avg_response_time);
    }

    /**
     * Test DTO creation with defaults when stdClass has null values
     */
    public function test_creates_dto_with_defaults_from_empty_std_class(): void
    {
        $stdClass = new \stdClass;

        $dto = WhatsAppAccountStatsDTO::fromStdClass($stdClass);

        $this->assertSame(0, $dto->total_requests);
        $this->assertSame(0, $dto->unique_conversations);
        $this->assertSame(0, $dto->total_tokens);
        $this->assertSame(0.0, $dto->total_cost_usd);
        $this->assertSame(0.0, $dto->total_cost_xaf);
        $this->assertSame(0.0, $dto->avg_cost_per_request);
        $this->assertSame(0.0, $dto->avg_response_time);
    }

    /**
     * Test DTO conversion to array with proper rounding
     */
    public function test_converts_to_array_with_proper_rounding(): void
    {
        $dto = new WhatsAppAccountStatsDTO(
            total_requests: 100,
            unique_conversations: 25,
            total_tokens: 5000,
            total_cost_usd: 1.2567891,
            total_cost_xaf: 750.987,
            avg_cost_per_request: 0.01256789,
            avg_response_time: 1250.789
        );

        $array = $dto->toArrayWithRounding();

        $expected = [
            'total_requests' => 100,
            'unique_conversations' => 25,
            'total_tokens' => 5000,
            'total_cost_usd' => 1.256789, // rounded to 6 decimals
            'total_cost_xaf' => 750.99, // rounded to 2 decimals
            'avg_cost_per_request' => 0.012568, // rounded to 6 decimals
            'avg_response_time' => 1251.0, // rounded to 0 decimals
        ];

        $this->assertSame($expected, $array);
    }

    /**
     * Test that DTO inherits from BaseDTO and has standard toArray method
     */
    public function test_inherits_from_base_dto(): void
    {
        $dto = new WhatsAppAccountStatsDTO(
            total_requests: 50,
            unique_conversations: 10
        );

        // Test that it has the standard toArray method from BaseDTO/Spatie Data
        $this->assertTrue(method_exists($dto, 'toArray'));
        $this->assertTrue(method_exists($dto, 'toArrayFiltered'));

        $array = $dto->toArray();
        $this->assertIsArray($array);
        $this->assertArrayHasKey('total_requests', $array);
        $this->assertSame(50, $array['total_requests']);
    }

    /**
     * Test direct DTO construction
     */
    public function test_direct_construction(): void
    {
        $dto = new WhatsAppAccountStatsDTO(
            total_requests: 50,
            unique_conversations: 10,
            total_tokens: 2500,
            total_cost_usd: 0.625,
            total_cost_xaf: 375.0,
            avg_cost_per_request: 0.0125,
            avg_response_time: 800.0
        );

        $this->assertSame(50, $dto->total_requests);
        $this->assertSame(10, $dto->unique_conversations);
        $this->assertSame(2500, $dto->total_tokens);
        $this->assertSame(0.625, $dto->total_cost_usd);
        $this->assertSame(375.0, $dto->total_cost_xaf);
        $this->assertSame(0.0125, $dto->avg_cost_per_request);
        $this->assertSame(800.0, $dto->avg_response_time);
    }
}
