<?php

declare(strict_types=1);

namespace App\Catalogo\Articulos\Services;

use App\Catalogo\Articulos\Dtos\Request\ArticuloRequest;
use App\Catalogo\Articulos\Dtos\Response\ArticuloResponse;
use App\Catalogo\Articulos\Exceptions\ArticuloNotFoundException;
use App\Catalogo\Articulos\Exceptions\TemaAlreadyEliminatedException;
use App\Catalogo\Articulos\Exceptions\TemaAlreadyInArticuloException;
use App\Catalogo\Articulos\Exceptions\TemaNotFoundException;
use App\Catalogo\Articulos\Mappers\ArticuloMapper;
use App\Catalogo\Articulos\Models\Articulo;
use App\Catalogo\Articulos\Repository\ArticuloRepository;
use App\Shared\Exceptions\BusinessRuleException;

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
            fn (Articulo $articulo) => ArticuloMapper::toArticuloResponse($articulo),
            $articulos
        );
    }

    public function getById(int $id): ArticuloResponse
    {
        $articulo = $this->repository->findById($id);

        if ($articulo === null) {
            throw new ArticuloNotFoundException();
        }

        return ArticuloMapper::toArticuloResponse($articulo);
    }

    public function create(ArticuloRequest $request): ArticuloResponse
    {
        $articulo = Articulo::create(
            titulo: $request->getTitulo(),
            anioPublicacion: $request->getAnioPublicacion(),
            tipoDocumentoId: $request->getTipoDocumentoId(),
            idioma: $request->getIdioma(),
            descripcion: $request->getDescripcion()
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
            throw new ArticuloNotFoundException();
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
            throw new BusinessRuleException('No se puede modificar tipo_documento_id porque el artículo está asociado a un libro', 'tipo_documento_id');
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
                : $existing->getIdioma(),
            descripcion: array_key_exists('descripcion', $data)
                ? ($data['descripcion'] !== null ? trim((string) $data['descripcion']) : null)
                : $existing->getDescripcion()
        );

        $updated = $this->repository->updateArticulo($id, $articulo);

        return ArticuloMapper::toArticuloResponse($updated);
    }

    public function deleteArticulo(int $id): void
    {
        if ($this->repository->findById($id) === null) {
            throw new ArticuloNotFoundException();
        }

        $blockingRelation = $this->repository->getDeleteBlockingRelation($id);
        if ($blockingRelation !== null) {
            throw new BusinessRuleException("No se puede eliminar el artículo porque tiene {$blockingRelation}", 'id');
        }

        $this->repository->delete($id);
    }

    public function addTemaToArticulo(int $articuloId, int $temaId): void
    {
        if ($this->repository->findById($articuloId) === null) {
            throw new ArticuloNotFoundException();
        }

        if (!$this->repository->temaExists($temaId)) {
            throw new TemaNotFoundException();
        }

        if ($this->repository->isTemaAdded($articuloId, $temaId)) {
            throw new TemaAlreadyInArticuloException();
        }

        $this->repository->addTemaToArticulo($articuloId, $temaId);
    }

    /**
     * @return string[]
     */
    public function getTemaTitlesByArticuloId(int $articuloId): array
    {
        if ($this->repository->findById($articuloId) === null) {
            throw new ArticuloNotFoundException();
        }

        return $this->repository->findTemaTitlesByArticuloId($articuloId);
    }

    public function deleteTemaFromArticulo(int $articuloId, int $temaId): void
    {
        if ($this->repository->findById($articuloId) === null) {
            throw new ArticuloNotFoundException();
        }

        if (!$this->repository->temaExists($temaId)) {
            throw new TemaNotFoundException();
        }

        if (!$this->repository->isTemaAdded($articuloId, $temaId)) {
            throw new TemaAlreadyEliminatedException();
        }

        $this->repository->deleteTemaFromArticulo($articuloId, $temaId);
    }
}
