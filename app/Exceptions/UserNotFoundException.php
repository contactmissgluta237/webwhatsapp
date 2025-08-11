<?php

namespace App\Exceptions;

use Exception;

class UserNotFoundException extends Exception
{
    public function __construct(string $identifier)
    {
        parent::__construct("User with identifier {$identifier} not found.");
    }
}
