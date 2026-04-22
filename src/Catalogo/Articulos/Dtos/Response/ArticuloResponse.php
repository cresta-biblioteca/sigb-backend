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
        #[OA\Property(description: "Tipo de artículo según MARC21", type: "string", example: "libro")]
        private string $tipo,
        #[OA\Property(description: "Código de idioma ISO 639-1", type: "string", example: "es")]
        private string $idioma,
        #[OA\Property(type: "string", nullable: true, example: "Introducción completa a algoritmos")]
        private ?string $descripcion = null,
        #[OA\Property(type: "array", items: new OA\Items(type: "object"))]
        private array $temas = []
    ) {
    }

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
            'tipo' => $this->tipo,
            'idioma' => $this->idioma,
            'descripcion' => $this->descripcion,
            'temas' => $this->temas,
        ];
    }
}
