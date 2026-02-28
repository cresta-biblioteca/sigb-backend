<?php

declare(strict_types=1);

namespace App\Catalogo\Libros\Mappers;

use App\Catalogo\Libros\Dtos\Response\LibroResponse;
use App\Catalogo\Libros\Models\Libro;

class LibroMapper
{
    public static function toLibroResponse(Libro $libro): LibroResponse
    {
        return new LibroResponse(
            id: $libro->getId() ?? 0,
            articuloId: $libro->getArticuloId(),
            isbn: $libro->getIsbn(),
            autor: $libro->getAutor(),
            autores: $libro->getAutores(),
            colaboradores: $libro->getColaboradores(),
            tituloInformativo: $libro->getTituloInformativo(),
            cdu: $libro->getCdu(),
            exportMarc: $libro->getExportMarc(),
            articulo: $libro->getArticulo()?->toArray()
        );
    }
}
