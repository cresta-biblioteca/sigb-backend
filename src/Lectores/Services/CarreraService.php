<?php

declare(strict_types=1);

namespace App\Lectores\Services;

use App\Catalogo\Articulos\Exceptions\MateriaAlreadyEliminatedException;
use App\Catalogo\Articulos\Exceptions\MateriaAlreadyInCarreraException;
use App\Catalogo\Articulos\Exceptions\MateriaNotFoundException;
use App\Catalogo\Articulos\Mappers\MateriaMapper;
use App\Catalogo\Articulos\Repository\MateriaRepository;
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
    public function __construct(private CarreraRepository $repo, private MateriaRepository $materiaRepo)
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
            throw new CarreraNotFoundException($id);
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

    public function getMateriasByCarrera(int $idCarrera): array
    {
        $existeCarrera = $this->repo->exists($idCarrera);
        if (!$existeCarrera) {
            throw new CarreraNotFoundException($idCarrera);
        }
        $materias = $this->materiaRepo->findMateriasByCarrera($idCarrera);
        $materiasDTO = array_map(fn($materia) => MateriaMapper::toMateriaResponse($materia), $materias);
        return $materiasDTO;
    }

    public function createCarrera(CreateCarreraRequest $request): CarreraResponse
    {
        $carreraExistente = $this->repo->findCoincidence($request->cod, $request->nombre);
        if ($carreraExistente) {
            if ($carreraExistente->getCodigo() === strtoupper($request->cod)) {
                throw new CarreraAlreadyExistsException("codigo", $request->cod);
            }
            throw new CarreraAlreadyExistsException("nombre", $request->nombre);
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
            throw new CarreraNotFoundException($id);
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
                throw new CarreraAlreadyExistsException("codigo", $request->cod);
            }
            throw new CarreraAlreadyExistsException("nombre", $request->nombre);
        }

        $carrera = CarreraMapper::fromCarreraRequest($request);

        $carreraActualizada = $this->repo->updateCarrera($id, $carrera);

        return CarreraMapper::toCarreraResponse($carreraActualizada);
    }

    public function deleteCarrera(int $id): void
    {
        if (!$this->repo->findById($id)) {
            throw new CarreraNotFoundException($id);
        }

        $borrada = $this->repo->delete($id);
        if (!$borrada) {
            throw new CarreraNotFoundException($id);
        }
    }

    public function addMateriaToCarrera(int $idCarrera, int $idMateria): void
    {
        $carreraExistente = $this->repo->findById($idCarrera);
        if (!$carreraExistente) {
            throw new CarreraNotFoundException($idCarrera);
        }
        $materiaExistente = $this->materiaRepo->findById($idMateria);
        if (!$materiaExistente) {
            throw new MateriaNotFoundException($idMateria);
        }
        $estaAgregada = $this->repo->isMateriaAdded($idCarrera, $idMateria);
        if ($estaAgregada) {
            throw new MateriaAlreadyInCarreraException(
                "materia",
                "La materia(ID: {$idMateria}) ya esta agregada a esta carrera(ID: {$idCarrera})"
            );
        }

        $this->repo->addMateriaToCarrera($idCarrera, $idMateria);
    }

    public function deleteMateriaFromCarrera(int $idCarrera, int $idMateria): void
    {
        $carreraExistente = $this->repo->findById($idCarrera);
        if (!$carreraExistente) {
            throw new CarreraNotFoundException($idCarrera);
        }
        $materiaExistente = $this->materiaRepo->findById($idMateria);
        if (!$materiaExistente) {
            throw new MateriaNotFoundException($idMateria);
        }
        $existe = $this->repo->isMateriaAdded($idCarrera, $idMateria);
        if (!$existe) {
            throw new MateriaAlreadyEliminatedException(
                "materia",
                "La materia(ID: {$idMateria}) no pertenece a la carrera(ID: {$idCarrera})"
            );
        }
        $this->repo->deleteMateriaFromCarrera($idCarrera, $idMateria);
    }
}
