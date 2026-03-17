<?php

namespace App\Exceptions;

use Exception;

class GpxParseException extends Exception
{
    /**
     * Exception levée quand la trace GPX contient moins de 2 points.
     */
    public static function insufficientPoints(): self
    {
        return new self('La trace GPX doit contenir au moins 2 points.');
    }
}
