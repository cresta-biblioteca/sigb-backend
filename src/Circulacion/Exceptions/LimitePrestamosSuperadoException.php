<?php

declare(strict_types=1);

namespace App\Circulacion\Exceptions;

use App\Shared\Exceptions\BusinessRuleException;

class LimitePrestamosSuperadoException extends BusinessRuleException
{
    public function __construct(int $limite)
    {
        parent::__construct(
            "El lector alcanzó el límite máximo de {$limite} préstamos para este tipo"
        );
    }

    public function getErrorCode(): string
    {
        return 'LIMITE_PRESTAMOS_SUPERADO';
    }
}
