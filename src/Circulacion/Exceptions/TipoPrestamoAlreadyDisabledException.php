<?php

declare(strict_types=1);

namespace App\Circulacion\Exceptions;

use App\Shared\Exceptions\BusinessValidationException;

class TipoPrestamoAlreadyDisabledException extends BusinessValidationException
{
    public function __construct(string $field, string $message)
    {
        parent::__construct($field, $message);
    }
}
