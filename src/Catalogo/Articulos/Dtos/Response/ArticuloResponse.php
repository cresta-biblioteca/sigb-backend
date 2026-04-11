<?php

declare(strict_types=1);

namespace App\Catalogo\Articulos\Dtos\Response;

use JsonSerializable;
use OpenApi\Attributes as OA;

#[OA\Schema(schema: "ArticuloResponse")]
readonly class ArticuloResponse implements JsonSerializable
{
    public function __construct(
        #[OA\Property(type: "integer", example: 1)]
        private int $id,
        #[OA\Property(type: "string", example: "Algorithms")]
        private string $titulo,
        #[OA\Property(description: "Año de publicación", type: "integer", example: 2011)]
        private int $anioPublicacion,
        #[OA\Property(description: "ID del tipo de documento", type: "integer", example: 1)]
        private int $tipoDocumentoId,
        #[OA\Property(description: "Código de idioma ISO 639-1", type: "string", example: "es")]
        private string $idioma,
        #[OA\Property(type: "string", nullable: true, example: "Introducción completa a algoritmos")]
        private ?string $descripcion = null,
        #[OA\Property(type: "object", nullable: true)]
        private ?array $tipoDocumento = null,
        #[OA\Property(type: "array", items: new OA\Items(type: "object"))]
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
