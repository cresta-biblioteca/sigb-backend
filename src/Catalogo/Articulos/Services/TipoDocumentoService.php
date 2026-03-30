<?php

declare(strict_types=1);

namespace App\Catalogo\Articulos\Services;

use App\Catalogo\Articulos\Dtos\Request\CreateTipoDocumentoRequest;
use App\Catalogo\Articulos\Dtos\Request\UpdateTipoDocumentoRequest;
use App\Catalogo\Articulos\Dtos\Response\TipoDocumentoResponse;
use App\Catalogo\Articulos\Exceptions\TipoDocumentoAlreadyExistsException;
use App\Catalogo\Articulos\Exceptions\TipoDocumentoNotFoundException;
use App\Catalogo\Articulos\Mappers\TipoDocumentoMapper;
use App\Catalogo\Articulos\Models\TipoDocumento;
use App\Catalogo\Articulos\Repository\TipoDocumentoRepository;

class TipoDocumentoService
{
    public function __construct(private TipoDocumentoRepository $repo)
    {
    }

    public function getAll(): array
    {
        $tipoDocumentos = $this->repo->findAll();
        $response = array_map(fn($tipoDoc) => TipoDocumentoMapper::toResponse($tipoDoc), $tipoDocumentos);
        return $response;
    }

    public function getById(int $id): TipoDocumentoResponse
    {
        $tipoDocumentoExistente = $this->repo->findById($id);
        if (!$tipoDocumentoExistente) {
            throw new TipoDocumentoNotFoundException();
        }

        return TipoDocumentoMapper::toResponse($tipoDocumentoExistente);
    }

    public function getByParams(array $params): array
    {
        $tipoDocs = $this->repo->findByParams($params);
        $response = array_map(
            fn($tipoDoc) => TipoDocumentoMapper::toResponse($tipoDoc),
            $tipoDocs
        );
        return $response;
    }

    public function createTipoDocumento(CreateTipoDocumentoRequest $request): TipoDocumentoResponse
    {
        $tipoDoc = TipoDocumentoMapper::fromRequest($request);
        $tipoDocExistente = $this->repo->findCoincidence($tipoDoc->getCodigo(), $tipoDoc->getDescripcion());
        if ($tipoDocExistente) {
            if ($tipoDocExistente->getCodigo() === $tipoDoc->getCodigo()) {
                throw new TipoDocumentoAlreadyExistsException();
            }
            throw new TipoDocumentoAlreadyExistsException();
        }

        $docCreado = $this->repo->insertTipoDocumento($tipoDoc);

        return TipoDocumentoMapper::toResponse($docCreado);
    }

    public function updateTipoDocumento(int $id, UpdateTipoDocumentoRequest $request): TipoDocumentoResponse
    {

        /** @var TipoDocumento $tipoDocExiste */
        $tipoDocExiste = $this->repo->findById($id);
        if (!$tipoDocExiste) {
            throw new TipoDocumentoNotFoundException();
        }

        if ($request->codigo === null) {
            $request->setCodigo($tipoDocExiste->getCodigo());
        }
        if ($request->descripcion === null) {
            $request->setDescripcion($tipoDocExiste->getDescripcion());
        }
        if ($request->renovable === null) {
            $request->setRenovable($tipoDocExiste->isRenovable());
        }
        if ($request->detalle === null) {
            $request->setDetalle($tipoDocExiste->getDetalle());
        }

        $tipoDoc = TipoDocumentoMapper::fromRequest($request);

        $coincidencia = $this->repo->findCoincidence($request->codigo, $request->descripcion, $id);
        if ($coincidencia) {
            if ($coincidencia->getCodigo() === strtoupper($request->codigo)) {
                throw new TipoDocumentoAlreadyExistsException();
            }
            throw new TipoDocumentoAlreadyExistsException();
        }

        $tipoDocActualizado = $this->repo->updateTipoDocumento($id, $tipoDoc);

        return TipoDocumentoMapper::toResponse($tipoDocActualizado);
    }

    public function deleteTipoDocumento(int $id): void
    {
        $tipoDocExistente = $this->repo->findById($id);
        if (!$tipoDocExistente) {
            throw new TipoDocumentoNotFoundException();
        }

        $borrado = $this->repo->delete($id);
        if (!$borrado) {
            throw new TipoDocumentoNotFoundException();
        }
    }
}
