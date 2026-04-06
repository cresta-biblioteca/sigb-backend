<?php

declare(strict_types=1);

namespace App\Circulacion\Services;

use App\Circulacion\Dtos\Request\CreateTipoPrestamoRequest;
use App\Circulacion\Dtos\Request\UpdateTipoPrestamoRequest;
use App\Circulacion\Dtos\Response\TipoPrestamoResponse;
use App\Circulacion\Exceptions\TipoPrestamoAlreadyDisabledException;
use App\Circulacion\Exceptions\TipoPrestamoAlreadyEnabledException;
use App\Circulacion\Exceptions\TipoPrestamoAlreadyExistsException;
use App\Circulacion\Exceptions\TipoPrestamoNotFoundException;
use App\Circulacion\Mappers\TipoPrestamoMapper;
use App\Circulacion\Models\TipoPrestamo;
use App\Circulacion\Repositories\TipoPrestamoRepository;

class TipoPrestamoService
{
    public function __construct(private TipoPrestamoRepository $repo)
    {
    }

    public function getAll(): array
    {
        $tipos = $this->repo->findAll();
        $response = array_map(fn($tipo) => TipoPrestamoMapper::toResponse($tipo), $tipos);
        return $response;
    }

    public function getById(int $id): TipoPrestamoResponse
    {
        $tipo = $this->repo->findById($id);
        if (!$tipo) {
            throw new TipoPrestamoNotFoundException();
        }
        return TipoPrestamoMapper::toResponse($tipo);
    }

    public function createTipoPrestamo(CreateTipoPrestamoRequest $request): TipoPrestamoResponse
    {
        $tipo = TipoPrestamoMapper::fromRequest($request);
        $coincidence = $this->repo->findCoincidence($tipo->getCodigo(), $tipo->getDescripcion());
        if ($coincidence) {
            if ($coincidence->getCodigo() === $tipo->getCodigo()) {
                throw new TipoPrestamoAlreadyExistsException();
            }
            throw new TipoPrestamoAlreadyExistsException();
        }

        $tipoCreated = $this->repo->insertTipoPrestamo($tipo);
        return TipoPrestamoMapper::toResponse($tipoCreated);
    }

    public function updateTipoPrestamo(int $id, UpdateTipoPrestamoRequest $request): TipoPrestamoResponse
    {
        /** @var TipoPrestamo $tipoExistente */
        $tipoExistente = $this->repo->findById($id);
        if (!$tipoExistente) {
            throw new TipoPrestamoNotFoundException();
        }
        if ($request->codigo !== null) {
            $tipoExistente->setCodigo($request->codigo);
        }
        if ($request->descripcion !== null) {
            $tipoExistente->setDescripcion($request->descripcion);
        }
        if ($request->maxCantidadPrestamos !== null) {
            $tipoExistente->setMaxCantidadPrestamos($request->maxCantidadPrestamos);
        }
        if ($request->duracionPrestamo !== null) {
            $tipoExistente->setDuracionPrestamo($request->duracionPrestamo);
        }
        if ($request->renovaciones !== null) {
            $tipoExistente->setRenovaciones($request->renovaciones);
        }
        if ($request->diasRenovacion !== null) {
            $tipoExistente->setDiasRenovacion($request->diasRenovacion);
        }
        if ($request->cantDiasRenovar !== null) {
            $tipoExistente->setCantDiasRenovar($request->cantDiasRenovar);
        }

        $coincidence = $this->repo->findCoincidence($tipoExistente->getCodigo(), $tipoExistente->getDescripcion());
        if ($coincidence && $coincidence->getId() !== $id) {
            if ($coincidence->getCodigo() === $tipoExistente->getCodigo()) {
                throw new TipoPrestamoAlreadyExistsException();
            }
            throw new TipoPrestamoAlreadyExistsException();
        }

        $tipoUpdated = $this->repo->updateTipoPrestamo($id, $tipoExistente);

        return TipoPrestamoMapper::toResponse($tipoUpdated);
    }

    public function disableTipoPrestamo(int $id): void
    {
        /** @var TipoPrestamo $tipoExistente */
        $tipoExistente = $this->repo->findById($id);
        if (!$tipoExistente) {
            throw new TipoPrestamoNotFoundException();
        }
        if ($tipoExistente->isHabilitado() === false) {
            throw new TipoPrestamoAlreadyDisabledException();
        }
        $this->repo->disableTipoPrestamo($id);
    }

    public function enableTipoPrestamo(int $id): void
    {
        /** @var TipoPrestamo $tipoExistente */
        $tipoExistente = $this->repo->findById($id);
        if (!$tipoExistente) {
            throw new TipoPrestamoNotFoundException();
        }
        if ($tipoExistente->isHabilitado() === true) {
            throw new TipoPrestamoAlreadyEnabledException();
        }
        $this->repo->enableTipoPrestamo($id);
    }
}
