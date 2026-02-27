<?php

declare(strict_types=1);

namespace App\Catalogo\Articulos\Services;

use App\Catalogo\Articulos\Dtos\Request\MateriaRequest;
use App\Catalogo\Articulos\Dtos\Response\MateriaResponse;
use App\Catalogo\Articulos\Exceptions\MateriaAlreadyExistsException;
use App\Catalogo\Articulos\Exceptions\MateriaNotFoundException;
use App\Catalogo\Articulos\Mappers\MateriaMapper;
use App\Catalogo\Articulos\Models\Materia;
use App\Catalogo\Articulos\Repository\MateriaRepository;

class MateriaService
{
    public function __construct(private MateriaRepository $repo)
    {
    }

    /**
     * @return MateriaResponse[]
     */
    public function getAll(): array
    {
        $materias = $this->repo->findAll();
        $materiasDTO = array_map(fn($materia) => MateriaMapper::toMateriaResponse($materia), $materias);
        return $materiasDTO;
    }

    public function getById(int $id): MateriaResponse
    {
        $materia = $this->repo->findById($id);

        if (!$materia) {
            throw new MateriaNotFoundException($id);
        }
        return MateriaMapper::toMateriaResponse($materia);
    }

    public function createMateria(MateriaRequest $request): MateriaResponse
    {
        // Ya se valida en el modelo que no este vacio y no supere los 100 caracteres
        $materia = MateriaMapper::fromMateriaRequest($request);

        // Sugerencia -> cambiar a unique el titulo en la base de datos y se evita este check
        if ($this->repo->findCoincidence($materia->getTitulo())) {
            throw new MateriaAlreadyExistsException($materia->getTitulo());
        }

        $materiaCreada = $this->repo->insertMateria($materia);
        return MateriaMapper::toMateriaResponse($materiaCreada);
    }

    public function updateMateria(int $id, MateriaRequest $request): MateriaResponse
    {
        $materia = MateriaMapper::fromMateriaRequest($request);

        $materiaExistente = $this->repo->findById($id);

        if (!$materiaExistente) {
            throw new MateriaNotFoundException($id);
        }

        /** @var Materia $materiaExistente */

        if ($materia->getTitulo() !== $materiaExistente->getTitulo()) {
            $coincidencia = $this->repo->findCoincidence($materia->getTitulo());
            if ($coincidencia && $coincidencia->getId() !== $id) {
                throw new MateriaAlreadyExistsException($materia->getTitulo());
            }
        }

        $materiaActualizada = $this->repo->updateMateria($id, $materia);

        return MateriaMapper::toMateriaResponse($materiaActualizada);
    }

    /**
     * @return MateriaResponse[]
     */
    public function getByParams(array $params): array
    {
        $materias = $this->repo->findByParams($params);
        $materiasDTO = array_map(fn($materia) => MateriaMapper::toMateriaResponse($materia), $materias);
        return $materiasDTO;
    }

    public function deleteMateria(int $id): void
    {
        $materiaExistente = $this->repo->findById($id);

        if (!$materiaExistente) {
            throw new MateriaNotFoundException($id);
        }

        $borrada = $this->repo->delete($id);
        if (!$borrada) {
            throw new MateriaNotFoundException($id);
        }
    }
}
