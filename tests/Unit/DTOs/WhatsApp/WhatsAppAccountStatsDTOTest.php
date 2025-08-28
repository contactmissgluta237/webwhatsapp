<?php

declare(strict_types=1);

namespace Tests\Unit\DTOs\WhatsApp;

use App\DTOs\WhatsApp\WhatsAppAccountStatsDTO;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class WhatsAppAccountStatsDTOTest extends TestCase
{
    #[Test]
    public function test_creates_dto_from_array_data(): void
    {
        $data = [
            'total_requests' => 100,
            'unique_conversations' => 25,
            'total_tokens' => 5000,
            'total_cost_usd' => 1.25,
            'total_cost_xaf' => 750.0,
            'avg_cost_per_request' => 0.0125,
            'avg_response_time' => 1250.5,
        ];

        $dto = new WhatsAppAccountStatsDTO(
            total_requests: $data['total_requests'],
            unique_conversations: $data['unique_conversations'],
            total_tokens: $data['total_tokens'],
            total_cost_usd: $data['total_cost_usd'],
            total_cost_xaf: $data['total_cost_xaf'],
            avg_cost_per_request: $data['avg_cost_per_request'],
            avg_response_time: $data['avg_response_time']
        );

        $this->assertSame(100, $dto->total_requests);
        $this->assertSame(25, $dto->unique_conversations);
        $this->assertSame(5000, $dto->total_tokens);
        $this->assertSame(1.25, $dto->total_cost_usd);
        $this->assertSame(750.0, $dto->total_cost_xaf);
        $this->assertSame(0.0125, $dto->avg_cost_per_request);
        $this->assertSame(1250.5, $dto->avg_response_time);
    }

    #[Test]
    public function test_creates_dto_with_default_values(): void
    {
        $dto = new WhatsAppAccountStatsDTO;

        $this->assertSame(0, $dto->total_requests);
        $this->assertSame(0, $dto->unique_conversations);
        $this->assertSame(0, $dto->total_tokens);
        $this->assertSame(0.0, $dto->total_cost_usd);
        $this->assertSame(0.0, $dto->total_cost_xaf);
        $this->assertSame(0.0, $dto->avg_cost_per_request);
        $this->assertSame(0.0, $dto->avg_response_time);
    }

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
    public function test_validates_data_types_correctly(): void
    {
        $dto = new WhatsAppAccountStatsDTO(
            total_requests: 100,
            unique_conversations: 25,
            total_tokens: 5000,
            total_cost_usd: 1.25,
            total_cost_xaf: 750.0,
            avg_cost_per_request: 0.0125,
            avg_response_time: 800.0
        );

        $this->assertIsInt($dto->total_requests);
        $this->assertIsInt($dto->unique_conversations);
        $this->assertIsInt($dto->total_tokens);
        $this->assertIsFloat($dto->total_cost_usd);
        $this->assertIsFloat($dto->total_cost_xaf);
        $this->assertIsFloat($dto->avg_cost_per_request);
        $this->assertIsFloat($dto->avg_response_time);
    }

    #[Test]
    public function test_handles_large_numbers_correctly(): void
    {
        $dto = new WhatsAppAccountStatsDTO(
            total_requests: 999999,
            unique_conversations: 50000,
            total_tokens: 10000000,
            total_cost_usd: 999.999999,
            total_cost_xaf: 599999.99,
            avg_cost_per_request: 0.000001,
            avg_response_time: 9999.999
        );

        $this->assertSame(999999, $dto->total_requests);
        $this->assertSame(50000, $dto->unique_conversations);
        $this->assertSame(10000000, $dto->total_tokens);
        $this->assertSame(999.999999, $dto->total_cost_usd);
        $this->assertSame(599999.99, $dto->total_cost_xaf);
        $this->assertSame(0.000001, $dto->avg_cost_per_request);
        $this->assertSame(9999.999, $dto->avg_response_time);
    }

    #[Test]
    public function test_rounding_precision_in_array_conversion(): void
    {
        $dto = new WhatsAppAccountStatsDTO(
            total_requests: 100,
            unique_conversations: 25,
            total_tokens: 5000,
            total_cost_usd: 1.1234567890123, // Très précis
            total_cost_xaf: 750.987654, // Très précis
            avg_cost_per_request: 0.0123456789, // Très précis
            avg_response_time: 1250.6789 // Très précis
        );

        $array = $dto->toArrayWithRounding();

        // Vérifier les arrondis selon les spécifications
        $this->assertSame(1.123457, $array['total_cost_usd']); // 6 décimales
        $this->assertSame(750.99, $array['total_cost_xaf']); // 2 décimales
        $this->assertSame(0.012346, $array['avg_cost_per_request']); // 6 décimales
        $this->assertSame(1251.0, $array['avg_response_time']); // 0 décimales
    }

    #[Test]
    public function test_toarray_from_base_dto_works_correctly(): void
    {
        $dto = new WhatsAppAccountStatsDTO(
            total_requests: 50,
            unique_conversations: 10,
            total_tokens: 2500
        );

        $array = $dto->toArray();

        $this->assertIsArray($array);
        $this->assertArrayHasKey('total_requests', $array);
        $this->assertArrayHasKey('unique_conversations', $array);
        $this->assertArrayHasKey('total_tokens', $array);
        $this->assertArrayHasKey('total_cost_usd', $array);
        $this->assertArrayHasKey('total_cost_xaf', $array);
        $this->assertArrayHasKey('avg_cost_per_request', $array);
        $this->assertArrayHasKey('avg_response_time', $array);

        $this->assertEquals(50, $array['total_requests']);
        $this->assertEquals(10, $array['unique_conversations']);
        $this->assertEquals(2500, $array['total_tokens']);
        $this->assertEquals(0.0, $array['total_cost_usd']); // Valeur par défaut
    }
}
