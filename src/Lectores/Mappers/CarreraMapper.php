<?php

declare(strict_types=1);

namespace App\Lectores\Mappers;

use App\Lectores\Dtos\Request\CarreraRequest;
use App\Lectores\Dtos\Request\CreateCarreraRequest;
use App\Lectores\Dtos\Request\UpdateCarreraRequest;
use App\Lectores\Dtos\Response\CarreraResponse;
use App\Lectores\Models\Carrera;

class CarreraMapper
{
    public static function fromArrayToCreate(array $input): CreateCarreraRequest
    {
        return new CreateCarreraRequest(
            trim($input["cod"]),
            trim($input["nombre"])
        );
    }
    public static function fromArrayToUpdate(array $input): UpdateCarreraRequest
    {
        return new UpdateCarreraRequest(
            isset($input["cod"]) ? trim($input["cod"]) : null,
            isset($input["nombre"]) ? trim($input["nombre"]) : null
        );
    }

    public static function fromCarreraRequest(CreateCarreraRequest|UpdateCarreraRequest $request): Carrera
    {
        return Carrera::create(
            $request->cod,
            $request->nombre
        );
    }

    public static function toCarreraResponse(Carrera $carrera): CarreraResponse
    {
        return new CarreraResponse(
            $carrera->getId(),
            $carrera->getCodigo(),
            $carrera->getNombre()
        );
    }
}
