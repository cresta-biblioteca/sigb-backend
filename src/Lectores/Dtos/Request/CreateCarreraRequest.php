<?php

declare(strict_types=1);

namespace App\Lectores\Dtos\Request;

readonly class CreateCarreraRequest
{
    public string $cod;
    public string $nombre;

    public function __construct(string $cod, string $nombre)
    {
        $this->cod = $cod;
        $this->nombre = $nombre;
    }
}
