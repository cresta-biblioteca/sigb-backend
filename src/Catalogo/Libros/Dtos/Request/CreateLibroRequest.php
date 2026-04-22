<?php

declare(strict_types=1);

namespace App\Catalogo\Libros\Dtos\Request;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "CreateLibroRequest",
    required: ["articulo", "libro"]
)]
readonly class CreateLibroRequest
{
    private function __construct(
        // Articulo fields
        public string $titulo,
        public int $anioPublicacion,
        public string $tipo,
        public string $idioma,
        // Libro fields
        public ?string $descripcion,
        public ?string $isbn,
        public ?string $issn,
        public ?int $paginas,
        public ?string $tituloInformativo,
        public ?int $cdu,
        public ?string $editorial,
        public ?string $lugarDePublicacion,
        public ?string $edicion,
        public ?string $dimensiones,
        public ?string $ilustraciones,
        public ?string $serie,
        public ?string $numeroSerie,
        public ?string $notas,
        public ?string $paisPublicacion,
        /** @var array<int, array{nombre: string, apellido: string, rol: string}> */
        public array $personas,
    ) {
    }

    public static function fromArray(array $articuloData, array $libroData): CreateLibroRequest
    {
        return new self(
            titulo: $articuloData['titulo'],
            anioPublicacion: (int)$articuloData['anio_publicacion'],
            tipo: (string)$articuloData['tipo'],
            idioma: $articuloData['idioma'] ?? 'es',
            descripcion: $articuloData['descripcion'] ?? null,
            isbn: $libroData['isbn'] ?? null,
            issn: $libroData['issn'] ?? null,
            paginas: isset($libroData['paginas']) ? (int)$libroData['paginas'] : null,
            tituloInformativo: $libroData['titulo_informativo'] ?? null,
            cdu: isset($libroData['cdu']) ? (int)$libroData['cdu'] : null,
            editorial: $libroData['editorial'] ?? null,
            lugarDePublicacion: $libroData['lugar_de_publicacion'] ?? null,
            edicion: $libroData['edicion'] ?? null,
            dimensiones: $libroData['dimensiones'] ?? null,
            ilustraciones: $libroData['ilustraciones'] ?? null,
            serie: $libroData['serie'] ?? null,
            numeroSerie: $libroData['numero_serie'] ?? null,
            notas: $libroData['notas'] ?? null,
            paisPublicacion: $libroData['pais_publicacion'] ?? null,
            personas: $libroData['personas'] ?? [],
        );
    }
}
