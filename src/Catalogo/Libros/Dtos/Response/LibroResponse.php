<?php

declare(strict_types=1);

namespace App\Catalogo\Libros\Dtos\Response;

use JsonSerializable;

readonly class LibroResponse implements JsonSerializable
{
    public function __construct(
        private int $id,
        private string $isbn,
        private ?string $autor,
        private ?string $autores,
        private ?string $colaboradores,
        private ?string $tituloInformativo,
        private ?int $cdu,
        private string $exportMarc,
        // Información del artículo asociado
        private ?string $titulo = null,
        private ?int $anioPublicacion = null,
        private ?int $tipoDocumentoId = null,
        private ?string $idioma = null
    ) {
    }

    public function jsonSerialize(): array
    {
        $data = [
            'id' => $this->id,
            'isbn' => $this->isbn,
            'autor' => $this->autor,
            'autores' => $this->autores,
            'colaboradores' => $this->colaboradores,
            'titulo_informativo' => $this->tituloInformativo,
            'cdu' => $this->cdu,
            'export_marc' => $this->exportMarc,
        ];

        // Agregar información del artículo si está disponible
        if ($this->titulo !== null) {
            $data['articulo'] = [
                'titulo' => $this->titulo,
                'anio_publicacion' => $this->anioPublicacion,
                'tipo_documento_id' => $this->tipoDocumentoId,
                'idioma' => $this->idioma,
            ];
        }

        return $data;
    }
}
