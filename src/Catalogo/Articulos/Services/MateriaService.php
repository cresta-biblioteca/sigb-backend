<?php

declare(strict_types=1);

namespace App\Catalogo\Articulos\Services;

use App\Catalogo\Articulos\Exceptions\MateriaAlreadyExistsException;
use App\Catalogo\Articulos\Models\Materia;
use App\Catalogo\Articulos\Repository\MateriaRepository;
use App\Shared\Exceptions\ValidationException;

class MateriaService
{
    public function __construct(private MateriaRepository $repo)
    {
    }

    public function getAll(): array {
        $materias = $this->repo->findAll();
        $materiasDTO = array_map(fn($materia) => $materia->toArray(), $materias);
        return $materiasDTO;
    }

    public function createMateria(array $input): Materia
    {
        // Validar que el campo titulo exista
        if (!isset($input['titulo'])) {
            throw ValidationException::forField('titulo', 'El campo titulo es requerido');
        }

        $titulo = trim($input['titulo']);

        // Validar que no esté vacío después del trim
        if (empty($titulo)) {
            throw ValidationException::forField('titulo', 'El campo titulo no puede estar vacío');
        }

        // Verificar si ya existe una materia con ese título
        if ($this->repo->findCoincidence($titulo)) {
            throw new MateriaAlreadyExistsException($titulo);
        }

        // Crear y guardar la materia (las validaciones de longitud están en el modelo)
        $materia = Materia::create($titulo);
        $this->repo->save($materia);

        return $materia;
    }
}