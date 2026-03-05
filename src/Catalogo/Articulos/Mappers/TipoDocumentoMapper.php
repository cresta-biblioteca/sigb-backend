<?php

declare(strict_types=1);

namespace App\Catalogo\Articulos\Mappers;

use App\Catalogo\Articulos\Dtos\Request\CreateTipoDocumentoRequest;
use App\Catalogo\Articulos\Dtos\Request\UpdateTipoDocumentoRequest;
use App\Catalogo\Articulos\Dtos\Response\TipoDocumentoResponse;
use App\Catalogo\Articulos\Models\TipoDocumento;

class TipoDocumentoMapper
{
    public static function fromArrayToCreate(array $input): CreateTipoDocumentoRequest
    {
        return new CreateTipoDocumentoRequest(
            trim($input["codigo"]),
            trim($input["descripcion"]),
            $input["renovable"] ?? true,
            $input["detalle"] ?? null,
        );
    }

    public static function fromArrayToUpdate(array $input): UpdateTipoDocumentoRequest
    {
        return new UpdateTipoDocumentoRequest(
            isset($input["codigo"]) ? trim($input["codigo"]) : null,
            isset($input["descripcion"]) ? trim($input["descripcion"]) : null,
            $input["renovable"] ?? null,
            isset($input["detalle"]) ? trim($input["detalle"]) : null,
        );
    }

    public static function fromRequest(CreateTipoDocumentoRequest|UpdateTipoDocumentoRequest $request): TipoDocumento
    {
        return TipoDocumento::create(
            $request->codigo,
            $request->descripcion,
            $request->renovable,
            $request->detalle,
        );
    }

    public static function toResponse(TipoDocumento $tipoDocumento): TipoDocumentoResponse
    {
        return new TipoDocumentoResponse(
            $tipoDocumento->getId(),
            $tipoDocumento->getCodigo(),
            $tipoDocumento->getDescripcion(),
            $tipoDocumento->isRenovable(),
            $tipoDocumento->getDetalle(),
        );
    }
}