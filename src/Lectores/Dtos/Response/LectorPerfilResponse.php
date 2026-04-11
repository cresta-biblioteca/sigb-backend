<?php

declare(strict_types=1);

namespace App\Lectores\Dtos\Response;

use JsonSerializable;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "LectorPerfilResponse",
    required: ["nombre", "apellido", "tarjeta", "dni", "telefono", "email"]
)]
readonly class LectorPerfilResponse implements JsonSerializable
{
    public function __construct(
        #[OA\Property(type: "string", example: "Juan")]
        private string $nombre,
        #[OA\Property(type: "string", example: "Pérez")]
        private string $apellido,
        #[OA\Property(type: "string", example: "123456")]
        private string $tarjeta,
        #[OA\Property(type: "string", example: "12345678")]
        private string $dni,
        #[OA\Property(type: "string", nullable: true, example: "K1234")]
        private ?string $legajo,
        #[OA\Property(type: "string", example: "2616123456")]
        private string $telefono,
        #[OA\Property(type: "string", example: "juan@example.com")]
        private string $email,
    ) {
    }

    public function jsonSerialize(): array
    {
        return [
            'nombre' => $this->nombre,
            'apellido' => $this->apellido,
            'tarjeta' => $this->tarjeta,
            'dni' => $this->dni,
            'legajo' => $this->legajo,
            'telefono' => $this->telefono,
            'email' => $this->email,
        ];
    }
}
