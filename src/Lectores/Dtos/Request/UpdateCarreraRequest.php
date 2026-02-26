<?php

declare(strict_types=1);

namespace App\Lectores\Dtos\Request;

class UpdateCarreraRequest
{
    public ?string $cod;
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
