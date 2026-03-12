<?php

declare(strict_types=1);

namespace App\Catalogo\Articulos\Dtos\Request;

use OpenApi\Attributes as OA;

#[OA\Schema(schema: "MateriaRequest", required: ["titulo"])]
readonly class MateriaRequest
{
    #[OA\Property(description: "Titulo de la materia", type: "string", example: "Matematica")]
    public string $titulo;

    public function __construct(
        string $titulo
    ) {
        $this->titulo = $titulo;
    }
}
