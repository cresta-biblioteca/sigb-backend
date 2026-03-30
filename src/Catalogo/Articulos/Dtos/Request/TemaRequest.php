<?php

declare(strict_types=1);

namespace App\Catalogo\Articulos\Dtos\Request;

use OpenApi\Attributes as OA;

#[OA\Schema(schema: "TemaRequest", required: ["titulo"])]
readonly class TemaRequest
{
    #[OA\Property(description: "Nombre del tema", type: "string", example: "Abejas")]
    public string $titulo;

    public function __construct(
        string $titulo
    ) {
        $this->titulo = $titulo;
    }
}
