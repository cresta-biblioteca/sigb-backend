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

        $personas = array_map(fn($lp) => [
            'nombre' => $lp->persona->getNombre(),
            'apellido' => $lp->persona->getApellido(),
            'rol' => $lp->rol,
            'orden' => $lp->orden,
        ], $libro->getPersonas());

        $temas = [];

        if ($articulo !== null) {
            $temas = array_map(fn($t) => [
                'id' => $t->getId(),
                'titulo' => $t->getTitulo(),
            ], $articulo->getTemas());
        }

        return new LibroResponse(
            id: $libro->getId() ?? 0,
            isbn: $libro->getIsbn(),
            issn: $libro->getIssn(),
            paginas: $libro->getPaginas(),
            tituloInformativo: $libro->getTituloInformativo(),
            cdu: $libro->getCdu(),
            editorial: $libro->getEditorial(),
            lugarDePublicacion: $libro->getLugarDePublicacion(),
            edicion: $libro->getEdicion(),
            dimensiones: $libro->getDimensiones(),
            ilustraciones: $libro->getIlustraciones(),
            serie: $libro->getSerie(),
            numeroSerie: $libro->getNumeroSerie(),
            notas: $libro->getNotas(),
            paisPublicacion: $libro->getPaisPublicacion(),
            personas: $personas,
            // Información del artículo (si está disponible)
            titulo: $articulo?->getTitulo(),
            anioPublicacion: $articulo?->getAnioPublicacion(),
            tipo: $articulo?->getTipo(),
            idioma: $articulo?->getIdioma(),
            descripcion: $articulo?->getDescripcion(),
            temas: $temas,
        );
    }
}
