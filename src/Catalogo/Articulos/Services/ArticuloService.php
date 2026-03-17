<?php

declare(strict_types=1);

namespace App\Catalogo\Articulos\Services;

use App\Catalogo\Articulos\Dtos\Request\ArticuloRequest;
use App\Catalogo\Articulos\Dtos\Response\ArticuloResponse;
use App\Catalogo\Articulos\Exceptions\ArticuloNotFoundException;
use App\Catalogo\Articulos\Exceptions\MateriaAlreadyEliminatedException;
use App\Catalogo\Articulos\Exceptions\MateriaAlreadyInArticuloException;
use App\Catalogo\Articulos\Exceptions\MateriaNotFoundException;
use App\Catalogo\Articulos\Exceptions\TemaAlreadyEliminatedException;
use App\Catalogo\Articulos\Exceptions\TemaAlreadyInArticuloException;
use App\Catalogo\Articulos\Exceptions\TemaNotFoundException;
use App\Catalogo\Articulos\Mappers\ArticuloMapper;
use App\Catalogo\Articulos\Models\Articulo;
use App\Catalogo\Articulos\Repository\ArticuloRepository;
use App\Shared\Exceptions\BusinessValidationException;

class ArticuloService
{
    public function __construct(private ArticuloRepository $repository)
    {
    }

    /**
     * @return ArticuloResponse[]
     */
    public function getAll(): array
    {
        $articulos = $this->repository->findAll();

        return array_map(
            fn(Articulo $articulo) => ArticuloMapper::toArticuloResponse($articulo),
            $articulos
        );
    }

    public function getById(int $id): ArticuloResponse
    {
        $articulo = $this->repository->findById($id);

        if ($articulo === null) {
            throw new ArticuloNotFoundException($id);
        }

        return ArticuloMapper::toArticuloResponse($articulo);
    }

    public function create(ArticuloRequest $request): ArticuloResponse
    {
        $articulo = Articulo::create(
            titulo: $request->getTitulo(),
            anioPublicacion: $request->getAnioPublicacion(),
            tipoDocumentoId: $request->getTipoDocumentoId(),
            idioma: $request->getIdioma()
        );

        $created = $this->repository->insertArticulo($articulo);

        return ArticuloMapper::toArticuloResponse($created);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function patchArticulo(int $id, array $data): ArticuloResponse
    {
        $existing = $this->repository->findById($id);

        if ($existing === null) {
            throw new ArticuloNotFoundException($id);
        }

        /** @var Articulo $existing */

        $newTipoDocumentoId = array_key_exists('tipo_documento_id', $data)
            ? (int) $data['tipo_documento_id']
            : $existing->getTipoDocumentoId();

        if (
            array_key_exists('tipo_documento_id', $data)
            && $newTipoDocumentoId !== $existing->getTipoDocumentoId()
            && $this->repository->isLinkedToLibro($id)
        ) {
            throw BusinessValidationException::forField(
                'tipo_documento_id',
                'No se puede modificar tipo_documento_id porque el artículo está asociado a un libro'
            );
        }

        $articulo = Articulo::create(
            titulo: array_key_exists('titulo', $data)
            ? trim((string) $data['titulo'])
            : $existing->getTitulo(),
            anioPublicacion: array_key_exists('anio_publicacion', $data)
            ? (int) $data['anio_publicacion']
            : $existing->getAnioPublicacion(),
            tipoDocumentoId: $newTipoDocumentoId,
            idioma: array_key_exists('idioma', $data)
            ? strtolower((string) $data['idioma'])
            : $existing->getIdioma()
        );

        $updated = $this->repository->updateArticulo($id, $articulo);

        return ArticuloMapper::toArticuloResponse($updated);
    }

    public function deleteArticulo(int $id): void
    {
        if ($this->repository->findById($id) === null) {
            throw new ArticuloNotFoundException($id);
        }

        $blockingRelation = $this->repository->getDeleteBlockingRelation($id);
        if ($blockingRelation !== null) {
            throw BusinessValidationException::forField(
                'id',
                "No se puede eliminar el artículo porque tiene {$blockingRelation}"
            );
        }

        $this->repository->delete($id);
    }

    public function addTemaToArticulo(int $articuloId, int $temaId): void
    {
        if ($this->repository->findById($articuloId) === null) {
            throw new ArticuloNotFoundException($articuloId);
        }

        if (!$this->repository->temaExists($temaId)) {
            throw new TemaNotFoundException($temaId);
        }

        if ($this->repository->isTemaAdded($articuloId, $temaId)) {
            throw new TemaAlreadyInArticuloException(
                'tema',
                "El tema (ID: {$temaId}) ya está agregado a este artículo (ID: {$articuloId})"
            );
        }

        $this->repository->addTemaToArticulo($articuloId, $temaId);
    }

    /**
     * @return string[]
     */
    public function getTemaTitlesByArticuloId(int $articuloId): array
    {
        if ($this->repository->findById($articuloId) === null) {
            throw new ArticuloNotFoundException($articuloId);
        }

        return $this->repository->findTemaTitlesByArticuloId($articuloId);
    }

    public function deleteTemaFromArticulo(int $articuloId, int $temaId): void
    {
        if ($this->repository->findById($articuloId) === null) {
            throw new ArticuloNotFoundException($articuloId);
        }

        if (!$this->repository->temaExists($temaId)) {
            throw new TemaNotFoundException($temaId);
        }

        if (!$this->repository->isTemaAdded($articuloId, $temaId)) {
            throw new TemaAlreadyEliminatedException(
                'tema',
                "El tema (ID: {$temaId}) no pertenece al artículo (ID: {$articuloId})"
            );
        }

        $this->repository->deleteTemaFromArticulo($articuloId, $temaId);
    }

    public function addMateriaToArticulo(int $articuloId, int $materiaId): void
    {
        if ($this->repository->findById($articuloId) === null) {
            throw new ArticuloNotFoundException($articuloId);
        }

        if (!$this->repository->materiaExists($materiaId)) {
            throw new MateriaNotFoundException($materiaId);
        }

        if ($this->repository->isMateriaAdded($articuloId, $materiaId)) {
            throw new MateriaAlreadyInArticuloException(
                'materia',
                "La materia (ID: {$materiaId}) ya está agregada a este artículo (ID: {$articuloId})"
            );
        }

        $this->repository->addMateriaToArticulo($articuloId, $materiaId);
    }

    /**
     * @return string[]
     */
    public function getMateriaTitlesByArticuloId(int $articuloId): array
    {
        if ($this->repository->findById($articuloId) === null) {
            throw new ArticuloNotFoundException($articuloId);
        }

        return $this->repository->findMateriaTitlesByArticuloId($articuloId);
    }

    public function deleteMateriaFromArticulo(int $articuloId, int $materiaId): void
    {
        if ($this->repository->findById($articuloId) === null) {
            throw new ArticuloNotFoundException($articuloId);
        }

        if (!$this->repository->materiaExists($materiaId)) {
            throw new MateriaNotFoundException($materiaId);
        }

        if (!$this->repository->isMateriaAdded($articuloId, $materiaId)) {
            throw new MateriaAlreadyEliminatedException(
                'materia',
                "La materia (ID: {$materiaId}) no pertenece al artículo (ID: {$articuloId})"
            );
        }

        $this->repository->deleteMateriaFromArticulo($articuloId, $materiaId);
    }
}
