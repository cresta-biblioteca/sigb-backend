<?php

declare(strict_types=1);

namespace App\Circulacion\Mappers;

use App\Circulacion\Dtos\Request\CreateTipoPrestamoRequest;
use App\Circulacion\Dtos\Request\UpdateTipoPrestamoRequest;
use App\Circulacion\Dtos\Response\TipoPrestamoResponse;
use App\Circulacion\Models\TipoPrestamo;

class TipoPrestamoMapper
{
    public static function fromArrayToCreate(array $input): CreateTipoPrestamoRequest
    {
        return new CreateTipoPrestamoRequest(
            $input['codigo'],
            $input['descripcion'],
            $input['max_cantidad_prestamos'],
            $input['duracion'],
            $input['renovaciones'],
            $input['dias_renovacion'],
            $input['cant_dias_renovar']
        );
    }

    public static function fromArrayToUpdate(array $input): UpdateTipoPrestamoRequest
    {
        return new UpdateTipoPrestamoRequest(
            $input['codigo'] ?? null,
            $input['descripcion'] ?? null,
            $input['max_cantidad_prestamos'] ?? null,
            $input['duracion'] ?? null,
            $input['renovaciones'] ?? null,
            $input['dias_renovacion'] ?? null,
            $input['cant_dias_renovar'] ?? null
        );
    }
    public static function fromRequest(CreateTipoPrestamoRequest|UpdateTipoPrestamoRequest $request): TipoPrestamo
    {
        return TipoPrestamo::create(
            $request->codigo,
            $request->maxCantidadPrestamos,
            $request->duracionPrestamo,
            $request->renovaciones,
            $request->diasRenovacion,
            $request->cantDiasRenovar,
            $request->descripcion
        );
    }
    public static function toResponse(TipoPrestamo $tipoPrestamo): TipoPrestamoResponse
    {
        return new TipoPrestamoResponse(
            $tipoPrestamo->getId(),
            $tipoPrestamo->getCodigo(),
            $tipoPrestamo->getDescripcion(),
            $tipoPrestamo->getMaxCantidadPrestamos(),
            $tipoPrestamo->getDuracionPrestamo(),
            $tipoPrestamo->getRenovaciones(),
            $tipoPrestamo->getDiasRenovacion(),
            $tipoPrestamo->getCantDiasRenovar(),
            $tipoPrestamo->isHabilitado()
        );
    }
}
