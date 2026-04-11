<?php

declare(strict_types=1);

namespace App\Lectores\Services;

use App\Lectores\Dtos\Response\LectorPerfilResponse;
use App\Lectores\Repositories\LectorRepository;
use App\Shared\Exceptions\NotFoundException;

class LectorService
{
    public function __construct(private readonly LectorRepository $lectorRepository)
    {
    }

    public function getPerfil(int $userId, string $dni): LectorPerfilResponse
    {
        $lector = $this->lectorRepository->findByUserId($userId);

        if ($lector === null) {
            throw new NotFoundException('Lector no encontrado');
        }

        return new LectorPerfilResponse(
            $lector->getNombre(),
            $lector->getApellido(),
            $lector->getTarjetaId(),
            $dni,
            $lector->getLegajo(),
            $lector->getTelefono(),
            $lector->getEmail(),
        );
    }
}
