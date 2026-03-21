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
        private ?string $autor,
        private ?string $autores,
        private ?string $colaboradores,
        private ?string $tituloInformativo,
        private ?int $cdu,
        private string $exportMarc,
        private ?string $editorial,
        private ?string $lugarDePublicacion,
        // Información del artículo asociado
        private ?string $titulo = null,
        private ?int $anioPublicacion = null,
        private ?int $tipoDocumentoId = null,
        private ?string $idioma = null,
        private ?string $descripcion = null
    ) {
    }

    public function jsonSerialize(): array
    {
        $data = [
            'id' => $this->id,
            'isbn' => $this->isbn,
            'issn' => $this->issn,
            'paginas' => $this->paginas,
            'autor' => $this->autor,
            'autores' => $this->autores,
            'colaboradores' => $this->colaboradores,
            'titulo_informativo' => $this->tituloInformativo,
            'cdu' => $this->cdu,
            'export_marc' => $this->exportMarc,
            'editorial' => $this->editorial,
            'lugar_de_publicacion' => $this->lugarDePublicacion,
        ];

        // Agregar información del artículo si está disponible
        if ($this->titulo !== null) {
            $data['articulo'] = [
                'titulo' => $this->titulo,
                'anio_publicacion' => $this->anioPublicacion,
                'tipo_documento_id' => $this->tipoDocumentoId,
                'idioma' => $this->idioma,
                'descripcion' => $this->descripcion,
            ];
        }

        return $data;
    }
}
