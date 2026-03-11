<?php

declare(strict_types=1);

namespace App\Catalogo\Articulos\Mappers;

use App\Catalogo\Articulos\Dtos\Request\TemaRequest;
use App\Catalogo\Articulos\Dtos\Response\TemaResponse;
use App\Catalogo\Articulos\Models\Tema;

class TemaMapper
{
    public static function fromArray(array $input): TemaRequest
    {
        return new TemaRequest(
            titulo: trim($input["titulo"])
        );
    }

    public static function fromTemaRequest(TemaRequest $tema): Tema
    {
        return Tema::create(
            titulo: $tema->titulo
        );
    }

    public static function toTemaResponse(Tema $tema): TemaResponse
    {
        return new TemaResponse(
            id: $tema->getId(),
            titulo: $tema->getTitulo()
        );
    }
}
