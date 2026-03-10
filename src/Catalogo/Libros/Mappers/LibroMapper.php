<?php

declare(strict_types=1);

namespace App\Catalogo\Libros\Mappers;

use App\Catalogo\Libros\Dtos\Response\LibroResponse;
use App\Catalogo\Libros\Models\Libro;

class LibroMapper
{
    public static function toLibroResponse(Libro $libro): LibroResponse
    {
        $articulo = $libro->getArticulo();

        return new LibroResponse(
            id: $libro->getId() ?? 0,
            isbn: $libro->getIsbn(),
            autor: $libro->getAutor(),
            autores: $libro->getAutores(),
            colaboradores: $libro->getColaboradores(),
            tituloInformativo: $libro->getTituloInformativo(),
            cdu: $libro->getCdu(),
            exportMarc: $libro->getExportMarc(),
            // Información del artículo (si está disponible)
            titulo: $articulo?->getTitulo(),
            anioPublicacion: $articulo?->getAnioPublicacion(),
            tipoDocumentoId: $articulo?->getTipoDocumentoId(),
            idioma: $articulo?->getIdioma()
        );
    }
}
