<?php

declare(strict_types=1);

namespace App\Catalogo\Articulos\Mappers;

use App\Catalogo\Articulos\Dtos\Request\MateriaRequest;
use App\Catalogo\Articulos\Dtos\Response\MateriaResponse;
use App\Catalogo\Articulos\Models\Materia;

class MateriaMapper
{
    public static function fromArray(array $input): MateriaRequest
    {
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
