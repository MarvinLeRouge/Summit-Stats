<?php

namespace App\Exceptions;

use Exception;

class GpxParseException extends Exception
{
    public static function insufficientPoints(): self
    {
        return new self("La trace GPX doit contenir au moins 2 points.");
    }
}
