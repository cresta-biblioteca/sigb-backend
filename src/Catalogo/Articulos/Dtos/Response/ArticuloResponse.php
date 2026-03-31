<?php

declare(strict_types=1);

namespace App\Catalogo\Articulos\Dtos\Response;

use JsonSerializable;

readonly class ArticuloResponse implements JsonSerializable
{
    public function __construct(
        private int $id,
        private string $titulo,
        private int $anioPublicacion,
        private int $tipoDocumentoId,
        private string $idioma,
        private ?string $descripcion = null,
        private ?array $tipoDocumento = null,
        private array $temas = []
    ) {
    }

    /**
     * Getter para ID - necesario para relaciones padre-hijo (ej: crear Libro)
     */
    public function getId(): int
    {
        return $this->id;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'titulo' => $this->titulo,
            'anio_publicacion' => $this->anioPublicacion,
            'tipo_documento_id' => $this->tipoDocumentoId,
            'idioma' => $this->idioma,
            'descripcion' => $this->descripcion,
            'tipo_documento' => $this->tipoDocumento,
            'temas' => $this->temas,
        ];
    }
}
