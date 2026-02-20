<?php

declare(strict_types=1);

namespace App\Auth\Dtos\Request;

use DateTimeImmutable;

readonly class UserRegisterRequest
{
    public string $dni;
    public string $password;
    public string $nombre;
    public string $apellido;
    public ?string $legajo;
    public ?string $genero;
    public DateTimeImmutable $fechaNacimiento;
    public string $telefono;
    public string $email;
    public ?string $crestaId;

    public function __construct(
        string $dni,
        string $password,
        string $nombre,
        string $apellido,
        ?string $legajo,
        ?string $genero,
        DateTimeImmutable $fechaNacimiento,
        string $telefono,
        string $email,
        ?string $crestaId
    ) {
        $this->dni = $dni;
        $this->password = $password;
        $this->nombre = $nombre;
        $this->apellido = $apellido;
        $this->legajo = $legajo;
        $this->genero = $genero;
        $this->fechaNacimiento = $fechaNacimiento;
        $this->telefono = $telefono;
        $this->email = $email;
        $this->crestaId = $crestaId;
    }
}
