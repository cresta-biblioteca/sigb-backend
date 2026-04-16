<?php

declare(strict_types=1);

namespace App\Lectores\Services;

use App\Lectores\Dtos\Response\LectorPerfilResponse;
use App\Lectores\Exceptions\LectorCarreraAlreadyAssignedException;
use App\Lectores\Exceptions\LectorCarreraNotAssignedException;
use App\Lectores\Repositories\CarreraRepository;
use App\Lectores\Repositories\LectorRepository;
use App\Shared\Exceptions\NotFoundException;

class LectorService
{
    public function __construct(
        private readonly LectorRepository $lectorRepository,
        private readonly CarreraRepository $carreraRepository
    ) {
    }

    public function getPerfil(int $userId, string $dni): LectorPerfilResponse
    {
        $lector = $this->lectorRepository->findByUserId($userId);

        if ($lector === null) {
            throw new NotFoundException('Lector no encontrado');
        }

        $carreras = array_map(
            static fn($carrera) => $carrera->getNombre(),
            $lector->getCarreras()
        );

        return new LectorPerfilResponse(
            $lector->getNombre(),
            $lector->getApellido(),
            $lector->getTarjetaId(),
            $dni,
            $lector->getLegajo(),
            $lector->getTelefono(),
            $lector->getEmail(),
            $carreras,
        );
    }

    public function assignCarrera(int $lectorId, int $carreraId): void
    {
        if ($this->lectorRepository->findById($lectorId) === null) {
            throw new NotFoundException('Lector no encontrado');
        }

        if ($this->carreraRepository->findById($carreraId) === null) {
            throw new NotFoundException('Carrera no encontrada');
        }

        if ($this->lectorRepository->hasCarrera($lectorId, $carreraId)) {
            throw new LectorCarreraAlreadyAssignedException();
        }

        $this->lectorRepository->assignCarrera($lectorId, $carreraId);
    }

    public function removeCarrera(int $lectorId, int $carreraId): void
    {
        if ($this->lectorRepository->findById($lectorId) === null) {
            throw new NotFoundException('Lector no encontrado');
        }

        if ($this->carreraRepository->findById($carreraId) === null) {
            throw new NotFoundException('Carrera no encontrada');
        }

        if (!$this->lectorRepository->hasCarrera($lectorId, $carreraId)) {
            throw new LectorCarreraNotAssignedException();
        }

        $this->lectorRepository->removeCarrera($lectorId, $carreraId);
    }
}
