<?php

declare(strict_types=1);

namespace App\Catalogo\Libros\Mappers;

use App\Catalogo\Libros\Dtos\Request\LibroRequest;
use App\Catalogo\Libros\Dtos\Response\LibroResponse;
use App\Catalogo\Libros\Models\Libro;

class LibroMapper
{
    public static function requestToEntity(LibroRequest $request): Libro
    {
        return Libro::create(
            $request->articuloId,
            $request->isbn,
            $request->exportMarc,
            $request->autor,
            $request->autores,
            $request->colaboradores,
            $request->tituloInformativo,
            $request->cdu
        );
    }

    public static function updateFromRequest(Libro $libro, LibroRequest $request): Libro
    {
        $libro->setArticuloId($request->articuloId);
        $libro->setIsbn($request->isbn);
        $libro->setExportMarc($request->exportMarc);
        $libro->setAutor($request->autor);
        $libro->setAutores($request->autores);
        $libro->setColaboradores($request->colaboradores);
        $libro->setTituloInformativo($request->tituloInformativo);
        $libro->setCdu($request->cdu);

        return $libro;
    }

    public static function toResponse(Libro $libro): LibroResponse
    {
        return new LibroResponse(
            articuloId: $libro->getArticuloId(),
            isbn: $libro->getIsbn(),
            autor: $libro->getAutor(),
            autores: $libro->getAutores(),
            colaboradores: $libro->getColaboradores(),
            tituloInformativo: $libro->getTituloInformativo(),
            cdu: $libro->getCdu(),
            exportMarc: $libro->getExportMarc(),
            createdAt: $libro->getCreatedAt() ?? new \DateTimeImmutable(),
            updatedAt: $libro->getUpdatedAt() ?? new \DateTimeImmutable()
        );
    }
}
