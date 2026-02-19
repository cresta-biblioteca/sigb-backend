<?php

declare(strict_types=1);

namespace App\Auth\Dtos\Request;

use DateTimeImmutable;

class UserRegisterRequest {
    public readonly string $dni;
    public readonly string $password;
    // datos del lector
    public readonly string $nombre;
    public readonly string $apellido;
    public readonly ?string $legajo;
    public readonly ?string $genero;
    public readonly DateTimeImmutable $fechaNacimiento;
    public readonly string $telefono;
    public readonly string $email;
    public readonly ?string $crestaId;

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
