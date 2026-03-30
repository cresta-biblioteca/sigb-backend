<?php

declare(strict_types=1);

namespace App\Lectores\Dtos\Request;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "CreateCarreraRequest",
    required: ["cod", "nombre"]
)]
readonly class CreateCarreraRequest
{
    #[OA\Property(description: "Codigo de la carrera", type: "string", example: "AN")]
    public string $cod;
    #[OA\Property(description: "Nombre de la carrera", type: "string", example: "Analista Programador")]
    public string $nombre;

    public function __construct(string $cod, string $nombre)
    {
        $this->cod = $cod;
        $this->nombre = $nombre;
    }
}
