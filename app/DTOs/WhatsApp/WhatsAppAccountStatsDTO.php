<?php

declare(strict_types=1);

namespace App\DTOs\WhatsApp;

use App\DTOs\BaseDTO;

final class WhatsAppAccountStatsDTO extends BaseDTO
{
    public function __construct(
        public int $total_requests = 0,
        public int $unique_conversations = 0,
        public int $total_tokens = 0,
        public float $total_cost_usd = 0.0,
        public float $total_cost_xaf = 0.0,
        public float $avg_cost_per_request = 0.0,
        public float $avg_response_time = 0.0,
    ) {}

    public function toArrayWithRounding(): array
    {
        return [
            'total_requests' => $this->total_requests,
            'unique_conversations' => $this->unique_conversations,
            'total_tokens' => $this->total_tokens,
            'total_cost_usd' => round($this->total_cost_usd, 6),
            'total_cost_xaf' => round($this->total_cost_xaf, 2),
            'avg_cost_per_request' => round($this->avg_cost_per_request, 6),
            'avg_response_time' => round($this->avg_response_time, 0),
        ];
    }
}
