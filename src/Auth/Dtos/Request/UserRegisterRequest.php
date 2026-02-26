<?php

declare(strict_types=1);

namespace App\Auth\Dtos\Request;

use DateTimeImmutable;

readonly class UserRegisterRequest
{
    public function __construct(
        private string $dni,
        private string $password,
        private string $nombre,
        private string $apellido,
        private ?string $legajo,
        private string $genero,
        private DateTimeImmutable $fechaNacimiento,
        private string $telefono,
        private string $email,
        private ?string $crestaId
    ) {
    }

    public function getDni(): string
    {
        return $this->dni;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getNombre(): string
    {
        return $this->nombre;
    }

    public function getApellido(): string
    {
        return $this->apellido;
    }

    public function getLegajo(): ?string
    {
        return $this->legajo;
    }

    public function getGenero(): ?string
    {
        return $this->genero;
    }

    public function getFechaNacimiento(): DateTimeImmutable
    {
        return $this->fechaNacimiento;
    }

    public function getTelefono(): string
    {
        return $this->telefono;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getCrestaId(): ?string
    {
        return $this->crestaId;
    }
}
