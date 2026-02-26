<?php

declare(strict_types=1);

namespace App\Lectores\Dtos\Response;

use JsonSerializable;

readonly class CarreraResponse implements JsonSerializable
{
    public function __construct(
        private int $id,
        private string $cod,
        private string $nombre
    ) {
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'codigo' => $this->cod,
            'nombre' => $this->nombre,
        ];
    }
}
