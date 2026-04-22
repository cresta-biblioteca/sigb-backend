<?php

declare(strict_types=1);

namespace App\Catalogo\Articulos\Mappers;

use App\Catalogo\Articulos\Dtos\Response\ArticuloResponse;
use App\Catalogo\Articulos\Models\Articulo;

class ArticuloMapper
{
    public static function toArticuloResponse(Articulo $articulo): ArticuloResponse
    {
        return new ArticuloResponse(
            id: $articulo->getId() ?? 0,
            titulo: $articulo->getTitulo(),
            anioPublicacion: $articulo->getAnioPublicacion(),
            tipo: $articulo->getTipo(),
            idioma: $articulo->getIdioma(),
            descripcion: $articulo->getDescripcion(),
            temas: array_map(
                fn($tema) => $tema->toArray(),
                $articulo->getTemas()
            )
        );
    }
}
