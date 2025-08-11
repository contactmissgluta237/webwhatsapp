<?php

namespace App\Exceptions\Auth;

use Exception;

class InvalidCredentialsException extends Exception
{
    protected $message = 'The provided credentials do not match our records.';
    protected $code = 401;
}
