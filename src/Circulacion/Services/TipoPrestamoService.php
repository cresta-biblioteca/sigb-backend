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
use App\Circulacion\Repositories\TipoPrestamoRepository;

class TipoPrestamoService
{
    private const FIELD_MAPPING = [
        'codigo' => ['setCodigo', 'getCodigo'],
        'descripcion' => ['setDescripcion', 'getDescripcion'],
        'maxCantidadPrestamos' => ['setMaxCantidadPrestamos', 'getMaxCantidadPrestamos'],
        'duracionPrestamo' => ['setDuracionPrestamo', 'getDuracionPrestamo'],
        'renovaciones' => ['setRenovaciones', 'getRenovaciones'],
        'diasRenovacion' => ['setDiasRenovacion', 'getDiasRenovacion'],
        'cantDiasRenovar' => ['setCantDiasRenovar', 'getCantDiasRenovar'],
    ];

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
            throw new TipoPrestamoNotFoundException($id);
        }
        return TipoPrestamoMapper::toResponse($tipo);
    }

    public function createTipoPrestamo(CreateTipoPrestamoRequest $request): TipoPrestamoResponse
    {
        $tipo = TipoPrestamoMapper::fromRequest($request);
        $coincidence = $this->repo->findCoincidence($tipo->getCodigo(), $tipo->getDescripcion());
        if ($coincidence) {
            if ($coincidence->getCodigo() === $tipo->getCodigo()) {
                throw new TipoPrestamoAlreadyExistsException("codigo", $tipo->getCodigo());
            }
            throw new TipoPrestamoAlreadyExistsException("descripcion", $tipo->getDescripcion());
        }

        $tipoCreated = $this->repo->insertTipoPrestamo($tipo);
        return TipoPrestamoMapper::toResponse($tipoCreated);
    }

    public function updateTipoPrestamo(int $id, UpdateTipoPrestamoRequest $request): TipoPrestamoResponse
    {
        $tipoExistente = $this->repo->findById($id);
        if (!$tipoExistente) {
            throw new TipoPrestamoNotFoundException($id);
        }
        foreach (self::FIELD_MAPPING as $property => [$setter, $getter]) {
            if ($request->$property === null) {
                $request->$setter($tipoExistente->$getter());
            }
        }
        $coincidence = $this->repo->findCoincidence($request->codigo, $request->descripcion);
        if ($coincidence && $coincidence->getId() !== $id) {
            if ($coincidence->getCodigo() === strtoupper($request->codigo)) {
                throw new TipoPrestamoAlreadyExistsException("codigo", $request->codigo);
            }
            throw new TipoPrestamoAlreadyExistsException("descripcion", $request->descripcion);
        }
        $tipoPrestamo = TipoPrestamoMapper::fromRequest($request);

        $tipoUpdated = $this->repo->updateTipoPrestamo($id, $tipoPrestamo);

        return TipoPrestamoMapper::toResponse($tipoUpdated);
    }

    public function disableTipoPrestamo(int $id): void
    {
        /** @var TipoPrestamo $tipoExistente */
        $tipoExistente = $this->repo->findById($id);
        if (!$tipoExistente) {
            throw new TipoPrestamoNotFoundException($id);
        }
        if ($tipoExistente->isHabilitado() === false) {
            throw new TipoPrestamoAlreadyDisabledException(
                "tipoPrestamo",
                "El tipo de prestamo ya se encuentra deshabilitado"
            );
        }
        $this->repo->disableTipoPrestamo($id);
    }

    public function enableTipoPrestamo(int $id): void
    {
        /** @var TipoPrestamo $tipoExistente */
        $tipoExistente = $this->repo->findById($id);
        if (!$tipoExistente) {
            throw new TipoPrestamoNotFoundException($id);
        }
        if ($tipoExistente->isHabilitado() === true) {
            throw new TipoPrestamoAlreadyEnabledException(
                "tipoPrestamo",
                "El tipo de prestamo ya se encuentra habilitado"
            );
        }
        $this->repo->enableTipoPrestamo($id);
    }
}
