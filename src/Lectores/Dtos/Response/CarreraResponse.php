<?php

declare(strict_types=1);

namespace App\Lectores\Dtos\Response;

readonly class CarreraResponse
{
    public int $id;
    public string $cod;
    public string $nombre;

    public function __construct(int $id, string $cod, string $nombre)
    {
        $this->id = $id;
        $this->cod = $cod;
        $this->nombre = $nombre;
    }
}
