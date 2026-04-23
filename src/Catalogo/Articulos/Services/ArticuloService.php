<?php

declare(strict_types=1);

namespace App\Catalogo\Articulos\Services;

use App\Catalogo\Articulos\Dtos\Request\ArticuloRequest;
use App\Catalogo\Articulos\Dtos\Request\PatchArticuloRequest;
use App\Catalogo\Articulos\Dtos\Response\ArticuloResponse;
use App\Catalogo\Articulos\Exceptions\ArticuloNotFoundException;
use App\Catalogo\Articulos\Exceptions\TemaAlreadyEliminatedException;
use App\Catalogo\Articulos\Exceptions\TemaAlreadyInArticuloException;
use App\Catalogo\Articulos\Exceptions\TemaNotFoundException;
use App\Catalogo\Articulos\Mappers\ArticuloMapper;
use App\Catalogo\Articulos\Models\Articulo;
use App\Catalogo\Articulos\Repository\ArticuloRepository;
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
            throw new ArticuloNotFoundException();
        }

        return ArticuloMapper::toArticuloResponse($articulo);
    }

    public function create(ArticuloRequest $request): ArticuloResponse
    {
        $articulo = Articulo::create(
            titulo: $request->getTitulo(),
            anioPublicacion: $request->getAnioPublicacion(),
            tipo: $request->getTipo(),
            idioma: $request->getIdioma(),
            descripcion: $request->getDescripcion()
        );

        $created = $this->repository->insertArticulo($articulo);

        return ArticuloMapper::toArticuloResponse($created);
    }

    public function patchArticulo(int $id, PatchArticuloRequest $request): ArticuloResponse
    {
        $existing = $this->repository->findById($id);

        if ($existing === null) {
            throw new ArticuloNotFoundException();
        }

        $patchable = ['titulo', 'anio_publicacion', 'idioma', 'descripcion'];
        $provided = array_values(array_intersect($request->provided, $patchable));

        if ($request->isProvided('titulo')) {
            $existing->setTitulo($request->titulo);
        }
        if ($request->isProvided('anio_publicacion')) {
            $existing->setAnioPublicacion($request->anioPublicacion);
        }
        if ($request->isProvided('idioma')) {
            $existing->setIdioma($request->idioma);
        }
        if ($request->isProvided('descripcion')) {
            $existing->setDescripcion($request->descripcion);
        }

        $updated = $this->repository->updateArticulo($id, $existing, $provided);

        return ArticuloMapper::toArticuloResponse($updated);
    }

    public function deleteArticulo(int $id): void
    {
        if ($this->repository->findById($id) === null) {
            throw new ArticuloNotFoundException();
        }

        $blockingRelation = $this->repository->getDeleteBlockingRelation($id);
        if ($blockingRelation !== null) {
            throw new BusinessRuleException(
                "No se puede eliminar el artículo porque tiene {$blockingRelation}",
                'id'
            );
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
