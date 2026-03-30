<?php

declare(strict_types=1);

namespace App\Lectores\Dtos\Request;

use OpenApi\Attributes as OA;

#[OA\Schema(schema: "UpdateCarreraRequest")]
class UpdateCarreraRequest
{
    #[OA\Property(description: "Codigo de la carrera", type: "string", example: "AG", nullable: true)]
    public ?string $cod;
    #[OA\Property(description: "Nombre de la carrera", type: "string", example: "Agronomia", nullable: true)]
    public ?string $nombre;

    public function __construct(?string $cod = null, ?string $nombre = null)
    {
        $this->cod = $cod;
        $this->nombre = $nombre;
    }

    public function setCod(string $cod): void
    {
        $this->cod = $cod;
    }

    public function setNombre(string $nombre): void
    {
        $this->nombre = $nombre;
    }
}
