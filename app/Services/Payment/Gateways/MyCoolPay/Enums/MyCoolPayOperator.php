<?php

namespace App\Services\Payment\Gateways\MyCoolPay\Enums;

use Spatie\Enum\Laravel\Enum;

/**
 * @method static self CM_MOMO()
 * @method static self CM_OM()
 * @method static self MCP()
 * @method static self CARD()
 */
final class MyCoolPayOperator extends Enum
{
    protected static function values(): array
    {
        return [
            'CM_MOMO' => 'CM_MOMO',
            'CM_OM' => 'CM_OM',
            'MCP' => 'MCP',
            'CARD' => 'CARD',
        ];
    }
}
