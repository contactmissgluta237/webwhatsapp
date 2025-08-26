<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

class InsufficientFundsException extends Exception
{
    public function __construct(string $message = 'No remaining messages and insufficient wallet balance')
    {
        parent::__construct($message, 402); // 402 Payment Required
    }
}
