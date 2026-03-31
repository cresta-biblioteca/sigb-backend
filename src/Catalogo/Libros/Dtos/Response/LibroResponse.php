<?php

declare(strict_types=1);

namespace App\Catalogo\Libros\Dtos\Response;

use JsonSerializable;

readonly class LibroResponse implements JsonSerializable
{
    public function __construct(
        private int $id,
        private ?string $isbn,
        private ?string $issn,
        private ?int $paginas,
        private ?string $tituloInformativo,
        private ?int $cdu,
        private ?string $editorial,
        private ?string $lugarDePublicacion,
        private ?string $edicion,
        private ?string $dimensiones,
        private ?string $ilustraciones,
        private ?string $serie,
        private ?string $numeroSerie,
        private ?string $notas,
        private ?string $paisPublicacion,
        /** @var array<int, array{nombre: string, apellido: string, rol: string, orden: int}> */
        private array $personas = [],
        // Información del artículo asociado
        private ?string $titulo = null,
        private ?int $anioPublicacion = null,
        private ?int $tipoDocumentoId = null,
        private ?string $idioma = null,
        private ?string $descripcion = null,
        /** @var array<int, array{id: int, titulo: string}> */
        private array $temas = [],
    ) {
    }

    public function jsonSerialize(): array
    {
        $data = [
            'id' => $this->id,
            'isbn' => $this->isbn,
            'issn' => $this->issn,
            'paginas' => $this->paginas,
            'titulo_informativo' => $this->tituloInformativo,
            'cdu' => $this->cdu,
            'editorial' => $this->editorial,
            'lugar_de_publicacion' => $this->lugarDePublicacion,
            'edicion' => $this->edicion,
            'dimensiones' => $this->dimensiones,
            'ilustraciones' => $this->ilustraciones,
            'serie' => $this->serie,
            'numero_serie' => $this->numeroSerie,
            'notas' => $this->notas,
            'pais_publicacion' => $this->paisPublicacion,
            'personas' => $this->personas,
        ];

        // Agregar información del artículo si está disponible
        if ($this->titulo !== null) {
            $data['articulo'] = [
                'titulo' => $this->titulo,
                'anio_publicacion' => $this->anioPublicacion,
                'tipo_documento_id' => $this->tipoDocumentoId,
                'idioma' => $this->idioma,
                'descripcion' => $this->descripcion,
                'temas' => $this->temas,
            ];
        }

        return $data;
    }
}
