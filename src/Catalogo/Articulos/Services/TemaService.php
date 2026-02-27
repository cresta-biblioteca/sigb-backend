<?php

declare(strict_types=1);

namespace App\Catalogo\Articulos\Services;

use App\Catalogo\Articulos\Dtos\Request\TemaRequest;
use App\Catalogo\Articulos\Dtos\Response\TemaResponse;
use App\Catalogo\Articulos\Exceptions\TemaAlreadyExistsException;
use App\Catalogo\Articulos\Exceptions\TemaNotFoundException;
use App\Catalogo\Articulos\Mappers\TemaMapper;
use App\Catalogo\Articulos\Models\Tema;
use App\Catalogo\Articulos\Repository\TemaRepository;

class TemaService
{
    public function __construct(private TemaRepository $repo)
    {
    }

    /**
     * @return TemaResponse[]
     */
    public function getAll(): array
    {
        $temas = $this->repo->findAll();
        $temasDTO = array_map(fn($tema) => TemaMapper::toTemaResponse($tema), $temas);
        return $temasDTO;
    }

    /**
     * Busca temas filtrando por parámetros
     *
     * @param array{titulo?: string} $params
     * @return TemaResponse[]
     */
    public function getByParams(array $params = []): array
    {
        $temas = $this->repo->findByParams($params);

        $temasDTO = array_map(
            fn($tema) => TemaMapper::toTemaResponse($tema),
            $temas
        );
        return $temasDTO;
    }

    public function getById(int $id): TemaResponse
    {
        $tema = $this->repo->findById($id);

        if (!$tema) {
            throw new TemaNotFoundException($id);
        }
        return TemaMapper::toTemaResponse($tema);
    }

    public function createTema(TemaRequest $request): TemaResponse
    {
        $tema = TemaMapper::fromTemaRequest($request);

        if ($this->repo->findCoincidence($tema->getTitulo())) {
            throw new TemaAlreadyExistsException($tema->getTitulo());
        }

        $temaCreado = $this->repo->insertTema($tema);
        return TemaMapper::toTemaResponse($temaCreado);
    }

    public function updateTema(int $id, TemaRequest $request): TemaResponse
    {
        $tema = TemaMapper::fromTemaRequest($request);

        $temaExistente = $this->repo->findById($id);

        if (!$temaExistente) {
            throw new TemaNotFoundException($id);
        }

        /** @var Tema|null $temaCoincidente */
        $temaCoincidente = $this->repo->findCoincidence($tema->getTitulo());
        if ($temaCoincidente !== null && $temaCoincidente->getId() !== $id) {
            throw new TemaAlreadyExistsException($tema->getTitulo());
        }

        $temaActualizado = $this->repo->updateTema($id, $tema);

        return TemaMapper::toTemaResponse($temaActualizado);
    }

    public function deleteTema(int $id): void
    {
        $temaExistente = $this->repo->findById($id);

        if (!$temaExistente) {
            throw new TemaNotFoundException($id);
        }

        $borrado = $this->repo->delete($id);
        if (!$borrado) {
            throw new TemaNotFoundException($id);
        }
    }
}
