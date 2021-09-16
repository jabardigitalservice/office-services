<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Validation\ValidationException;

class InvalidTokenException extends Exception
{
    public function validationException($message)
    {
        return ValidationException::withMessages([
            'message' => $message
        ]);
    }
}
