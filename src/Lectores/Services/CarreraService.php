<?php

declare(strict_types=1);

namespace App\Lectores\Services;

use App\Lectores\Dtos\Request\CreateCarreraRequest;
use App\Lectores\Dtos\Request\UpdateCarreraRequest;
use App\Lectores\Dtos\Response\CarreraResponse;
use App\Lectores\Exceptions\CarreraAlreadyExistsException;
use App\Lectores\Exceptions\CarreraNotFoundException;
use App\Lectores\Mappers\CarreraMapper;
use App\Lectores\Models\Carrera;
use App\Lectores\Repositories\CarreraRepository;

class CarreraService
{
    public function __construct(private CarreraRepository $repo)
    {
    }

    public function getAll(): array
    {
        $carreras = $this->repo->findAll();
        $carrerasDTO = array_map(fn($carrera) => CarreraMapper::toCarreraResponse($carrera), $carreras);
        return $carrerasDTO;
    }

    public function getById(int $id): CarreraResponse
    {
        $carreraExistente = $this->repo->findById($id);
        if (!$carreraExistente) {
            throw new CarreraNotFoundException();
        }

        return CarreraMapper::toCarreraResponse($carreraExistente);
    }

    /**
     * Busca carreras filtrando por código y/o nombre
     *
     * @param array{cod?: string, nombre?: string} $params
     * @return CarreraResponse[]
     */
    public function getByParams(array $params = []): array
    {
        $allowedKeys = ['cod', 'nombre', 'order'];
        $filteredParams = array_intersect_key($params, array_flip($allowedKeys));

        $carreras = $this->repo->findByParams($filteredParams);

        return array_map(
            fn($carrera) => CarreraMapper::toCarreraResponse($carrera),
            $carreras
        );
    }

    public function createCarrera(CreateCarreraRequest $request): CarreraResponse
    {
        $carreraExistente = $this->repo->findCoincidence($request->cod, $request->nombre);
        if ($carreraExistente) {
            if ($carreraExistente->getCodigo() === strtoupper($request->cod)) {
                throw new CarreraAlreadyExistsException();
            }
            throw new CarreraAlreadyExistsException();
        }
        $carrera = CarreraMapper::fromCarreraRequest($request);

        $carreraInsertada = $this->repo->insertCarrera($carrera);

        return CarreraMapper::toCarreraResponse($carreraInsertada);
    }

    public function updateCarrera(int $id, UpdateCarreraRequest $request): CarreraResponse
    {
        /** @var Carrera $carreraExistente */
        $carreraExistente = $this->repo->findById($id);
        if (!$carreraExistente) {
            throw new CarreraNotFoundException();
        }
        if ($request->cod === null) {
            $request->setCod($carreraExistente->getCodigo());
        }
        if ($request->nombre === null) {
            $request->setNombre($carreraExistente->getNombre());
        }
        $coincidence = $this->repo->findCoincidence($request->cod, $request->nombre);
        if ($coincidence && $coincidence->getId() !== $id) {
            if ($coincidence->getCodigo() === strtoupper($request->cod)) {
                throw new CarreraAlreadyExistsException();
            }
            throw new CarreraAlreadyExistsException();
        }

        $carrera = CarreraMapper::fromCarreraRequest($request);

        $carreraActualizada = $this->repo->updateCarrera($id, $carrera);

        return CarreraMapper::toCarreraResponse($carreraActualizada);
    }

    public function deleteCarrera(int $id): void
    {
        if (!$this->repo->findById($id)) {
            throw new CarreraNotFoundException();
        }

        $borrada = $this->repo->delete($id);
        if (!$borrada) {
            throw new CarreraNotFoundException();
        }
    }
}
