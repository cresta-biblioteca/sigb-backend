<?php

declare(strict_types=1);

namespace App\Catalogo\Libros\Dtos\Request;

readonly class CreateLibroRequest
{
    private function __construct(
        // Articulo fields
        public string $titulo,
        public int     $anioPublicacion,
        public int     $tipoDocumentoId,
        public string  $idioma,
        // Libro fields
        public string  $exportMarc,
        public ?string $descripcion,
        public ?string $isbn,
        public ?string $issn,
        public ?int    $paginas,
        public ?string $autor,
        public ?string $autores,
        public ?string $colaboradores,
        public ?string $tituloInformativo,
        public ?int    $cdu,
        public ?string $editorial,
        public ?string $lugarDePublicacion
    )
    {
    }

    public
    static function fromArray(array $articuloData, array $libroData): CreateLibroRequest
    {
        return new self(
            titulo: $articuloData['titulo'],
            anioPublicacion: (int)$articuloData['anio_publicacion'],
            tipoDocumentoId: (int)$articuloData['tipo_documento_id'],
            idioma: $articuloData['idioma'] ?? 'es',
            exportMarc: $libroData['export_marc'],
            descripcion: $articuloData['descripcion'] ?? null,
            isbn: $libroData['isbn'] ?? null,
            issn: $libroData['issn'] ?? null,
            paginas: isset($libroData['paginas']) ? (int)$libroData['paginas'] : null,
            autor: $libroData['autor'] ?? null,
            autores: $libroData['autores'] ?? null,
            colaboradores: $libroData['colaboradores'] ?? null,
            tituloInformativo: $libroData['titulo_informativo'] ?? null,
            cdu: isset($libroData['cdu']) ? (int)$libroData['cdu'] : null,
            editorial: $libroData['editorial'] ?? null,
            lugarDePublicacion: $libroData['lugar_de_publicacion'] ?? null
        );
    }
}
