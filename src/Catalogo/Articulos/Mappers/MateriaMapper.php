<?php

namespace App\Catalogo\Articulos\Mappers;

use App\Catalogo\Articulos\Dtos\Request\MateriaRequest;
use App\Catalogo\Articulos\Dtos\Response\MateriaResponse;
use App\Catalogo\Articulos\Models\Materia;
use App\Shared\Exceptions\ValidationException;

class MateriaMapper
{
    public static function fromArray(array $input): MateriaRequest
    {
        if (!isset($input["titulo"])) {
            throw ValidationException::forField("titulo", "El campo titulo es obligatorio");
        }

        return new MateriaRequest(
            titulo: trim($input["titulo"])
        );
    }
    public static function fromMateriaRequest(MateriaRequest $materia): Materia
    {
        return Materia::create(
            titulo: $materia->titulo
        );
    }

    public static function toMateriaResponse(Materia $materia): MateriaResponse
    {
        return new MateriaResponse(
            id: $materia->getId(),
            titulo: $materia->getTitulo()
        );
    }
}
